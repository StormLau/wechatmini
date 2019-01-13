<?php

namespace PhalApi\Wechatmini;

use PhalApi\Exception\BadRequestException;


/**
 * 微信小程序扩展
 *
 * @author: JamesLiu 2018-07-03
 */
class Lite
{

    protected $appid;
    protected $secret;
    protected $mch_id;
    protected $mch_key;

    protected $openid;            //openid
    protected $out_trade_no;      //商户订单号
    protected $body;              //商品描述
    protected $total_fee;         //总金额


    /**
     * @param string $appid 小程序appid
     * @param string $secret 小程序后台生成的秘钥，不要随便修改
     * @param string $mch_id 商户号,不用支付可以不用配置
     */

    public function __construct()
    {
        $this->appid = \PhalApi\DI()->config->get('app.Wechatmini')['appid'];
        $this->secret = \PhalApi\DI()->config->get('app.Wechatmini')['secret_key'];
        $this->mch_id = \PhalApi\DI()->config->get('app.Wechatmini')['mch_id'];
        $this->mch_key = \PhalApi\DI()->config->get('app.Wechatmini')['mch_key'];

        if (!$this->appid) {
            throw new BadRequestException('请配置appid', 600);
        }
        if (!$this->secret) {
            throw new BadRequestException('请配置secret_key', 600);
        }
    }

