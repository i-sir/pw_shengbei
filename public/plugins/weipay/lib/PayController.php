<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\weipay\lib;

use Exception;
use Yansongda\Pay\Contract\ParserInterface;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Parser\ArrayParser;

class PayController
{

    /**
     * @var array
     */
    public $wx_config;

    public function __construct()
    {
        try {
            //获取初始化信息
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
                'wx_notify_url'     => cmf_get_domain() . $plugin_config['wx_notify_url'],//微信支付回调地址
            ];


            $this->ali_config = [
                // 沙箱模式
                'debug'       => false,
                // 签名类型（RSA|RSA2）
                'sign_type'   => "RSA2",
                // 应用ID
                'appid'       => $plugin_config['ali_app_id'],
                // 支付宝公钥内容 (1行填写，特别注意：这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
                'public_key'  => './upload/' . $plugin_config['ali_alipay_public_cert_path'],
                // 支付宝私钥内容 (1行填写)
                'private_key' => $plugin_config['ali_app_secret_cert'],
                // 应用公钥证书内容（新版资金类接口转 app_cert_sn）
                'app_cert'    => './upload/' . $plugin_config['ali_app_public_cert_path'],
                // 支付宝根证书内容（新版资金类接口转 alipay_root_cert_sn）
                'root_cert'   => './upload/' . $plugin_config['ali_alipay_root_cert_path'],
                // 支付成功通知地址
                'notify_url'  => cmf_get_domain() . $plugin_config['ali_notify_url'],
                // 网页支付回跳地址
                'return_url'  => cmf_get_domain() . $plugin_config['ali_return_url'],
            ];


            $config = [
                'alipay' => [
                    'default' => [
                        // 必填-支付宝分配的 app_id
                        'app_id'                  => $plugin_config['ali_app_id'],
                        // 必填-应用私钥 字符串或路径
                        'app_secret_cert'         => $plugin_config['ali_app_secret_cert'],
                        // 必填-应用公钥证书 路径
                        'app_public_cert_path'    => './upload/' . $plugin_config['ali_app_public_cert_path'],
                        // 必填-支付宝公钥证书 路径
                        'alipay_public_cert_path' => './upload/' . $plugin_config['ali_alipay_public_cert_path'],
                        // 必填-支付宝根证书 路径
                        'alipay_root_cert_path'   => './upload/' . $plugin_config['ali_alipay_root_cert_path'],
                        'return_url'              => cmf_get_domain() . $plugin_config['ali_return_url'],
                        'notify_url'              => cmf_get_domain() . $plugin_config['ali_notify_url'],
                        // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                        'service_provider_id'     => '',
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                        'mode'                    => Pay::MODE_NORMAL
                    ]
                ],
                'wechat' => [
                    'default' => [
                        // 必填-商户号，服务商模式下为服务商商户号
                        'mch_id'                  => $plugin_config['wx_mch_id'],
                        // 必填-商户秘钥
                        'mch_secret_key'          => $plugin_config['wx_v3_mch_secret_key'],
                        // 必填-商户私钥 字符串或路径
                        'mch_secret_cert'         => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_secret_cert'],
                        // 必填-商户公钥证书路径
                        'mch_public_cert_path'    => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_public_cert_path'],
                        // 必填
                        'notify_url'              => cmf_get_domain() . $plugin_config['wx_notify_url'],
                        // 选填-公众号 的 app_id
                        'mp_app_id'               => $plugin_config['wx_mp_app_id'],
                        // 选填-小程序 的 app_id
                        'mini_app_id'             => $plugin_config['wx_mini_app_id'],
                        // 选填-app 的 app_id
                        'app_id'                  => $plugin_config['wx_app_id'],
                        // 选填-合单 app_id
                        'combine_app_id'          => '',
                        // 选填-合单商户号
                        'combine_mch_id'          => '',
                        // 选填-服务商模式下，子公众号 的 app_id
                        'sub_mp_app_id'           => '',
                        // 选填-服务商模式下，子 app 的 app_id
                        'sub_app_id'              => '',
                        // 选填-服务商模式下，子小程序 的 app_id
                        'sub_mini_app_id'         => '',
                        // 选填-服务商模式下，子商户id
                        'sub_mch_id'              => '',
                        // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                        'wechat_public_cert_path' => [
                            //'45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__.'/Cert/wechatPublicKey.crt',
                        ],
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                        //'mode' => Pay::MODE_NORMAL,
                    ]
                ],
                'logger' => [
                    //打开日志系统需安装 composer require monolog/monolog
                    'enable'   => false,
                    'file'     => './plugins/weipay/log/log.log',
                    'level'    => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type'     => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http'   => [ // optional
                              'timeout'         => 5.0,
                              'connect_timeout' => 5.0,
                ],
            ];

            Pay::config($config);
            //设置参数返回类型为数组
            Pay::set(ParserInterface::class, ArrayParser::class);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * 微信公众号支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $openid    | 用户openid
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_mp($order_num, float $amount, $openid): array
    {
        if (empty($openid)) return ['code' => 0, 'msg' => 'openid为空', 'data' => ''];

        $wechat = new \WeChat\Pay($this->wx_config);
        $amount = round($amount * 100);
        // 组装参数，可以参考官方商户文档
        $options = [
            'body'             => '订单支付',
            'out_trade_no'     => $order_num,
            'total_fee'        => $amount,
            'openid'           => $openid,
            'trade_type'       => 'JSAPI',
            'notify_url'       => $this->wx_config['wx_notify_url'],
            'spbill_create_ip' => '127.0.0.1',
        ];


        try {
            // 生成预支付码
            $result = $wechat->createOrder($options);
            if ($result['result_code'] != 'SUCCESS') return ['code' => 0, 'msg' => $result['err_code_des'], 'data' => $result];

            // 创建JSAPI参数签名
            $result = $wechat->createParamsForJsApi($result['prepay_id']);

            // @todo 把 $options 传到前端用js发起支付就可以了
            return ['code' => 1, 'msg' => '请求成功', 'data' => $result];

        } catch (Exception $e) {
            // 出错啦，处理下吧
            return ['code' => 0, 'msg' => $e->getMessage(), 'data' => ''];
        }
    }

    /**
     * 微信H5支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_h5($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->wap([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ],
                'scene_info'   => [
                    'payer_client_ip' => $_SERVER['SERVER_ADDR'],
                    'h5_info'         => [
                        'type' => 'Wap',
                    ]
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信App支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_app($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->app([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ]
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信扫码支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_scan($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->scan([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ]
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信小程序支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $openid    | 用户openid
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_mini($order_num, float $amount, $openid): array
    {
        if (empty($openid)) return ['code' => 0, 'msg' => 'openid为空', 'data' => ''];

        $amount = round($amount * 100);

        try {
            $result = Pay::wechat()->mini([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total'    => $amount,
                    'currency' => 'CNY',
                ],
                'payer'        => [
                    'openid' => $openid,
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝网页支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_web($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->web([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝H5支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_wap($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->wap([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝App支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_app($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->app([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝小程序支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $buyer_id  | 小程序用户id
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_mini($order_num, float $amount, $buyer_id): array
    {
        try {
            $result = Pay::alipay()->mini([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
                'buyer_id'     => $buyer_id,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝刷卡支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $auth_code | 小程序用户id
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_pos($order_num, float $amount, $auth_code): array
    {
        try {
            $result = Pay::alipay()->pos([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
                'auth_code'    => $auth_code,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝扫码支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_scan($order_num, float $amount): array
    {
        try {
            $result = Pay::alipay()->scan([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信订单退款
     * @param $transaction_id | 第三方单号
     * @param $order_num      | 系统订单号
     * @param $amount         | 退款金额
     * @param $total          | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_refund($transaction_id, $order_num, float $amount, float $total = 0): array
    {
        if ($amount < 0.01) return ['code' => 0, 'msg' => '金额不能小于0.01', 'data' => ''];

        $amount = round($amount * 100);
        if (!empty($total)) {
            $total = round($total * 100);
        } else {
            $total = $amount;
        }

        //处理退款
        $options = [
            'transaction_id' => $transaction_id,
            'out_refund_no'  => $order_num,
            'total_fee'      => $amount,
            'refund_fee'     => $total,
        ];


        $wechat = new \WeChat\Pay($this->wx_config);
        $result = $wechat->createRefund($options);


        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝订单退款
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_refund($order_num, float $amount): array
    {
        if ($amount < 0.1) return ['code' => 0, 'msg' => '金额不能小于0.1', 'data' => ''];
        try {
            $result = Pay::alipay()->refund([
                'out_trade_no'  => $order_num,
                'refund_amount' => $amount,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝转账
     * @param        $amount    | 转账金额
     * @param        $identity  | 支付宝账号
     * @param        $name      | 支付宝用户姓名
     * @param string $order_num | 订单号 默认自动获取时间戳
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_transfer222(float $amount, $identity, $name, string $order_num = ''): array
    {
        if (empty($order_num)) $order_num = time();

        $plugin_config = cmf_get_option('weipay');
//        $config = [
//            // 沙箱模式
//            'debug'       => false,
//            // 签名类型（RSA|RSA2）
//            'sign_type'   => "RSA2",
//            // 应用ID
//            'appid'       => $plugin_config['ali_app_id'],
//            // 支付宝公钥内容 (1行填写，特别注意：这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
//            'public_key'  => 'MIIDsjCCApqgAwIBAgIQICQRBSsdHOR7P2EoTU9saTANBgkqhkiG9w0BAQsFADCBgjELMAkGA1UEBhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0aG9yaXR5MTkwNwYDVQQDDDBBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IENsYXNzIDIgUjEwHhcNMjQxMTA1MDczNzE1WhcNMjkxMTA0MDczNzE1WjCBkjELMAkGA1UEBhMCQ04xLTArBgNVBAoMJOa1juWNl+iVieWunee9kee7nOenkeaKgOaciemZkOWFrOWPuDEPMA0GA1UECwwGQWxpcGF5MUMwQQYDVQQDDDrmlK/ku5jlrp0o5Lit5Zu9Kee9kee7nOaKgOacr+aciemZkOWFrOWPuC0yMDg4OTQxNjE1ODI0MjY2MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhty+YmjcmCVXVmnT8fuah4YxWISOtY1yXtpRCDhEGe6dOw5J40pZEkbm5iBGYzMJX1QkWsQcpVBtzcsKRQ/EZ6SI9zSofIS9CWUbk3UQBg+XEvyZi14vZXyUh7nT/HdnBahcdKzI+EcpN8djTN3r09d1f0zUgKzX989Zzm2WkcmUHvL5wGPg0rSqcv1dtUZVUeODpp9n1F6exLwSJ7sJvFEP3jW+CvVvgb8Vd527OhOTpub630oPL0UozPgjPUAlvGzbJz7cTB+D2HWRoQIDFm3m/xTTz6h3Qfbl93JOkYqMk4b6ZPZqz/SSh46dowmZy5Pw71SYaaBwZOlSCpgKTQIDAQABoxIwEDAOBgNVHQ8BAf8EBAMCA/gwDQYJKoZIhvcNAQELBQADggEBAK33SPB15WaBQhmoNRQJdc4Tl75BByxHCJ9NaKWIsFdbu36mVuj6bYwoPHoWH+wB0QCSJd8fKa3vJh9DJTQ6oYjZOq9UPm5q7pnSZryPkkh8sGBQeg1VA+O0fsVxhQk102KGebnvBX8CicRUzpDb093oIfePrPrZdgqN63VEk0+vrjXlODDcL1gBgOsBEq9MOUnDIQE7mMbxkgdRdoui2VxNRnV94EzKuTckZO/6vppjknIs8vM9AGY7PsszRc3EUCr6gk3OIH9JiNcS/M5fyuiJOdp4a6wlZ9Gu8tUSuPZ0RG1xHa5D/DPabF3sedaBcv/xESnQ0nthWI1fLqt5YUc=',
//            // 支付宝私钥内容 (1行填写)
//            'private_key' => $plugin_config['ali_app_secret_cert'],
//            // 应用公钥证书内容（新版资金类接口转 app_cert_sn）
//            'app_cert'    => './upload/' . $plugin_config['ali_app_public_cert_path'],
//            // 支付宝根证书内容（新版资金类接口转 alipay_root_cert_sn）
//            'root_cert'   => './upload/' . $plugin_config['ali_alipay_root_cert_path'],
//            // 支付成功通知地址
//            'notify_url'  => cmf_get_domain() . $plugin_config['ali_notify_url'],
//            // 网页支付回跳地址
//            'return_url'  => cmf_get_domain() . $plugin_config['ali_return_url'],
//        ];
        //        $result = $pay->apply([
        //            'out_biz_no'      => $order_num, // 订单号
        //            'payee_type'      => 'ALIPAY_LOGONID', // 收款方账户类型(ALIPAY_LOGONID | ALIPAY_USERID)
        //            'payee_account'   => $identity, // 收款方账户
        //            'amount'          => 1, // 转账金额
        //            'payer_show_name' => '未寒', // 付款方姓名
        //            'payee_real_name' => $name, // 收款方真实姓名
        //            'remark'          => '张三', // 转账备注
        //        ]);
        //        dump($result);
        //        exit;

        $config = [
            // 沙箱模式
            'debug'       => false,
            // 签名类型（RSA|RSA2）
            'sign_type'   => "RSA2",
            // 应用ID
            'appid'       => '2021004192698744',
            // 支付宝公钥内容 (1行填写，特别注意：这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
            'public_key'  => '-----BEGIN CERTIFICATE-----
MIIDsjCCApqgAwIBAgIQICQRBSsdHOR7P2EoTU9saTANBgkqhkiG9w0BAQsFADCBgjELMAkGA1UE
BhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0
aG9yaXR5MTkwNwYDVQQDDDBBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IENs
YXNzIDIgUjEwHhcNMjQxMTA1MDczNzE1WhcNMjkxMTA0MDczNzE1WjCBkjELMAkGA1UEBhMCQ04x
LTArBgNVBAoMJOa1juWNl+iVieWunee9kee7nOenkeaKgOaciemZkOWFrOWPuDEPMA0GA1UECwwG
QWxpcGF5MUMwQQYDVQQDDDrmlK/ku5jlrp0o5Lit5Zu9Kee9kee7nOaKgOacr+aciemZkOWFrOWP
uC0yMDg4OTQxNjE1ODI0MjY2MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhty+Ymjc
mCVXVmnT8fuah4YxWISOtY1yXtpRCDhEGe6dOw5J40pZEkbm5iBGYzMJX1QkWsQcpVBtzcsKRQ/E
Z6SI9zSofIS9CWUbk3UQBg+XEvyZi14vZXyUh7nT/HdnBahcdKzI+EcpN8djTN3r09d1f0zUgKzX
989Zzm2WkcmUHvL5wGPg0rSqcv1dtUZVUeODpp9n1F6exLwSJ7sJvFEP3jW+CvVvgb8Vd527OhOT
pub630oPL0UozPgjPUAlvGzbJz7cTB+D2HWRoQIDFm3m/xTTz6h3Qfbl93JOkYqMk4b6ZPZqz/SS
h46dowmZy5Pw71SYaaBwZOlSCpgKTQIDAQABoxIwEDAOBgNVHQ8BAf8EBAMCA/gwDQYJKoZIhvcN
AQELBQADggEBAK33SPB15WaBQhmoNRQJdc4Tl75BByxHCJ9NaKWIsFdbu36mVuj6bYwoPHoWH+wB
0QCSJd8fKa3vJh9DJTQ6oYjZOq9UPm5q7pnSZryPkkh8sGBQeg1VA+O0fsVxhQk102KGebnvBX8C
icRUzpDb093oIfePrPrZdgqN63VEk0+vrjXlODDcL1gBgOsBEq9MOUnDIQE7mMbxkgdRdoui2VxN
RnV94EzKuTckZO/6vppjknIs8vM9AGY7PsszRc3EUCr6gk3OIH9JiNcS/M5fyuiJOdp4a6wlZ9Gu
8tUSuPZ0RG1xHa5D/DPabF3sedaBcv/xESnQ0nthWI1fLqt5YUc=
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
MIIE4jCCAsqgAwIBAgIIYsSr5bKAMl8wDQYJKoZIhvcNAQELBQAwejELMAkGA1UEBhMCQ04xFjAU
BgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0aG9yaXR5MTEw
LwYDVQQDDChBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IFIxMB4XDTE4MDMy
MjE0MzQxNVoXDTM3MTEyNjE0MzQxNVowgYIxCzAJBgNVBAYTAkNOMRYwFAYDVQQKDA1BbnQgRmlu
YW5jaWFsMSAwHgYDVQQLDBdDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTE5MDcGA1UEAwwwQW50IEZp
bmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eSBDbGFzcyAyIFIxMIIBIjANBgkqhkiG9w0B
AQEFAAOCAQ8AMIIBCgKCAQEAsLMfYaoRoPRbmDcAfXPCmKf43pWRN5yTXa/KJWO0l+mrgQvs89bA
NEvbDUxlkGwycwtwi5DgBuBgVhLliXu+R9CYgr2dXs8D8Hx/gsggDcyGPLmVrDOnL+dyeauheARZ
fA3du60fwEwwbGcVIpIxPa/4n3IS/ElxQa6DNgqxh8J9Xwh7qMGl0JK9+bALuxf7B541Gr4p0WEN
G8fhgjBV4w4ut9eQLOoa1eddOUSZcy46Z7allwowwgt7b5VFfx/P1iKJ3LzBMgkCK7GZ2kiLrL7R
iqV+h482J7hkJD+ardoc6LnrHO/hIZymDxok+VH9fVeUdQa29IZKrIDVj65THQIDAQABo2MwYTAf
BgNVHSMEGDAWgBRfdLQEwE8HWurlsdsio4dBspzhATAdBgNVHQ4EFgQUSqHkYINtUSAtDPnS8Xoy
oP9p7qEwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8EBAMCAQYwDQYJKoZIhvcNAQELBQADggIB
AIQ8TzFy4bVIVb8+WhHKCkKNPcJe2EZuIcqvRoi727lZTJOfYy/JzLtckyZYfEI8J0lasZ29wkTt
a1IjSo+a6XdhudU4ONVBrL70U8Kzntplw/6TBNbLFpp7taRALjUgbCOk4EoBMbeCL0GiYYsTS0mw
7xdySzmGQku4GTyqutIGPQwKxSj9iSFw1FCZqr4VP4tyXzMUgc52SzagA6i7AyLedd3tbS6lnR5B
L+W9Kx9hwT8L7WANAxQzv/jGldeuSLN8bsTxlOYlsdjmIGu/C9OWblPYGpjQQIRyvs4Cc/mNhrh+
14EQgwuemIIFDLOgcD+iISoN8CqegelNcJndFw1PDN6LkVoiHz9p7jzsge8RKay/QW6C03KNDpWZ
EUCgCUdfHfo8xKeR+LL1cfn24HKJmZt8L/aeRZwZ1jwePXFRVtiXELvgJuM/tJDIFj2KD337iV64
fWcKQ/ydDVGqfDZAdcU4hQdsrPWENwPTQPfVPq2NNLMyIH9+WKx9Ed6/WzeZmIy5ZWpX1TtTolo6
OJXQFeItMAjHxW/ZSZTok5IS3FuRhExturaInnzjYpx50a6kS34c5+c8hYq7sAtZ/CNLZmBnBCFD
aMQqT8xFZJ5uolUaSeXxg7JFY1QsYp5RKvj4SjFwCGKJ2+hPPe9UyyltxOidNtxjaknOCeBHytOr
-----END CERTIFICATE-----',
            // 支付宝私钥内容 (1行填写)
            'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCl45LSIbcgR5R28vkmoNYSZs85yJR+tjVaDUrKSRHnFB+UNMjdWe5YTnvBoExVoTlhCj4tkx5bZG0HrOmHBQEwY1lSIH9o/gWBYciM5/cLWZo8TbK4xi6izfirYY8hxc+CbiMM60a0db05xunEWEApmXgyf2pu7+qviBSHFLmtzbSOtqRwC0rxaDBxkpDmlGUX2c0wdDWyXXyTU+Gt0xZUDiMdjGJbIfuP9T8hEkrFPjHVdkDqchHtTJqO7goVNjvqRQVkJtEAAhlbVkawFwawZAWQB2MlwyhFUNEfHd8mJMMBHqk84yKw0kXTlUDdNEfzTi2Xb00l1jecyU/J+8mtAgMBAAECggEAFSK/rCI7kNNN6THf6LoJTGLo+DXEJbjVZ1nWM5vHuidoNpvbE8jHg1zMi8j+sNJP2ztQ3MGn1oEfGyE+x8MteZrO4JXfZeMnlGp/AOT1Ju8Npa1Inh4yBvAzRLKzZ9wqKjYaJSU85vUxXNEpK6kK08IH/HcbenL69c5ivys21Rvx1jVc5ZWFOS6aoBxbL7XtaqQyshWoICJnGJ7SXr5qvrQIoxK3o7zuuM6QCFMKgJMP3Da2cc0bNf32MwOi9e7yPNtDEiDgwioOy2GeMnyreNe2aOzBHHVwUM9tHUG8NMFS0aH+w5eWJnMZs+DmG1RL2EoyyDH+HIW910YjjEF7CQKBgQDY+L4IO1CNZc3DUdR1Jw4jNuq0nFRfTKQX0P2ojSJINWUxhSb15u3og1kQWwYjVmgGXy/0TNtbtUqIa54iVryMD0K4KLRAiXtAGlzfjO70MvHQQG6F+rFCDQKKv6v89Sd/s/BM41ieOiTcD6+nKpOc41JL5zXtpVUewE2DlKqkdwKBgQDDuopzRzwIsum3LGe72IH96+vthnCWBUF6/GgopYqPDRlrhYe9UUvZ+IUaBWquGQFyTkcy6Gjgbso0chwnd0nqW7q/HgRJ2tEiyl+xc2T2LSU3b/+RxaJW2jEHYQNYRgkcNoewHzevgdwxkYlw2ni28KY/680ED95pN5oQMnb/+wKBgQDRAZQ1Y8Xt34J9w2bwz4Vr4Kvo/aq8/pwXoSeoZQQAIQdw0347ZJAK6fQysCxSgBrHAIy2Pg8U4aeBkIGNPJZ2KQExW2x/uq/yiTKr2hwZOrX70QVmpJ56LQQk2gx4KUQ6XQB/YIVuLj5xid7AHmCBwez11yz41soPTFmfBef3cwKBgHX33LKWYKytiQgKD4u8drzgkRZcTUdea5UAxJabD+QgdQ3FMYb9lMYPb8m7Mg00rRaD743TXkLHA8CQdj+jOj2yg9/k65jH9f0OFJcTgeqOUzwSmOr3P10xrRNReX6e16bVhvq0FhAGKP0HRttqEg/RA6LLMIoeNmMcTBMvF2I5AoGAf6t5BY+zmKp1jxYkEH79ArQU8bvNJH8blcGUKep6zB0FQzJ/KPJjQtiilpPMCiT+DL/aukkCnxLazraANUmmYMSj/muMEs6J+LGTWmFu1IPGD1RuMFxLgrpUSgIY7572vdtQETzZSHJUOCGJWLwLeISqjYZjtp1SsFhvyU8qECE=',
            // 应用公钥证书内容（新版资金类接口转 app_cert_sn）
            'app_cert'    => '-----BEGIN CERTIFICATE-----
MIIEoDCCA4igAwIBAgIQICUBJPUKaP9hwvFj3B1M1TANBgkqhkiG9w0BAQsFADCBgjELMAkGA1UE
BhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmljYXRpb24gQXV0
aG9yaXR5MTkwNwYDVQQDDDBBbnQgRmluYW5jaWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IENs
YXNzIDEgUjEwHhcNMjUwMTI0MDUxNzIxWhcNMzAwMTIzMDUxNzIxWjBoMQswCQYDVQQGEwJDTjEt
MCsGA1UECgwk5rWO5Y2X6JWJ5a6d572R57uc56eR5oqA5pyJ6ZmQ5YWs5Y+4MQ8wDQYDVQQLDAZB
bGlwYXkxGTAXBgNVBAMMEDIwODg5NDE2MTU4MjQyNjYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAw
ggEKAoIBAQCl45LSIbcgR5R28vkmoNYSZs85yJR+tjVaDUrKSRHnFB+UNMjdWe5YTnvBoExVoTlh
Cj4tkx5bZG0HrOmHBQEwY1lSIH9o/gWBYciM5/cLWZo8TbK4xi6izfirYY8hxc+CbiMM60a0db05
xunEWEApmXgyf2pu7+qviBSHFLmtzbSOtqRwC0rxaDBxkpDmlGUX2c0wdDWyXXyTU+Gt0xZUDiMd
jGJbIfuP9T8hEkrFPjHVdkDqchHtTJqO7goVNjvqRQVkJtEAAhlbVkawFwawZAWQB2MlwyhFUNEf
Hd8mJMMBHqk84yKw0kXTlUDdNEfzTi2Xb00l1jecyU/J+8mtAgMBAAGjggEpMIIBJTAfBgNVHSME
GDAWgBRxB+IEYRbk5fJl6zEPyeD0PJrVkTAdBgNVHQ4EFgQUxEuph9ygqzCFtXBsBWvyH+oUJd8w
QAYDVR0gBDkwNzA1BgdggRwBbgEBMCowKAYIKwYBBQUHAgEWHGh0dHA6Ly9jYS5hbGlwYXkuY29t
L2Nwcy5wZGYwDgYDVR0PAQH/BAQDAgbAMC8GA1UdHwQoMCYwJKAioCCGHmh0dHA6Ly9jYS5hbGlw
YXkuY29tL2NybDk3LmNybDBgBggrBgEFBQcBAQRUMFIwKAYIKwYBBQUHMAKGHGh0dHA6Ly9jYS5h
bGlwYXkuY29tL2NhNi5jZXIwJgYIKwYBBQUHMAGGGmh0dHA6Ly9jYS5hbGlwYXkuY29tOjgzNDAv
MA0GCSqGSIb3DQEBCwUAA4IBAQCNsFSEInATy4I/aoIS2Am28Tc5KdP+txtzMOT0qnY01Ucm11zV
DaYVXcYi9DtWj5LgB2gUCxzgfgh732D2A2ZoLMY4zDsz/3VOR4yia9ivVYAPKmbctLT8DTZ/BzRC
/snedHbJoUR8WIrQNWbZ2v5L8CRx3vTbVXdMxTC7f/lFbh3CrBT9AK9pXsN8syMLw9e/Io4XUHNA
3A2asMxuCWym8FdzWnT3U00XB0yNxCTOG2RtNZ/OJiJmNYYlQr6PyHAriPdBGuQFb8cZgnnbAhUU
Uz7Y+pVkiQWD7bUgqL7Z6YVdBwaQuLjBAL+EGtqx/yR08TlgzQgGd0xlUh/BawAC
-----END CERTIFICATE-----',
            // 支付宝根证书内容（新版资金类接口转 alipay_root_cert_sn）
            'root_cert'   => '-----BEGIN CERTIFICATE-----
MIIBszCCAVegAwIBAgIIaeL+wBcKxnswDAYIKoEcz1UBg3UFADAuMQswCQYDVQQG
EwJDTjEOMAwGA1UECgwFTlJDQUMxDzANBgNVBAMMBlJPT1RDQTAeFw0xMjA3MTQw
MzExNTlaFw00MjA3MDcwMzExNTlaMC4xCzAJBgNVBAYTAkNOMQ4wDAYDVQQKDAVO
UkNBQzEPMA0GA1UEAwwGUk9PVENBMFkwEwYHKoZIzj0CAQYIKoEcz1UBgi0DQgAE
MPCca6pmgcchsTf2UnBeL9rtp4nw+itk1Kzrmbnqo05lUwkwlWK+4OIrtFdAqnRT
V7Q9v1htkv42TsIutzd126NdMFswHwYDVR0jBBgwFoAUTDKxl9kzG8SmBcHG5Yti
W/CXdlgwDAYDVR0TBAUwAwEB/zALBgNVHQ8EBAMCAQYwHQYDVR0OBBYEFEwysZfZ
MxvEpgXBxuWLYlvwl3ZYMAwGCCqBHM9VAYN1BQADSAAwRQIgG1bSLeOXp3oB8H7b
53W+CKOPl2PknmWEq/lMhtn25HkCIQDaHDgWxWFtnCrBjH16/W3Ezn7/U/Vjo5xI
pDoiVhsLwg==
-----END CERTIFICATE-----

-----BEGIN CERTIFICATE-----
MIIF0zCCA7ugAwIBAgIIH8+hjWpIDREwDQYJKoZIhvcNAQELBQAwejELMAkGA1UE
BhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNVBAsMF0NlcnRpZmlj
YXRpb24gQXV0aG9yaXR5MTEwLwYDVQQDDChBbnQgRmluYW5jaWFsIENlcnRpZmlj
YXRpb24gQXV0aG9yaXR5IFIxMB4XDTE4MDMyMTEzNDg0MFoXDTM4MDIyODEzNDg0
MFowejELMAkGA1UEBhMCQ04xFjAUBgNVBAoMDUFudCBGaW5hbmNpYWwxIDAeBgNV
BAsMF0NlcnRpZmljYXRpb24gQXV0aG9yaXR5MTEwLwYDVQQDDChBbnQgRmluYW5j
aWFsIENlcnRpZmljYXRpb24gQXV0aG9yaXR5IFIxMIICIjANBgkqhkiG9w0BAQEF
AAOCAg8AMIICCgKCAgEAtytTRcBNuur5h8xuxnlKJetT65cHGemGi8oD+beHFPTk
rUTlFt9Xn7fAVGo6QSsPb9uGLpUFGEdGmbsQ2q9cV4P89qkH04VzIPwT7AywJdt2
xAvMs+MgHFJzOYfL1QkdOOVO7NwKxH8IvlQgFabWomWk2Ei9WfUyxFjVO1LVh0Bp
dRBeWLMkdudx0tl3+21t1apnReFNQ5nfX29xeSxIhesaMHDZFViO/DXDNW2BcTs6
vSWKyJ4YIIIzStumD8K1xMsoaZBMDxg4itjWFaKRgNuPiIn4kjDY3kC66Sl/6yTl
YUz8AybbEsICZzssdZh7jcNb1VRfk79lgAprm/Ktl+mgrU1gaMGP1OE25JCbqli1
Pbw/BpPynyP9+XulE+2mxFwTYhKAwpDIDKuYsFUXuo8t261pCovI1CXFzAQM2w7H
DtA2nOXSW6q0jGDJ5+WauH+K8ZSvA6x4sFo4u0KNCx0ROTBpLif6GTngqo3sj+98
SZiMNLFMQoQkjkdN5Q5g9N6CFZPVZ6QpO0JcIc7S1le/g9z5iBKnifrKxy0TQjtG
PsDwc8ubPnRm/F82RReCoyNyx63indpgFfhN7+KxUIQ9cOwwTvemmor0A+ZQamRe
9LMuiEfEaWUDK+6O0Gl8lO571uI5onYdN1VIgOmwFbe+D8TcuzVjIZ/zvHrAGUcC
AwEAAaNdMFswCwYDVR0PBAQDAgEGMAwGA1UdEwQFMAMBAf8wHQYDVR0OBBYEFF90
tATATwda6uWx2yKjh0GynOEBMB8GA1UdIwQYMBaAFF90tATATwda6uWx2yKjh0Gy
nOEBMA0GCSqGSIb3DQEBCwUAA4ICAQCVYaOtqOLIpsrEikE5lb+UARNSFJg6tpkf
tJ2U8QF/DejemEHx5IClQu6ajxjtu0Aie4/3UnIXop8nH/Q57l+Wyt9T7N2WPiNq
JSlYKYbJpPF8LXbuKYG3BTFTdOVFIeRe2NUyYh/xs6bXGr4WKTXb3qBmzR02FSy3
IODQw5Q6zpXj8prYqFHYsOvGCEc1CwJaSaYwRhTkFedJUxiyhyB5GQwoFfExCVHW
05ZFCAVYFldCJvUzfzrWubN6wX0DD2dwultgmldOn/W/n8at52mpPNvIdbZb2F41
T0YZeoWnCJrYXjq/32oc1cmifIHqySnyMnavi75DxPCdZsCOpSAT4j4lAQRGsfgI
kkLPGQieMfNNkMCKh7qjwdXAVtdqhf0RVtFILH3OyEodlk1HYXqX5iE5wlaKzDop
PKwf2Q3BErq1xChYGGVS+dEvyXc/2nIBlt7uLWKp4XFjqekKbaGaLJdjYP5b2s7N
1dM0MXQ/f8XoXKBkJNzEiM3hfsU6DOREgMc1DIsFKxfuMwX3EkVQM1If8ghb6x5Y
jXayv+NLbidOSzk4vl5QwngO/JYFMkoc6i9LNwEaEtR9PhnrdubxmrtM+RjfBm02
77q3dSWFESFQ4QxYWew4pHE0DpWbWy/iMIKQ6UZ5RLvB8GEcgt8ON7BBJeMc+Dyi
kT9qhqn+lw==
-----END CERTIFICATE-----

-----BEGIN CERTIFICATE-----
MIICiDCCAgygAwIBAgIIQX76UsB/30owDAYIKoZIzj0EAwMFADB6MQswCQYDVQQG
EwJDTjEWMBQGA1UECgwNQW50IEZpbmFuY2lhbDEgMB4GA1UECwwXQ2VydGlmaWNh
dGlvbiBBdXRob3JpdHkxMTAvBgNVBAMMKEFudCBGaW5hbmNpYWwgQ2VydGlmaWNh
dGlvbiBBdXRob3JpdHkgRTEwHhcNMTkwNDI4MTYyMDQ0WhcNNDkwNDIwMTYyMDQ0
WjB6MQswCQYDVQQGEwJDTjEWMBQGA1UECgwNQW50IEZpbmFuY2lhbDEgMB4GA1UE
CwwXQ2VydGlmaWNhdGlvbiBBdXRob3JpdHkxMTAvBgNVBAMMKEFudCBGaW5hbmNp
YWwgQ2VydGlmaWNhdGlvbiBBdXRob3JpdHkgRTEwdjAQBgcqhkjOPQIBBgUrgQQA
IgNiAASCCRa94QI0vR5Up9Yr9HEupz6hSoyjySYqo7v837KnmjveUIUNiuC9pWAU
WP3jwLX3HkzeiNdeg22a0IZPoSUCpasufiLAnfXh6NInLiWBrjLJXDSGaY7vaokt
rpZvAdmjXTBbMAsGA1UdDwQEAwIBBjAMBgNVHRMEBTADAQH/MB0GA1UdDgQWBBRZ
4ZTgDpksHL2qcpkFkxD2zVd16TAfBgNVHSMEGDAWgBRZ4ZTgDpksHL2qcpkFkxD2
zVd16TAMBggqhkjOPQQDAwUAA2gAMGUCMQD4IoqT2hTUn0jt7oXLdMJ8q4vLp6sg
wHfPiOr9gxreb+e6Oidwd2LDnC4OUqCWiF8CMAzwKs4SnDJYcMLf2vpkbuVE4dTH
Rglz+HGcTLWsFs4KxLsq7MuU+vJTBUeDJeDjdA==
-----END CERTIFICATE-----

-----BEGIN CERTIFICATE-----
MIIDxTCCAq2gAwIBAgIUEMdk6dVgOEIS2cCP0Q43P90Ps5YwDQYJKoZIhvcNAQEF
BQAwajELMAkGA1UEBhMCQ04xEzARBgNVBAoMCmlUcnVzQ2hpbmExHDAaBgNVBAsM
E0NoaW5hIFRydXN0IE5ldHdvcmsxKDAmBgNVBAMMH2lUcnVzQ2hpbmEgQ2xhc3Mg
MiBSb290IENBIC0gRzMwHhcNMTMwNDE4MDkzNjU2WhcNMzMwNDE4MDkzNjU2WjBq
MQswCQYDVQQGEwJDTjETMBEGA1UECgwKaVRydXNDaGluYTEcMBoGA1UECwwTQ2hp
bmEgVHJ1c3QgTmV0d29yazEoMCYGA1UEAwwfaVRydXNDaGluYSBDbGFzcyAyIFJv
b3QgQ0EgLSBHMzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAOPPShpV
nJbMqqCw6Bz1kehnoPst9pkr0V9idOwU2oyS47/HjJXk9Rd5a9xfwkPO88trUpz5
4GmmwspDXjVFu9L0eFaRuH3KMha1Ak01citbF7cQLJlS7XI+tpkTGHEY5pt3EsQg
wykfZl/A1jrnSkspMS997r2Gim54cwz+mTMgDRhZsKK/lbOeBPpWtcFizjXYCqhw
WktvQfZBYi6o4sHCshnOswi4yV1p+LuFcQ2ciYdWvULh1eZhLxHbGXyznYHi0dGN
z+I9H8aXxqAQfHVhbdHNzi77hCxFjOy+hHrGsyzjrd2swVQ2iUWP8BfEQqGLqM1g
KgWKYfcTGdbPB1MCAwEAAaNjMGEwHQYDVR0OBBYEFG/oAMxTVe7y0+408CTAK8hA
uTyRMB8GA1UdIwQYMBaAFG/oAMxTVe7y0+408CTAK8hAuTyRMA8GA1UdEwEB/wQF
MAMBAf8wDgYDVR0PAQH/BAQDAgEGMA0GCSqGSIb3DQEBBQUAA4IBAQBLnUTfW7hp
emMbuUGCk7RBswzOT83bDM6824EkUnf+X0iKS95SUNGeeSWK2o/3ALJo5hi7GZr3
U8eLaWAcYizfO99UXMRBPw5PRR+gXGEronGUugLpxsjuynoLQu8GQAeysSXKbN1I
UugDo9u8igJORYA+5ms0s5sCUySqbQ2R5z/GoceyI9LdxIVa1RjVX8pYOj8JFwtn
DJN3ftSFvNMYwRuILKuqUYSHc2GPYiHVflDh5nDymCMOQFcFG3WsEuB+EYQPFgIU
1DHmdZcz7Llx8UOZXX2JupWCYzK1XhJb+r4hK5ncf/w8qGtYlmyJpxk3hr1TfUJX
Yf4Zr0fJsGuv
-----END CERTIFICATE-----',
            // 支付成功通知地址
            'notify_url'  => cmf_get_domain() . $plugin_config['ali_notify_url'],
            // 网页支付回跳地址
            'return_url'  => cmf_get_domain() . $plugin_config['ali_return_url'],
        ];
        $pay = \AliPay\Transfer::instance($config);

        // 新版资金类接口
        $result = $pay->create([
            'out_biz_no'   => $order_num, // 订单号
            'trans_amount' => $amount, // 转账金额
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene'    => 'DIRECT_TRANSFER',
            'payee_info'   => [
                'identity'      => $identity,
                'identity_type' =>$name,
                'name'          => '电竞',
            ],
        ]);
        dump($result);
        exit;


        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }


    /**
     * 支付宝转账
     * @param        $amount    | 转账金额
     * @param        $identity  | 支付宝账号
     * @param        $name      | 支付宝用户姓名
     * @param string $order_num | 订单号 默认自动获取时间戳
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_transfer(float $amount, $identity, $name, string $order_num = ''): array
    {
        if (empty($order_num)) $order_num = time();
        try {
            $result = Pay::alipay()->transfer([
                'out_biz_no'   => $order_num,
                'trans_amount' => $amount,
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene'    => 'DIRECT_TRANSFER',
                'payee_info'   => [
                    'identity'      => $identity,
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name'          => $name
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }



    /**
     * 微信企业付款到零钱
     * @param        $amount    | 付款金额
     * @param        $openid    | 转账目的用户openID
     * @param        $desc      | 付款订单描述
     * @param string $order_num | 转账订单号,不传会自动生成随机字符串
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_transfer(float $amount, $openid, $desc, string $order_num = ''): array
    {
        try {
            if (empty($order_num)) $order_num = time() . rand(10000, 99999);

            $wechat = new \WeChat\Pay($this->wx_config);

            $amount = $amount * 100;

            $result = $wechat->createTransfers([
                'partner_trade_no' => $order_num,
                'openid'           => $openid,
                'check_name'       => 'NO_CHECK',
                'amount'           => $amount,
                'desc'             => $desc,
                'spbill_create_ip' => $_SERVER['SERVER_ADDR'],
            ]);

        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信支付回调
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_notify(): array
    {
        try {
            $result = Pay::wechat()->callback();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝支付回调
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_notify(): array
    {
        try {
            $result = Pay::alipay()->callback();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信支付回调成功返回参数
     * @return string 返回成功参数
     */
    public function wx_pay_success(): string
    {
        return Pay::wechat()->success()->getBody()->getContents();
    }

    /**
     * 支付宝支付回调成功返回参数
     * @return string 返回成功参数
     */
    public function ali_pay_success(): string
    {
        return Pay::alipay()->success()->getBody()->getContents();
    }
}