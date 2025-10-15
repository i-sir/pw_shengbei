<?php

namespace api\wxapp\controller;

use think\facade\Log;

class PayController extends AuthController
{
    const KEY          = 'Qwertyuiopasdfghjklzxcvbnm123456';//用不到
    const SSLCERT_PATH = '证书路径';
    const SSLKEY_PATH  = '证书key路径';
    const MCHID        = '商户号';
    const APPID        = 'app_id';

    /**
     调用方法
     //微信打款
    $pay_result = $Pay->transfer_batches($withdrawal_info['openid'], $withdrawal_info['price'], $withdrawal_info['order_num']);
    if (empty($pay_result['code'])) $this->error($pay_result['msg']);

    //更新日志
    $pay_data               = $pay_result['data'];
    $update['batch_id']     = $pay_data['batch_id'];
    $update['batch_status'] = $pay_data['batch_status'];
    $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($update);
     */

    public function initialize()
    {
        parent::initialize();//初始化方法


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
            'ssl_key'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $this->wx_system_type,//wx_mini:小程序 wx_mp:公众号
        ];
    }


    /**
     * 付款到微信零钱
     * @param string $sOpenid   收款方openid
     * @param float  $nMoney    转账金额 | 最低打款0.5元
     * @param int    $order_num 订单号
     * @return array
     *
     *  https://pw216.aejust.net/api/wxapp/pay/transfer_batches
     */
    public function transfer_batches($sOpenid = '***', $nMoney = 0.01, $order_num = 001)
    {
        // 付款到零钱方法url
        $url = 'https://api.mch.weixin.qq.com/v3/transfer/batches';

        // 订单号
        $sOrderId = $order_num;
        // 转账备注 （微信用户会收到该备注）
        $tRemark = '提现';
        // 转账金额：微信是分为单位 *100 转换
        $transfer_amount = $nMoney * 100;

        // 转账接收列表设置
        $transfer_detail_list = array(
            [
                'out_detail_no'   => $sOrderId,                  // 明细单号
                'transfer_amount' => intval($transfer_amount),   // 转账总金额
                'transfer_remark' => $tRemark,                   // 单条转账备注
                'openid'          => $sOpenid,                   // 收款方openid
                // 'user_name'       => '张三',
                // 转账金额 >= 2,000元，收款用户姓名必填
            ],
        );
        // 请求参数设置
        $params = [
            'appid'                => $this->wx_config['appid'],                 // 文档顶部定义
            'out_batch_no'         => $sOrderId,                   // 商家批次单号
            'batch_name'           => date('Y-m-d H:i:s') . '转账',                 // 转账的名称
            'batch_remark'         => date('Y-m-d H:i:s') . '提现',                 // 转账的备注
            'total_amount'         => intval($transfer_amount),    // 转账总金额
            'total_num'            => 1,                           // 转账总笔数
            'transfer_detail_list' => $transfer_detail_list,       // 转账接收列表
        ];

        Log::write('wx_transfer_batches_params');
        Log::write($params);

        // 获取token
        $token = $this->getToken($params);
        // 发送请求
        $res = $this->https_request($url, json_encode($params), $token);
        // 反馈数组化
        $resArr = json_decode($res, true);

        // 存储转账成功信息或别的操作
        Log::write('wx_transfer_batches');
        Log::write($resArr);

        //$this->success('请求成功', $resArr);

        if (empty($resArr['batch_id'])) return ['code' => 0, 'msg' => $resArr['message'], 'data' => ''];


        return ['code' => 1, 'msg' => '成功', 'data' => $resArr];


        //$this->success('请求成功', $resArr);
        /**
         * 成功实例
         * {
         * "code": 1,
         * "msg": "请求成功",
         * "data": {
         * "batch_id": "131000004007906294405702024050898355148119",
         * "batch_status": "ACCEPTED",
         * "create_time": "2024-05-08T17:40:59+08:00",
         * "out_batch_no": "240508612590699569"
         * }
         * }
         */


    }


    /**
     * 构造请求
     */
    function https_request($url, $data = null, $token)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, (string)$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // 添加请求头
        $headers = [
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token,
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }

    /**
     * 获取token
     */
    public function getToken($pars)
    {
        $url         = 'https://api.mch.weixin.qq.com/v3/transfer/batches';
        $http_method = 'POST';                                  // 请求方法（GET,POST,PUT）
        $timestamp   = time();                                  // 请求时间戳
        $url_parts   = parse_url($url);                         // 获取请求的绝对URL
        $nonce       = $timestamp . rand('10000', '99999');     // 请求随机串
        $body        = json_encode((object)$pars);              // 请求报文主体
        $stream_opts = [
            "ssl" => [
                "verify_peer"      => false,
                "verify_peer_name" => false,
            ]
        ];

        // 证书路径信息：文档顶部定义   ps:必须绝对路径
        $apiclient_cert_path = $this->wx_config['ssl_cer'];//self::SSLCERT_PATH
        $apiclient_key_path  = $this->wx_config['ssl_key'];//self::SSLKEY_PATH

        $apiclient_cert_arr = openssl_x509_parse(file_get_contents($apiclient_cert_path, false, stream_context_create($stream_opts)));
        // 证书序列号
        $serial_no = $apiclient_cert_arr['serialNumberHex'];
        // 密钥
        $mch_private_key = file_get_contents($apiclient_key_path, false, stream_context_create($stream_opts));
        // 商户id：文档顶部定义
        $merchant_id   = $this->wx_config['mch_id'];
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message       = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        // 签名
        $sign   = base64_encode($raw_sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token  = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        // 微信返回token
        return $token;
    }


}