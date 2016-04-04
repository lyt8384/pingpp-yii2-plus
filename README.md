# pingpp-Yii2-plus
pingxx伪基于Yii2的封装

[![Latest Stable Version](https://poser.pugx.org/lyt8384/pingpp-yii2-plus/v/stable)](https://packagist.org/packages/lyt8384/pingpp-yii2-plus) [![Total Downloads](https://poser.pugx.org/lyt8384/pingpp-yii2-plus/downloads)](https://packagist.org/packages/lyt8384/pingpp-yii2-plus) [![Latest Unstable Version](https://poser.pugx.org/lyt8384/pingpp-yii2-plus/v/unstable)](https://packagist.org/packages/lyt8384/pingpp-yii2-plus) [![License](https://poser.pugx.org/lyt8384/pingpp-yii2-plus/license)](https://packagist.org/packages/lyt8384/pingpp-yii2-plus)

最近主要又换Yii2开发了，其实不封装这个也是可以直接用官方那个的，只不过每次都要额外设置key什么的，搜了下，有别的大神写了一个很完善的版本，但是用起来感觉没在Laravel里那么爽，所以就把代码复制过来改了个名。这个版本的优点就是，官方SDK更新了。这边不用更新。
本人比较菜，没写单元测试，有比较在行的朋友帮补一个？

# 配置方法
1. 在`composer.json`里添加如下内容，并运行`composer update`:
```json
{
    "require": {
        "lyt8384/pingpp-yii2-plus": "dev-master"
    }
}
```
1. 在`config/web.php`文件里的components变量下添加配置
```php
'pingpp' => [
    'class' => '\lyt8384\pingpp\Pingpp',
    'test_secret_key' => 'YOUR-TEST-KEY',
    'live_secret_key' => 'YOUR-LIVE-KEY',
    'live' => true,	//测试时请设置为false
    'pub_key_path' => '/path/to/pingpp_rsa_public_key.pem',	//该处不填不进行回调验证
    'private_key_path' => '/path/to/your_rsa_private_key.pem'	//该处不填不进行商家验证
],
```
# 使用方法
```php
use Yii;

class SomeClass extends Controller {
    
    public function someFunction()
    {
    	$pingpp = Yii::$app->pingpp;
    	$pingpp->Charge->create([
            'order_no'  => '123456789',
		    'amount'    => '100',
		    'app'       => array('id' => 'app_xxxxxxxxxxxxxx'),
		    'channel'   => 'upacp',
		    'currency'  => 'cny',
		    'client_ip' => '127.0.0.1',
		    'subject'   => 'Your Subject',
		    'body'      => 'Your Body'
        ]);
    }
}
```

```php
use Yii;

class SomeClass extends Controller {
    
    public function someFunction()
    {
    	$pingpp = Yii::$app->pingpp;
    	$pingpp->RedEnvelope->create([
            'order_no'  => '123456789',
	        'app'       => array('id' => 'APP_ID'),
	        'channel'   => 'wx_pub',
	        'amount'    => 100,
	        'currency'  => 'cny',
	        'subject'   => 'Your Subject',
	        'body'      => 'Your Body',
	        'extra'     => array(
	            'nick_name' => 'Nick Name',
	            'send_name' => 'Send Name'
	        ),
	        'recipient'   => 'Openid',
	        'description' => 'Your Description'
        ]);
    }
}
```

# 错误调用
当Pingpp调用发生错误的时候会`return false`，此时调用`Yii::$app->pingpp->getError();`返回具体错误内容。

# 接收 Webhooks 通知
直接调用`Yii::$app->pingpp->notice()`，若验证成功,会返回通知的`array`结构数据,若失败直接弹出错Http误回Pingpp。并产生一条Warning级别的错误日志。

# IDE自动提示
可以按照 [IDE autocompletion for custom components](https://github.com/samdark/yii2-cookbook/blob/master/book/ide-autocompletion.md) 设置，在`Yii.php`扩展的映射下面增加`@property \lyt8384\pingpp\Pingpp $pingpp Simple Pingpp wrapper for Yii2`

其他使用方法见官方文档[PingPlusPlus](https://github.com/PingPlusPlus/pingpp-php)