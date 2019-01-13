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
        'mch_id' => '商户号',//不用支付可以不用配置
        'mch_key' => '支付秘钥',//不用支付可以不用配置
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



### 获取Unionid

```php
\PhalApi\DI()->wechatmini->getUnionid('小程序返回请求的code'，'会话密钥', '解码内容');
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


返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功                        |
| data  | 返回数据，ok表示内容正常;risky表示含有违法违规内容|
| msg | 错误提示信息：如：invalid credential, access_token is invalid or not latest hint: [qaUhIa01589041]|


### 发送模板消息

说明：基于微信的通知渠道，我们为开发者提供了可以高效触达用户的模板消息能力，以便实现服务的闭环并提供更佳的体验。


```php
\PhalApi\DI()->wechatmini->sendWeAppMessage('touser', 'formid', 'template_id', 'page', 'emphasis_keyword', 'data');
```

参数说明：

|      参数      | 必填 |说明                                               |
| -------------  | -----|----------------------------------------------|
| touser | 是 |接收者（用户）的 openid|
| formid  | 是 |表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id|
| template_id | 是 |所需下发的模板消息的id（微信公众平台模板消息选择获取）|
| page | 否 |点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。|
| emphasis_keyword  | 否 |模板需要放大的关键词，不填则默认无放大|
| data | 是 |模板内容，不填则下发空模板|

示例：

```php
$openid = '接收者（用户）的 openid';
$form_id = "小程序端获取的formid",
$template_id = 'eb_azL3MW76Hn_U9yu6GZOf5Lm90AROUruI1OdECYUQ';
$page = 'pages/index/index';
$emphasis_keyword = 'keyword2.DATA';
$data = array(
    "keyword1" => array("value" => "我们已收到您的反馈意见，感谢您的使用", "color" => "#173177"),
    "keyword2" => array("value" => "感谢使用Phalapi", "color" => "#173177"));
$res = \PhalApi\DI()->wechatmini->sendWeAppMessage($openid, $formid, $template_id, $page,$emphasis_keyword, $data);
return $res;
```

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   |状态码：200表示数据获取成功,其他错误码可参考小程序错误码说明|
| data  | 返回数据，ok表示成功发送模板消息|
| msg | 错误提示信息：错误提示信息：如：form id used count reach limit hint: [P90MbA0846ge20]|

错误码说明：

|      返回码      | 说明                                               |
| -------------  | ---------------------------------------------------|
| 40037   |template_id不正确|
| 41028  |form_id不正确，或者过期|
| 41029 | form_id已被使用|
| 41030 | page不正确|
| 45009 | 接口调用超过限额（目前默认每个帐号日调用限额为100万）|


### 微信预支付


说明：商户在小程序中先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易后调起支付。


```php
\PhalApi\DI()->wechatmini->WxPay('付款者openid', '付款金额', '商品描述');
```

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   | 状态码：200表示数据获取成功                        |
| data  | 返回数据,数据获取失败时为空                 |
| data.appId  | 微信分配的小程序ID                |
| data.timeStamp  | 时间戳从1970年1月1日00:00:00至今的秒数,即当前的时间               |
| data.nonceStr  | 随机字符串，长度为32个字符以下。                 |
| data.package  | 统一下单接口返回的 prepay_id 参数值                 |
| data.signType  | 签名算法，暂支持 MD5                 |
| data.paySign  | 签名,具体签名方案参见小程序支付接口文档;                |
| msg | 错误提示信息：如：invalid credential, access_token is invalid or not latest hint: [qaUhIa01589041]|


### 微信小程序数据解密

说明：小程序可以通过各种前端接口获取微信提供的开放数据。 考虑到开发者服务器也需要获取这些开放数据，微信会对这些数据做签名和加密处理。 开发者后台拿到开放数据后可以对数据进行校验签名和解密，来保证数据不被篡改。


```php
\PhalApi\DI()->wechatmini->WXBizDataCrypt('sessionKey', 'encryptedData', 'iv');
```

参数说明：

|      参数      | 必填 |说明                                               |
| -------------  | -----|----------------------------------------------|
| sessionKey | 是 |会话密钥，wx.login可获取|
| encryptedData  | 是 |包括敏感数据在内的完整用户信息的加密数据|
| iv | 是 |加密算法的初始向量|

返回结果：

|      返回字段      | 说明                                               |
| -------------  | ---------------------------------------------------|
| ret   |状态码：200表示数据获取成功,其他错误码可参考小程序错误码说明|
| data  | 返回数据，返回解密后的数据|
| msg | 错误提示信息：错误提示信息|

错误码说明：

|      返回码      | 说明                                               |
| -------------  | ---------------------------------------------------|
| -41001   |IllegalAesKey|
| -41002  |IllegalIv|
| -41003 | IllegalBuffer|