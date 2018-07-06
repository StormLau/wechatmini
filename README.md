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

## 说明
获取access_token和文本检测需要用到access_token，所以需要给public文件夹给予777权限。

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

说明：直接获取access_token，不加任何处理，有次数限制，用此方法获取后可能会导致已经获取且在使用的token失效

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

### 获取access_token并且保存服务器

说明：获取access_token，由于微信对access_token获取次数有限制，此方法将token存服务器，需要时直接取服务器token，过期自动更新token

```php
\PhalApi\DI()->wechatmini->getAccessToken();
```

说明：直接获取access_token，不加任何处理，有次数限制，用此方法获取后可能会导致已经获取且在使用的token失效

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功                        |
| data  | 返回数据，access_token获取失败时为空                  |
| data.access_token  | 获取到的凭证                    |
| data.expires_in  | 凭证过期时间戳                |
| msg | 错误提示信息：如：invalid appid hint: [EAncHA01641466]       |


### 文本违规检测

说明：检查一段文本是否含有违法违规内容。应用场景举例：用户个人资料违规文字检测；媒体新闻类用户发表文章，评论内容检测；游戏类用户编辑上传的素材(如答题类小游戏用户上传的问题及答案)检测等。频率限制：单个 appId 调用上限为 2000 次/分钟，1,000,000 次/天


```php
\PhalApi\DI()->wechatmini->msgSecCheck('待检测内容');
```

说明：直接获取access_token，不加任何处理，有次数限制，用此方法获取后可能会导致已经获取且在使用的token失效

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功                        |
| data  | 返回数据，data                  |
| msg | 错误提示信息：如：invalid credential, access_token is invalid or not latest hint: [qaUhIa01589041]|