<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use think\facade\Db;
use think\facade\Log;
use WeChat\Contracts\BasicWeChat;

class WeChatController extends AuthController
{
    public $wx_config;

    public function initialize()
    {
        parent::initialize();// 初始化方法

        $plugin_config        = cmf_get_option('weipay');
        $this->wx_system_type = $plugin_config['wx_system_type'];//默认 读配置可手动修改
        if ($this->wx_system_type == 'wx_mini') {//wx_mini:小程序
            $appid     = $plugin_config['wx_mini_app_id'];
            $appsecret = $plugin_config['wx_mini_app_secret'];
        } else {//wx_mp:公众号
            $appid     = $plugin_config['wx_mp_app_id'];
            $appsecret = $plugin_config['wx_mp_app_secret'];
        }
        $this->wx_config = [
            //微信基本信息
            'token'             => $plugin_config['wx_token'],
            'wx_mini_appid'     => $plugin_config['wx_mini_app_id'],//小程序 appid
            'wx_mini_appsecret' => $plugin_config['wx_mini_app_secret'],//小程序 secret
            'wx_mp_appid'       => $plugin_config['wx_mp_app_id'],//公众号 appid
            'wx_mp_appsecret'   => $plugin_config['wx_mp_app_secret'],//公众号 secret
            'appid'             => $appid,//读取默认 appid
            'appsecret'         => $appsecret,//读取默认 secret
            'encodingaeskey'    => $plugin_config['wx_encodingaeskey'],
            // 配置商户支付参数
            'mch_id'            => $plugin_config['wx_mch_id'],
            'mch_key'           => $plugin_config['wx_v2_mch_secret_key'],
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //	'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'           => './upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => './upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $this->wx_system_type,//wx_mini:小程序 wx_mp:公众号
        ];

    }


    /**
     * 发货测试用
     *
     *   test_environment: http://pw216.ikun/api/wxapp/we_chat/send_shipping
     *   official_environment: https://pw216.aejust.net/api/wxapp/we_chat/send_shipping
     *   api: /wxapp/we_chat/send_shipping
     *   remark_name: 发货测试用
     *
     *
     */
    public function send_shipping()
    {
        $order_sn       = '31024012125932297865052';
        $openid         = 'o9MqX61dNfV7Q05cPnoc2eVLVunc';
        $item_desc      = '商品发货';
        $logistics_type = 3;
        $result         = $this->uploadShippingInfo($order_sn, $openid, $item_desc, $logistics_type);
        $this->success('成功', $result);
    }

    /**
     * 订阅消息发送 测试用
     *
     *   test_environment: http://pw216.ikun/api/wxapp/we_chat/send_test
     *   official_environment: https://pw216.aejust.net/api/wxapp/we_chat/send_test
     *   api: /wxapp/we_chat/send_test
     *   remark_name: 订阅消息发送 测试用
     *
     */
    public function send_test()
    {
        $openid = 111;
        // 发送订阅消息
        $templateId = 'Qq8AdEvte8s_HjfedaH3SBf8Yq9-SAkEr7-am3Vm8Hk';
        $send_date  = [
            'character_string1' => ['value' => cmf_order_sn()],
            'time2'             => ['value' => date('Y-m-d H:i:s', time())],
            'thing3'            => ['value' => '订单配送中!'],
        ];
        $result     = $this->sendSubscribeMessage($openid, $templateId, $send_date);
        $this->success('成功', $result);
    }


    /**
     * 小程序->订单发货
     * @param        $order_sn         : 订单编号，我们自己生成的订单编号
     * @param        $openid           : 用户openID
     * @param        $item_desc        : 物品信息 必填
     * @param int    $logistics_type   : 物流模式 1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
     * @param string $express_company  : 物流公司编码  配合腾讯的物流公司编码数据表
     * @param string $tracking_no      : 物流单号
     * @param string $receiver_contact : 顺丰是需要收货人手机号  15236182399
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function uploadShippingInfo($order_sn, $openid, $item_desc, int $logistics_type = 1, string $express_company = '', string $tracking_no = '', string $receiver_contact = '')
    {
        try {
            $shipping_list = [
                'tracking_no'     => $tracking_no,              //物流单号，物流快递发货时必填
                'express_company' => $express_company,      //物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填
                'item_desc'       => $item_desc,                  //商品信息，例如：微信红包抱枕*1个  必填
            ];
            if ($express_company == 'SF') {
                if (empty($receiver_contact)) return ['code' => 0, 'msg' => '顺丰快递需填写收件人手机号'];
                $shipping_list['contact']['receiver_contact'] = substr_replace($receiver_contact, '****', -8, 4);
                //联系方式，当发货的物流公司为顺丰时，联系方式为必填，收件人或寄件人联系方式二选一
                // 'receiver_contact' 收件人联系方式，收件人联系方式为，采用掩码传输，最后4位数字不能打掩码 示例值: `189****1234, 021-****1234, ****1234, 0**2-***1234, 0**2-******23-10, ****123-8008` 值限制: 0 ≤ value ≤ 1024 字段加密: 使用APIv3定义的方式加密
            }
            $param = [
                'order_key'      => [
                    'order_number_type' => 1,
                    'mchid'             => $this->wx_config['mch_id'],//商户号
                    'out_trade_no'      => $order_sn,
                ],
                'logistics_type' => $logistics_type,
                'delivery_mode'  => 1,
                'shipping_list'  => [$shipping_list],
                'upload_time'    => date("c", time()),
                'payer'          => [
                    'openid' => $openid
                ]
            ];

            $BasicWeChat  = new BasicWeChat($this->wx_config);
            $access_token = $BasicWeChat->getAccessToken();
            $url          = 'https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token=' . $access_token;
            $return       = $BasicWeChat->callPostApi($url, $param);
            if (!empty($return) && $return['errcode'] == 0) {
                return ['code' => 1, 'msg' => '调用成功', 'data' => ''];
            } else {
                return ['code' => 0, 'msg' => $return['errmsg'], 'data' => ''];
            }
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage(), 'data' => ''];
        }
    }


    /**
     * 小程序->发送订阅消息
     * @param $openid
     * @param $templateId
     * @param $send_date 通知参数
     * @return mixed
     */
    public function sendSubscribeMessage($openid, $templateId, $send_date)
    {
        $access_token = $this->get_stable_access_token();
        $url          = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$access_token}";
        $data         = [
            'touser'      => $openid,
            'template_id' => $templateId,
            'data'        => $send_date,
        ];
        $result       = $this->curl_post($url, json_encode($data));
        return json_decode($result, true);
    }

    /**
     * 获取超稳定 access_token
     * 该接口调用频率限制为 1万次 每分钟，每天限制调用 50万 次
     *
     *   test_environment: http://pw216.ikun/api/wxapp/we_chat/get_stable_access_token
     *   official_environment: https://pw216.aejust.net/api/wxapp/we_chat/get_stable_access_token
     *   api: /wxapp/we_chat/get_stable_access_token
     *   remark_name: 获取稳定access_token
     *
     *
     */
    public function get_stable_access_token()
    {
        $wx_config = $this->wx_config;
        $appid     = $wx_config['appid'];
        $secret    = $wx_config['appsecret'];
        $url2      = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        //小程序信息获取token
        $param['grant_type'] = 'client_credential';
        $param['appid']      = $appid;
        $param['secret']     = $secret;
        $res                 = $this->curl_post($url2, json_encode($param));
        $data                = json_decode($res, true);
        $token               = $data['access_token'];
        return $token;
    }


}
