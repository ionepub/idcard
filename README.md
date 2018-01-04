# idcard
二代身份证号码验证，可用于验证二代身份证号码是否合法、从身份证号码中获取生日/性别/年龄/地区信息 A Chinese identity card number verification class

## 安装

### 使用composer安装（建议）

```
# 稳定版本
composer require ionepub/idcard
composer require --prefer-dist ionepub/idcard

# 开发版本
composer require ionepub/idcard:dev-master
```

> tip

如果`composer require ionepub/idcard`时报错：

```
[InvalidArgumentException]                                                   
  Could not find package ionepub/idcard at any version for your minimum-stabi  
  lity (stable). Check the package spelling or your minimum-stability
```

需要先执行一次 ` composer update nothing `，再执行require就可以了

### 直接下载

下载地址：https://github.com/ionepub/idcard/releases/tag/1.0

## 使用

### 引入包

- composer

```
require 'vendor/autoload.php';
```

- 文件引入

```
require 'idcard/src/Idcard.php';
```

### 实例化

```
use Ionepub\Idcard;
$idcard = Idcard::getInstance();
```

或直接使用

```
$idcard = Ionepub\Idcard::getInstance();
```

### 设置一个身份证号

```
$idcard->setId('130724197906126153');
```

也可以在创建实例时设置：

```
$idcard = Ionepub\Idcard::getInstance('130724197906126153');
```

> 设置身份证号的方法支持链式操作

### 获取身份证号

```
$id = $idcard->getId();
```

可以跟设置连起来用：

```
$id = $idcard->setId('130724197906126153')->getId();
```

### 检查身份证号码格式是否正确

```
$result = $idcard->check();

$result = $idcard->setId('130724197906126153')->check();
```

### 通过身份证号获取生日

可以通过给getBirthday()方法传递一个分隔符参数来设置返回的生日格式，默认`-`

```
$birthday = $idcard->getBirthday(); // 1979-06-12
$birthday = $idcard->getBirthday('.'); // 1979.06.12
$birthday = $idcard->getBirthday(''); // 19790612
```

### 通过身份证号获取年龄

```
$age = $idcard->getAge();
```

### 通过身份证号获取性别

可以通过给getGender()方法传递一个语言参数来设置返回的性格语言，默认中文

- $idcard::GENDER_CN  中文：男/女
- $idcard::GENDER_EN  英文：male/female

```
$gender = $idcard->getGender(); // 男
$gender = $idcard->getGender($idcard::GENDER_EN); // male
```

### 通过身份证号获取所在地区

可以通过给getRegion()方法传递一个分隔符来设置返回的地区信息格式，默认空格

```
$region = $idcard->getRegion(); // 河北省 张家口市 沽源县
$region = $idcard->getRegion(','); // 河北省,张家口市,沽源县
```

### 输出带*号的身份证号

有时候需要在输出身份证号的时候做一些字符处理，通过format()方法可以方便的转换成`前4位+N×*+后4位`格式

可以通过向format()方法传递一个分隔符参数来设置隐藏的数字替换符号，默认`*`

```
$id = $idcard->format(); // 1307**********6153
$id = $idcard->format('-'); // 1307----------6153
```

可以通过向format()方法传递一个左位数参数来设置左侧需要保留的位数

```
$id = $idcard->format('*', 3); // 130***********6153
```

可以通过向format()方法传递一个右位数参数来设置右侧需要保留的位数

```
$id = $idcard->format('*', 3, 2); // 130*************53
```

需要注意的是，左位数和右位数之和如果跟身份证号码总长度一致，就是原样输出，如果大于号码长度，则返回false
