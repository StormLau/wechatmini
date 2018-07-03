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

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功，其他错误码可参考小程序错误码说明                          |
| data  | 返回数据，openid获取失败时为空                   |
| data.openid  | 用户唯一标识                    |
| data.session_key  | 会话密钥                    |
| data.unionid  | 用户在开放平台的唯一标识符，满足UnionID下发条件的情况下这个才有                    |
| msg | 错误提示信息：如：code been used, hints: [ req_id: OpwajA01912023 ]                    |



### 获取access_token

```php
\PhalApi\DI()->wechatmini->getToken();
```

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功                        |
| data  | 返回数据，access_token获取失败时为空                  |
| data.access_token  | 获取到的凭证                    |
| data.expires_in  | 凭证有效时间，单位：秒                    |
| msg | 错误提示信息：如：invalid appid hint: [EAncHA01641466]                  |


