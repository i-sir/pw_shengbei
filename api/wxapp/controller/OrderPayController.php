<?php

namespace api\wxapp\controller;

use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;

class OrderPayController extends AuthController
{

    public function initialize()
    {
        parent::initialize();//初始化方法

    }


    /**
     * 微信公众号支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/wx_pay_mp",
     *
     *
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     * 	   @OA\Parameter(
     *         name="pay_order_type",
     *         in="query",
     *         description="10陪玩单,20打赏,90支付保证金",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     * 	   @OA\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="1微信  2余额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/order_pay/wx_pay_mp
     *   official_environment: https://pw216.aejust.net/api/wxapp/order_pay/wx_pay_mp?pay_order_type=20&pay_type=1&order_num=250106455482029166&openid=olS7D6iUYhg3hq6EjLPw8yod052g
     *   api: /wxapp/order_pay/wx_pay_mp
     *   remark_name: 微信公众号支付
     *
     */
    public function wx_pay_mp()
    {
        // 启动事务
        Db::startTrans();


        $this->checkAuth();


        $params = $this->request->param();
        $openid = $this->openid;


        $Pay                   = new PayController();
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayRewardLogModel    = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $OrderPayModel         = new \initmodel\OrderPayModel();
        $MemberModel           = new \initmodel\MemberModel();
        $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金   (ps:InitModel)


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //套餐&陪玩
        if (empty($params['pay_order_type'])) {
            $params['pay_order_type'] = 10;
            $order_info               = $PlayPackageOrderModel->where($map)->find();
            if (empty($order_info)) $this->error('订单不存在');
            $PlayPackageOrderModel->where($map)->strict(false)->update([
                'pay_type'     => $params['pay_type'],
                'updatee_time' => time(),
            ]);
        }

        //打赏
        if ($params['pay_order_type'] == 20) {
            $order_info = $PlayRewardLogModel->where($map)->find();
            if (empty($order_info)) $this->error('订单不存在');
            $PlayRewardLogModel->where($map)->strict(false)->update([
                'pay_type'     => $params['pay_type'],
                'updatee_time' => time(),
            ]);
        }


        //支付保证金
        if ($params['pay_order_type'] == 90) {
            $order_info = $MemberPointOrderModel->where($map)->find();
            if (empty($order_info)) $this->error('订单不存在');
            $MemberPointOrderModel->where($map)->strict(false)->update([
                'pay_type'     => $params['pay_type'],
                'updatee_time' => time(),
            ]);
            $openid    = $this->user_info['wx_openid'];
        }


        //微信支付
        if ($params['pay_type'] == 1) {
            $order_num = $order_info['order_num'];
            $amount    = $order_info['amount'];

            //支付记录插入一条记录
            $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['pay_order_type'], 1, $order_info['id']);
            $result  = $Pay->wx_pay_mp($pay_num, $amount, $openid);
            if ($result['code'] != 1) {
                if (strstr($result['msg'], '此商家的收款功能已被限制')) $this->error('支付失败,请联系客服!错误码:pay_limit');
                $this->error($result['msg']);
            }

            // 提交事务
            Db::commit();

            $this->success('请求成功', $result['data']);
        }


        //余额支付
        if ($params['pay_type'] == 2) {
            $order_num = $order_info['order_num'];
            $amount    = $order_info['amount'];

            //检测用户余额是否充足
            if ($this->user_info['balance'] < $amount) $this->error('余额不足!');


            //支付记录插入一条记录
            $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['pay_order_type'], 2, $order_info['id']);


            //扣除余额
            $remark = "操作人[{$this->user_id}-{$this->user_info['nickname']}];操作说明[余额支付订单:{$amount}-{$order_num}];操作类型[用户支付订单];";//管理备注
            $MemberModel->dec_balance($this->user_id, $amount, '支付订单', $remark, $order_info['id'], $order_info['order_num'], 20);


            //支付回调
            $NotifyController = new NotifyController();
            $NotifyController->processOrder($pay_num, [], $params['pay_type']);


            // 提交事务
            Db::commit();


            $this->success('支付成功!');
        }

    }


    //https://pw216.aejust.net/api/wxapp/order_pay/wx_pay_refund_test?order_num=250610206819667730
    public function wx_pay_refund_test()
    {
        $params = $this->request->param();
        //给用户退款
        $Pay           = new PayController();
        $OrderPayModel = new \initmodel\OrderPayModel();

        $map            = [];
        $map[]          = ['order_num', '=', $params['order_num']];//实际订单号
        $map[]          = ['status', '=', 2];//已支付
        $pay_info       = $OrderPayModel->where($map)->find();//支付记录表
        $amount         = $pay_info['amount'];//支付金额&全部退款
        $order_num      = $pay_info['pay_num'];//支付单号
        $transaction_id = $pay_info['trade_num'];//第三方单号


        $refund_result = $Pay->wx_pay_refund($transaction_id, $order_num, $amount);
        $refund_result = $refund_result['data'];
        if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);


        $this->success('请求成功', $refund_result);
    }


    /**
     * 支付宝h5支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/ali_pay_wap",
     *
     *
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="10交停车费",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="支付类型:1微信支付,2余额支付,3积分支付,4支付宝支付",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/order_pay/ali_pay_wap
     *   official_environment: https://pw216.aejust.net/api/wxapp/order_pay/ali_pay_wap
     *   api: /wxapp/order_pay/ali_pay_wap
     *   remark_name: 支付宝h5支付
     *
     */
    public function ali_pay_wap()
    {
        $Pay                   = new PayController();
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayRewardLogModel    = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $OrderPayModel         = new \initmodel\OrderPayModel();
        $MemberModel           = new \initmodel\MemberModel();


        $params = $this->request->param();
        $openid = $this->openid;


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //套餐&陪玩
        if (empty($params['pay_order_type'])) {
            $params['pay_order_type'] = 10;
            $order_info               = $PlayPackageOrderModel->where($map)->find();
            if (empty($order_info)) $this->error('订单不存在');
            $PlayPackageOrderModel->where($map)->strict(false)->update([
                'pay_type'     => 3,
                'updatee_time' => time(),
            ]);
        }

        //打赏
        if ($params['pay_order_type'] == 20) {
            $order_info = $PlayRewardLogModel->where($map)->find();
            if (empty($order_info)) $this->error('订单不存在');
            $PlayRewardLogModel->where($map)->strict(false)->update([
                'pay_type'     => 3,
                'updatee_time' => time(),
            ]);
        }


        //支付宝支付
        $order_num = $order_info['order_num'] ?? cmf_order_sn();
        $amount    = $order_info['amount'] ?? 0.01;
        //$amount    =   0.01;


        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['pay_order_type'], 3, $order_info['id']);


        $res = $Pay->ali_pay_wap($pay_num, $amount);
        if ($res['code'] != 1) $this->error($res['msg']);
        exit($res['data']);
        $this->success('请求成功', $res);
    }


    /**
     * 支付宝转账
     * @return void
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/order_pay/ali_transfer_accounts
     *   official_environment: https://pw216.aejust.net/api/wxapp/order_pay/ali_transfer_accounts
     *   api: /wxapp/order_pay/ali_transfer_accounts
     *   remark_name: 支付宝转账
     *
     */
    public function ali_transfer_accounts()
    {
        $Pay = new PayController();

        $amount    = '0.1';//最少一毛钱
        $identity  = '185****163';
        $name      = '**';
        $order_num = cmf_order_sn(4);
        $result    = $Pay->ali_pay_transfer($amount, $identity, $name, $order_num);


        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


}