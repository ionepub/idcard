<?php
/**
 * Idcard.class.php
 * A Chinese identity card number verification class 二代身份证号码验证类
 * 可用于验证二代身份证号码是否合法、从身份证号码中获取生日/性别/年龄/地区信息
 * @author: ionepub
 * @version 1.1.0
 * GitHub repo: https://github.com/ionepub/idcard
 * @date 2017-09
 */
namespace Ionepub;

/**
 * class Idcard
 */
class Idcard
{
	/**
	 * 性别显示方式：中文 [ 男, 女 ]
	 * @access public
	 * @var string
	 */
	const GENDER_CN = 'cn';

	/**
	 * 性别显示方式：英文 [ male, female ]
	 * @access public
	 * @var string
	 */
	const GENDER_EN = 'en';

	/**
	 * 单例实例
	 * @access private
	 * @var class object
	 */
	private static $_instance;

	/**
	 * 身份证号
	 * @access private
	 * @var string
	 */
	private $id = '';

	/**
	 * 验证结果
	 * @access private
	 * @var bool
	 */
	private $isValid = false;

	/**
	 * 地区列表
	 * @access private
	 * @var array
	 */
	private static $region_list;

	/**
	 * 加权因子
	 * @access private
	 * @var array
	 */
	private static $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

	/**
	 * 校验码对应值
	 * @access private
	 * @var array
	 */
	private static $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

	/**
	 * 构造函数
	 * @access private
	 */
	private function __construct(){}

	/**
	 * 返回单例实例/初始化地区列表
	 * @access public
	 * @param string $id 身份证号参数，可选，传递时设置id
	 * @return object
	 */
	public static function getInstance($id = ''){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self();

			// 初始化地区列表
			$path = dirname(dirname(__FILE__));
			$region_list = file_get_contents($path.'/res/region.json');
			self::$region_list = json_decode($region_list, true);
		}
		// 如果传入了id，则设置id
		if(trim($id)){
			self::$_instance->setId(trim($id));
		}
		return self::$_instance;
	}

	/**
	 * 设置身份证号码参数，返回实例，可链式操作
	 * @access public
	 * @param string $id 身份证号参数
	 * @return object
	 */
	public function setId($id = ''){
		if(trim($id)){
			$this->id = strtoupper(trim($id));
			$this->isValid = false;
		}
		return self::$_instance;
	}

	/**
	 * 返回当前设置的身份证号码
	 * @access public
	 * @return string
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * 验证身份证号码是否正确
	 * @access public
	 * @return bool
	 */
	public function check(){
		return $this->isValid || ($this->checkFormat() && $this->checkArea() && $this->checkBirthday() && $this->setValid(true));
	}

	/**
	 * 通过身份证号获取生日信息
	 * @access public
	 * @param string $seperate 年月日之间的分隔符，默认-
	 * @return string | false
	 */
	public function getBirthday($seperate = '-'){
		if($this->check()){
			$birthday_arr = array(
				substr($this->id, 6, 4),
				substr($this->id, 10, 2),
				substr($this->id, 12, 2),
			);
			return implode( $seperate, $birthday_arr );
		}
		return false;
	}

	/**
	 * 通过身份证号码获取年龄
	 * @access public
	 * @return int | false
	 */
	public function getAge(){
		$birthday = $this->getBirthday("-");
		if($birthday){
			list($year, $month, $day) = explode("-", $birthday);
			$year_diff = date("Y") - $year;
	        $month_diff = date("m") - $month;
	        $day_diff  = date("d") - $day;
	        if ($day_diff < 0 || $month_diff < 0){
	            $year_diff--; // 不满一岁
	        }
	        return intval($year_diff);
		}
		return false;
	}

	/**
	 * 通过身份证获取性别信息
	 * @access public
	 * @param string $lang 性别显示方式 [GENDER_CN | GENDER_EN]
	 * @return string | int | false
	 */
	public function getGender($lang = self::GENDER_CN){
		if(!$this->check()){
			return false;
		}
		// 倒数第2位
		$gender = substr($this->id, 16, 1);

		return $this->parseGender($gender % 2 == 0, $lang);
	}

	/**
	 * 通过身份证获取地区信息(中国)
	 * @access public
	 * @param string $seperate 省市区的分隔符，默认空格
	 * @return string | false
	 */
	public function getRegion($seperate = ' '){
		if(!$this->check()){
			return false;
		}
		$province = substr($this->id, 0, 2) . '0000';
		$city = substr($this->id, 0, 4) . '00';
		$district = substr($this->id, 0, 6);
		$region_arr = array(
			self::$region_list[ $province ],
			self::$region_list[ $city ],
			self::$region_list[ $district ],
		);
		return implode( $seperate, $region_arr );
	}

	/**
     * 给用户身份证号加*，用于输出数据
     * 规则：前4位 + N* + 后4位
     * @param $idcard
     * @TODO
     */
    function formatOutputIdCardNo($idcard = ''){
        $sublen = mb_strlen($idcard, 'UTF-8') - 8;
        return mb_substr($idcard, 0, 4, 'UTF-8') . str_repeat('*', $sublen) . mb_substr($idcard, $sublen+4, null, 'UTF-8');
    }

	/**
	 * 检查身份证号码格式是否正确
	 * 基础格式 18位长度 6位地区码+8位日期+3位随机数+1位校验码
	 * @access private
	 * @return bool
	 */
	private function checkFormat(){
		return preg_match("/^[\d]{6}(18|19|20)\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}[xX\d]$/", $this->id);
	}

	/**
	 * 检查身份证号码上的地区码是否合法
	 * @access private
	 * @return bool
	 */
	private function checkArea(){
		$area_code = substr($this->id, 0, 6);
		return isset( self::$region_list[ $area_code ] );
	}

	/**
	 * 检查身份证号码上的生日日期是否合法
	 * @access private
	 * @return bool
	 */
	private function checkBirthday(){
		$year = intval(substr($this->id, 6, 4));
		$month = intval(substr($this->id, 10, 2));
		$day = intval(substr($this->id, 12, 2));
		return checkdate($month, $day, $year);
	}

	/**
	 * 检验最后一位校验码是否正确
	 * @access private
	 * @return bool
	 */
	private function checkCode(){
		// 取出本体码
        $idcard_base = substr($this->id, 0, 17);

        // 取出校验码
        $verify_code = substr($this->id, 17, 1);

		// 根据前17位计算校验码
        $total = 0;
        for($i = 0; $i < 17; $i++){
            $total += substr($idcard_base, $i, 1) * self::$factor[$i];
        }

        // 取模
        $mod = $total % 11;

		return $verify_code == self::$verify_code_list[$mod];
	}

	/**
	 * 设置isValid属性，该方法始终返回true
	 * @access private
	 * @param bool $isValid 是否合法
	 * @return true
	 */
	private function setValid($isValid = false){
		$this->isValid = $isValid;
		return true;
	}

	/**
	 * 按语言格式返回性别
	 * 当语言为中文时，返回[男/女]，当语言为英文时，返回[male,female]，否则返回[1/0]
	 * @access private
	 * @param bool $gender 是否男性
	 * @param string $lang 性别显示方式 [GENDER_CN | GENDER_EN]
	 * @return string | int | false
	 */
	private function parseGender($gender, $lang){
		if($lang == self::GENDER_CN){
			return $gender ? '女' : '男';
		}elseif ($lang == self::GENDER_EN) {
			return $gender ? 'female' : 'male';
		}else{
			return $gender ? 0 : 1;
		}
	}
}
