# wechatmini
PhalApi 2.x 微信小程序扩展

## 安装和配置
修改项目下的composer.json文件，并添加：  
```
    "phalapi/wechatmini":"dev-master"
```
然后执行```composer update```。  

## 安装成功后，添加以下配置到./config/app.php文件：

```php
'Wechatmini' => array(
        'appid' => '你的appid',
        'secret_key' => '微信小程序后台生成的secret_key，请勿随便修改',
    ),
```

## 注册
在/path/to/phalapi/config/di.php文件中，注册： 
 
```php
$di->wechatmini = function() {
        return new \PhalApi\Wechatmini\Lite();
};
```

## 使用

### 获取openid

```php
\PhalApi\DI()->wechatmini->getOpenid('小程序返回请求的code');
```

### 获取access_token

```php
\PhalApi\DI()->wechatmini->getToken();
```


