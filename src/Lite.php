<?php

namespace PhalApi\Pinyin;

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


    public function getOpenid($code)
    {
        $appid = $this->config['appid'];
        $secret = $this->config['secret_key'];
        $weixin = file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code");
        $jsondecode = json_decode($weixin, true); //对JSON格式的字符串进行编码
        if ($jsondecode['openid']) {
            return $jsondecode;
        } else {
            //openid获取失败
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

    public function getToken()
    {
        $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->config['appid']}&secret={$this->config['secret_key']}";
        $res = file_get_contents($token_url); //对JSON格式的字符串进行编码
        $jsondecode = json_decode($res, true);
        if ($jsondecode['access_token']) {
            return $jsondecode;
        } else {
            throw new BadRequestException($jsondecode['errmsg'], $jsondecode['errcode'] - 400);
        }
    }

}
