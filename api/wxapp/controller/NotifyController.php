<?php
/**
 * Created by PhpStorm.
 * User: zhang
 * Date: 2021/12/17
 * Time: 11:08
 */

namespace api\wxapp\controller;

use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;

class NotifyController extends AuthController
{


    public function initialize()
    {
        parent::initialize();//初始化方法

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
    }


    /**
     * 微信支付回调-微信回调用
     * api: /wxapp/notify/wxPayNotify
     */
    public function wxPayNotify()
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $wechat = new \WeChat\Pay($this->wx_config);
        // 4. 获取通知参数
        $result = $wechat->getNotify();
        Log::write($result, '微信回调用-wx_pay_notify');


        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            Log::write("微信回调用-wx_pay_notify:支付成功,修改订单状态;支付单号[{$result['out_trade_no']}]");
            //Log::write($result);

            $pay_num        = $result['out_trade_no'];
            $pay_amount     = round($result['total_fee'] / 100, 2);//支付金额(元)
            $transaction_id = $result['transaction_id'];


            /** 处理订单状态等操作 **/
            $this->processOrder($pay_num, $result);//微信官方支付回调


            // 返回接收成功的回复
            ob_clean();
            echo $wechat->getNotifySuccessReply();

        } else {
            Log::write('event_type:' . $result);
        }
    }


    /**
     * 支付宝回调
     * /api/wxapp/notify/aliPayNotify
     */
    public function aliPayNotify()
    {
        $Pay           = new PayController();
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $pay_data = $Pay->ali_pay_notify();
        $result   = $pay_data['data'];//支付参数
        Log::write('aliPayNotify:result');
        Log::write($result);


        if (in_array($result['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            $pay_num        = $result['out_trade_no'];
            $transaction_id = $result['trade_no'];


            /** 处理订单状态等操作 **/
            $this->processOrder($pay_num, $result, 3);//支付宝回调


            /** 更改支付记录,状态 */
            $result['time']          = time();
            $pay_update['pay_time']  = time();
            $pay_update['trade_num'] = $transaction_id ?? '9880' . cmf_order_sn(8);
            $pay_update['status']    = 2;
            $pay_update['notify']    = serialize($result);
            $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


            ob_clean();
            return $Pay->ali_pay_success();

        } else {
            Log::write('trade_status:' . $result['trade_status']);
        }
    }


    /**
     *
     * 微信支付回调 测试
     *
     *   test_environment: http://pw216.ikun/api/wxapp/notify/wx_pay_notify_test?pay_num=1000
     *   official_environment: https://pw216.aejust.net/api/wxapp/notify/wx_pay_notify_test?pay_num=1000
     *   api:   /wxapp/notify/wx_pay_notify_test?pay_num=31024062839464198773326
     */
    public function wx_pay_notify_test()
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $params  = $this->request->param();
        $pay_num = $params['pay_num'];


        /** 处理订单状态等操作 **/
        $pay_result = $this->processOrder($pay_num, []);//测试回调


        $this->success('操作成功', $pay_result['order_info']);
    }


    /**
     * 支付成功回调
     * @param $pay_num         支付单号
     * @param $PaymentResults  支付成功参数
     * @param $pay_type        1微信支付,2余额支付,3支付宝
     */
    public function processOrder($pay_num, $PaymentResults = [], $pay_type = 1)
    {
        $OrderPayModel              = new \initmodel\OrderPayModel();//支付记录表
        $PlayPackageOrderModel      = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayUserOrderModel         = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderController = new PlayPackageOrderController();//下单管理
        $PlayRewardLogModel         = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $MemberPlayModel            = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $SendTempMsgController      = new SendTempMsgController();
        $MemberPointOrderModel      = new \initmodel\MemberPointOrderModel(); //充值保证金   (ps:InitModel)


        //查询出支付信息,如果已支付,则不再处理
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
        if ($pay_info['status'] == 2) return false;

        /** 查询出支付信息,以及关联的订单号 */
        $order_pay = $OrderPayModel->where('pay_num', $pay_num)->find();
        Log::write('PayNotify:order_pay_info');
        Log::write($order_pay);

        //订单号
        $order_num = $order_pay['order_num'];

        /**  查询订单条件  */
        $map   = [];
        $map[] = ['order_num', '=', $order_num];


        /** 更新订单 默认字段  */
        $update['update_time'] = time();
        $update['pay_time']    = time();
        $update['status']      = 2;//已支付

        //陪玩单
        if ($order_pay['order_type'] == 10) {
            $order = $PlayPackageOrderModel->where($map)->find();//查询订单信息
            if ($order['status'] != 1) {
                Log::write('PayNotify:订单状态异常,订单号:' . $order_num);
                return false;
            }

            //更改已支付状态
            $update2['is_pay'] = 2;
            $PlayUserOrderModel->where($map)->strict(false)->update($update2);//更新订单信息

            //不用审核直接进入大厅
            $update['status'] = 20;
            //陪玩订单
            if ($order['play_user_id']) $update['status'] = 30;//指定陪玩直接待服务

            //处理如果再来一单,人数满了直接改为待服务状态
            if ($order['is_encore'] == 2) {
                if ($order['order_type'] == 1) $update['status'] = 30;
                if ($order['order_type'] == 2) {
                    $play_number = $order['play_number'];//单子,人数
                    $map10       = [];
                    $map10[]     = ['order_num', '=', $order_num];
                    $map10[]     = ['status', '=', 30];
                    $total_user  = $PlayUserOrderModel->where($map10)->count();
                    if ($total_user >= $play_number) $update['status'] = 30;
                }
            }


            //如果是抽奖订单,开始抽奖
            if ($order['package_id'] == 1) {
                $DrawController       = new DrawController();
                $draw_info            = $DrawController->draw();
                $update['draw_id']    = $draw_info['id'];
                $update['draw_name']  = $draw_info['name'];
                $update['draw_image'] = $draw_info['image'];
            }

            $result = $PlayPackageOrderModel->where($map)->strict(false)->update($update);//更新订单信息


            Log::write('PayNotify:order_info');
            Log::write($order);


            //发送模板消息
            $order = $PlayPackageOrderModel->where($map)->find();//查询订单信息 拿到最新的

            //if ($order['play_type'] == 1) $PlayPackageOrderController->send_msg($order); //定时任务通知
            //$PlayPackageOrderController->send_msg($order);


            //下单指定陪玩,通知对方
            if ($order['play_user_id']) {
                $send_data = [
                    'thing20'           => ['value' => $order['nickname']],
                    'character_string1' => ['value' => $order['order_num']],
                    'thing19'           => ['value' => $order['name']],
                    'amount4'           => ['value' => $order['amount']],
                    'time5'             => ['value' => date('Y-m-d H:i:s')],
                ];

                $map40   = [];
                $map40[] = ['id', '=', $order['play_user_id']];
                $openid  = $MemberPlayModel->where($map40)->value('wx_openid');

                //域名
                $url = cmf_get_domain() . "/h5/#/pages/order/pOrderDetail?order_num={$order_num}&is_receiving=1";
                if ($openid) $SendTempMsgController->sendTempMsg($openid, 'WUtxLcep8pFkTk_v7029_DS_JYnAcARXtXRUFaP_730', $send_data, '', 1, $url);

                //陪玩接单
                $this->receive_order($order['play_user_id'], $order['id']);
            }

        }


        //打赏
        if ($order_pay['order_type'] == 20) {
            $order  = $PlayRewardLogModel->where($map)->find();
            $result = $PlayRewardLogModel->where($map)->update($update);

            //给陪玩增加余额
            if ($order['is_send'] == 1) {
                $remark = "操作人[打赏完成];操作说明[订单已完成-{$order['order_num']}];操作类型[用户打赏陪玩];";//管理备注
                if ($order['commission']) $MemberPlayModel->inc_balance($order['play_user_id'], $order['commission'], '用户打赏', $remark, $order['id'], $order['order_num'], 40);
            }
        }

        //缴纳保证金
        if ($order_pay['order_type'] == 90) {
            $order  = $MemberPointOrderModel->where($map)->find();
            $result = $MemberPointOrderModel->where($map)->update($update);

            //给陪玩增加保证金
            $remark = "操作人[增加保证金];操作说明[增加保证金-{$order['order_num']}];操作类型[增加保证金];";//管理备注
            $MemberPlayModel->inc_point($order['user_id'], $order['point'], '充值保证金', $remark, $order['id'], $order['order_num'], 90);
        }


        /** 更改支付记录,状态 */
        $transaction_id = $PaymentResults['transaction_id'];

        $PaymentResults['time']  = time();
        $pay_update['pay_time']  = time();
        $pay_update['trade_num'] = $transaction_id ?? '9880' . cmf_order_sn(8);
        $pay_update['status']    = 2;
        $pay_update['notify']    = serialize($PaymentResults);
        $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);

        Log::write('PayNotify:pay_update');
        Log::write($pay_update);


        return ['result' => $result, 'order_info' => $order];
    }


    /**
     * 派单接单
     * @param int $play_user_id 陪玩id
     * @param int $id           订单id
     */
    public function receive_order($play_user_id = 0, $id = 0)
    {
        $PlayPackageOrderInit = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderInit    = new \init\PlayUserOrderInit();//陪玩管理   (ps:InitController)

        //参数
        $params       = [];
        $params['id'] = $id;
        //$user_info = $MemberPlayModel->where('id', $play_user_id)->find();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $id];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) Log::write('订单不存在!');


        //添加到陪玩记录
        $insert['user_id']   = $play_user_id;
        $insert['order_num'] = $order_info['order_num'];
        $insert['status']    = 30;//默认未取消
        $PlayUserOrderInit->api_edit_post($insert);


        //直接将订单状态改下
        $params['status']   = 30;
        $params['is_begin'] = 1;//可以开始服务

        //陪玩信息
        $params['play_user_id']        = $play_user_id;
        $params['where_play_user_ids'] = "/{$play_user_id}/";


        //更改订单信息
        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) Log::write('失败请重试!');

    }


}