    /**
     * 获取openid
     * @desc 根据code获取openid
     * @return array
     * @return int ret 状态码：200表示数据获取成功，其他错误码可参考小程序错误码说明
     * @return array data 返回数据，openid获取失败时为空
     * @return string data.openid 用户唯一标识
     * @return string data.session_key 会话密钥
     * @return string data.unionid 用户在开放平台的唯一标识符，满足UnionID下发条件的情况下这个才有
     * @return string msg 错误提示信息：如：code been used, hints: [ req_id: OpwajA01912023 ]
     */
    public function getOpenid($code)
    {
        if (!$code) {
            throw new BadRequestException('请在小程序获取code', 600);
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appid}&secret={$this->secret}&js_code=$code&grant_type=authorization_code";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $out = curl_exec($ch);
        curl_close($ch);
        $jsondecode = json_decode($out, true); //对JSON格式的字符串进行编码
        if ($jsondecode['openid']) {
            return $jsondecode;
        } else {
            //openid获取失败
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    /**
     * 获取openid
     * @desc 根据code获取openid，unionid
     * @return array
     * @return int ret 状态码：200表示数据获取成功，其他错误码可参考小程序错误码说明
     * @return array data 返回数据，openid获取失败时为空
     * @return string code code
     * @return string iv 会话密钥
     * @return string encryptedData 解码内容
     * @return string msg 错误提示信息：如：code been used, hints: [ req_id: OpwajA01912023 ]
     */
    public function getUnionid($code, $iv, $encryptedData)
    {
        $ids = $this->getOpenid($code);
        if (!$ids['session_key']) {
            throw new BadRequestException('获取sessionKey失败', 600);
        }
        $sessionkey = str_replace(' ', '+', urldecode(trim($ids['session_key'])));
//        \PhalApi\DI()->logger->info('sessinokey:', $sessionkey);
        $iv = str_replace(' ', '+', urldecode($iv));
        $encryptedData = str_replace(' ', '+', urldecode($encryptedData));
        $aesKey = base64_decode($sessionkey);
        $aesIV = base64_decode($iv);
//        \PhalApi\DI()->logger->info('iv:', $iv);
        $aesCipher = base64_decode($encryptedData);
//        \PhalApi\DI()->logger->info('encryptedData:', $encryptedData);
        $jm = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $jsondecode = json_decode($jm, true);
//        \PhalApi\DI()->logger->info('rs', $jsondecode);
        if ($jsondecode['openId']) {
            return $jsondecode;
        } else {
            //openid获取失败
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    /**
     * 获取access_token
     * @desc 直接获取access_token，不加任何处理，有次数限制，用此方法获取后可能会导致已经获取且在使用的token失效
     * @return array
     * @return int ret 状态码：200表示数据获取成功
     * @return array data 返回数据，access_token获取失败时为空
     * @return string data.access_token 获取到的凭证
     * @return string data.expires_in 凭证有效时间，单位：秒
     * @return string msg 错误提示信息：如：invalid appid hint: [EAncHA01641466]
     */
    public function getToken()
    {
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $out = curl_exec($ch);
        curl_close($ch);
        $jsondecode = json_decode($out, true);
        if ($jsondecode['access_token']) {
            return $jsondecode;
        } else {
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    /**
     * 获取access_token
     * @desc 获取access_token，由于微信对access_token获取次数有限制，此方法将token存服务器，需要时直接取服务器token，过期自动更新token
     * @return array
     * @return int ret 状态码：200表示数据获取成功
     * @return array data 返回数据，access_token获取失败时为空
     * @return string data.access_token 获取到的凭证
     * @return string data.expires 凭证过期时间戳
     * @return string msg 错误提示信息：如：invalid appid hint: [EAncHA01641466]
     */
//    需要对public给其777权限
    public function getAccessToken()
    {
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $file = file_get_contents("./access_token.json", true);
        $result = json_decode($file, true);
        if (($result == null) || (time() > $result['expires'])) {
            //进行access_token更新
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $out = curl_exec($ch);
            curl_close($ch);
            $jsondecode = json_decode($out, true);
            if ($jsondecode['access_token']) {
                $result['access_token'] = $jsondecode['access_token'];
                $result['expires'] = time() + 7000;
                $jsonStr = json_encode($result);
                $fp = fopen("./access_token.json", "w");
                fwrite($fp, $jsonStr);
                fclose($fp);
            } else {
                throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
            }
        }
        return $result;
    }

    /**
     * 文本违规检测
     * @desc 检查一段文本是否含有违法违规内容。应用场景举例：用户个人资料违规文字检测；媒体新闻类用户发表文章，评论内容检测；游戏类用户编辑上传的素材(如答题类小游戏用户上传的问题及答案)检测等。频率限制：单个 appId 调用上限为 2000 次/分钟，1,000,000 次/天
     * @return array
     * @return int ret 状态码：200表示数据获取成功
     * @return array data 返回数据，ok表示内容正常;risky表示含有违法违规内容
     * @return string msg 错误提示信息：如：invalid credential, access_token is invalid or not latest hint: [qaUhIa01589041]
     */
    public function msgSecCheck($content)
    {
        if (!$content) {
            throw new BadRequestException('请输入待检测内容', 600);
        }
        $access_token = $this->getAccessToken()['access_token'];
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;
        $post_data = array(
            "content" => $content,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $rs = curl_exec($ch);
        curl_close($ch);
        $jsondecode = json_decode($rs, true);
        if ($jsondecode['errcode'] == 0 || $jsondecode['errcode'] == 87014) {
            return $jsondecode['errmsg'];
        } else {
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    /**
     * 发送模板消息
     * @desc 基于微信的通知渠道，我们为开发者提供了可以高效触达用户的模板消息能力，以便实现服务的闭环并提供更佳的体验。
     * @return array
     * @return int ret 状态码：200表示数据获取成功,其他错误码可参考小程序错误码说明
     * @return array data 返回数据，ok表示内容正常
     * @return string msg 错误提示信息：如：form id used count reach limit hint: [P90MbA0846ge20]
     */

    public function sendWeAppMessage($touser, $formid, $template_id, $page, $emphasis_keyword, $data)
    {
        $access_token = $this->getAccessToken()['access_token'];
        if (!$touser) {
            throw new BadRequestException('openid不允许为空', 600);
        }
        if (!$formid) {
            throw new BadRequestException('formid不允许为空', 600);
        }
        if (!$template_id) {
            throw new BadRequestException('template_id不允许为空', 600);
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
        $post_data = array(
            "touser" => $touser,
            "template_id" => $template_id,
            "page" => $page,
            "form_id" => $formid,
            "emphasis_keyword" => $emphasis_keyword,
            "data" => $data
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $rs = curl_exec($ch);
        curl_close($ch);
        $jsondecode = json_decode($rs, true);
        if ($jsondecode['errcode'] == 0) {
            return $jsondecode['errmsg'];
        } else {
            return $jsondecode['errmsg'];
//            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    /**
     * 微信小程序数据解密
     * @desc 小程序可以通过各种前端接口获取微信提供的开放数据。 考虑到开发者服务器也需要获取这些开放数据，微信会对这些数据做签名和加密处理。 开发者后台拿到开放数据后可以对数据进行校验签名和解密，来保证数据不被篡改。
     * @return array
     * @return int ret 状态码：200表示数据获取成功,其他错误码可参考小程序错误码说明
     * @return array data 返回解密后的数据
     * @return string msg 错误提示信息
     */

    public function WXBizDataCrypt($sessionKey, $encryptedData, $iv)
    {

        if (!$encryptedData) {
            throw new BadRequestException('待解密数据不允许为空', 600);
        }

        if (!$iv) {
            throw new BadRequestException('加密算法的初始向量不允许为空', 600);
        }

        if (strlen($sessionKey) != 24) {
            throw new BadRequestException('IllegalAesKey', -41001 - 400);
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            throw new BadRequestException('IllegalIv', -41002 - 400);
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            throw new BadRequestException('IllegalBuffer', -41003 - 400);
        }
        if ($dataObj->watermark->appid != $this->appid) {
            throw new BadRequestException('IllegalBuffer', -41003 - 400);
        }
        return $dataObj;
    }

    /**
     * 微信支付
     * @desc 商户在小程序中先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易后调起支付。
     * @return array
     * @return int ret 状态码：200表示数据获取成功
     * @return array data 返回数据,数据获取失败时为空
     * @return string data.appId 微信分配的小程序ID
     * @return string data.timeStamp 时间戳从1970年1月1日00:00:00至今的秒数,即当前的时间
     * @return string data.nonceStr 随机字符串，长度为32个字符以下。
     * @return string data.package 统一下单接口返回的 prepay_id 参数值
     * @return string data.signType 签名算法，暂支持 MD5
     * @return string data.paySign 签名,具体签名方案参见小程序支付接口文档;
     * @return string msg 错误提示信息
     */

    public function WxPay($openid, $total_fee, $body)
    {

        if (!$this->mch_id) {
            throw new BadRequestException('请配置商户号', 600);
        }
        if (!$this->mch_key) {
            throw new BadRequestException('请配置支付秘钥', 600);
        }
        if (!$openid) {
            throw new BadRequestException('openid不能为空', 600);
        }

        if ($total_fee < 0.01) {
            throw new BadRequestException('付款金额最低0.01', 600);
        }
        if (!$total_fee) {
            throw new BadRequestException('付款金额不能为空', 600);
        }
        if (!$body) {
            $body = '商品充值';
        }

        $out_trade_no = date("YmdHis");
        $chars = '0123456789';
        $max = strlen($chars) - 1;
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < 4; $i++) {
            $out_trade_no .= $chars[mt_rand(0, $max)];
        }
        $this->out_trade_no = $out_trade_no;
        $this->openid = $openid;
        $this->body = $body;
        $this->total_fee = $total_fee;
//        统一下单接口
        $res = $this->weixinapp();
        return $res;
    }

    //统一下单接口
    private function unifiedorder()
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid' => $this->appid, //小程序ID
            'mch_id' => $this->mch_id, //商户号
            'nonce_str' => $this->createNoncestr(), //随机字符串
            'body' => $this->body,//商品描述
            'out_trade_no' => $this->out_trade_no,//商户订单号
            'total_fee' => floatval($this->total_fee * 100),//总金额 单位 分
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //终端IP
            'notify_url' => 'http://www.weixin.qq.com/wxpay/pay.php', //通知地址  确保外网能正常访问
            'openid' => $this->openid, //用户id
            'trade_type' => 'JSAPI'//交易类型
        );
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $xml = $this->postXmlCurl($xmlData, $url, 60);
        $return = $this->xmlToArray($xml);
        return $return;
    }


    private static function postXmlCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);


        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);


        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new BadRequestException('curl出错', $error);
        }
    }


    //数组转换成xml
    private function arrayToXml($arr)
    {
        $xml = "<root>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . $this->arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</root>";
        return $xml;
    }


    //xml转换成数组
    private function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    //微信小程序接口
    private function weixinapp()
    {
        //统一下单接口
        $unifiedorder = $this->unifiedorder();
        $parameters = array(
            'appId' => $this->appid, //小程序ID
            'timeStamp' => '' . time() . '', //时间戳
            'nonceStr' => $this->createNoncestr(), //随机串
            'package' => 'prepay_id=' . $unifiedorder['prepay_id'], //数据包
            'signType' => 'MD5'//签名方式
        );
        //签名
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }


    //作用：产生随机字符串，不长于32位
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    //作用：生成签名
    private function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->mch_key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    ///作用：格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
