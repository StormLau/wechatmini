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

    protected $config;

    /**
     * @param string $config ['appid']  小程序appid
     * @param string $config ['secret_key'] 小程序后台生成的秘钥，不要随便修改
     */

    public function __construct()
    {
        $this->config = \PhalApi\DI()->config->get('app.Wechatmini');
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
        $appid = $this->config['appid'];
        $secret = $this->config['secret_key'];
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
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
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->config['appid']}&secret={$this->config['secret_key']}";
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
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->config['appid']}&secret={$this->config['secret_key']}";
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
     * 图片违规检测
     * @desc 校验一张图片是否含有违法违规内容。应用场景举例：1）图片智能鉴黄：涉及拍照的工具类应用(如美拍，识图类应用)用户拍照上传检测；电商类商品上架图片检测；媒体类用户文章里的图片检测等；2）敏感人脸识别：用户头像；媒体类用户文章里的图片检测；社交类用户上传的图片检测等。频率限制：单个 appId 调用上限为 1000 次/分钟，100,000 次/天
     * @return array
     * @return int ret 状态码：200表示数据获取成功
     * @return array data 返回数据，ok表示内容正常;risky表示含有违法违规内容
     * @return string msg 错误提示信息：如：invalid credential, access_token is invalid or not latest hint: [qaUhIa01589041]
     */
    public function imgSecCheck($image)
    {
        $access_token = $this->getAccessToken()['access_token'];
        $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=' . $access_token;
        $post_data = array(
            "media" => $image['tmp_name'],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "media=@test.jpg");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $rs = curl_exec($ch);
        curl_close($ch);
        $jsondecode = json_decode($rs, true);
        if ($jsondecode['errcode'] == 0 || $jsondecode['errcode'] == 87014) {
            return $jsondecode['errmsg'];
        } else {
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

}
