<?php

namespace initmodel;

use think\Model;

class OrderPayModel extends Model
{
    protected $name  = 'order_pay'; //支付管理
    public    $field = '*';


    /**
     * 支付信息
     * @param $openid     身份标识openid
     * @param $order_num
     * @param $amount
     * @param $order_type 1套餐,2陪玩
     * @param $pay_type   支付类型:1微信支付,2余额支付,3支付宝支付
     * @param $new        是否使用新支付单号 0否 1是
     * @param $is_pay     是否已支付订单 0否 1是
     * @return void
     */
    public function add($openid = 0, $order_num = 0, $amount = 0.01, $order_type = 1, $pay_type = 1, $order_id = 0, $new = 1, $is_pay = 0)
    {
        $map   = [];
        $map[] = ['order_num', '=', $order_num];
        if ($is_pay) $map[] = ['status', '=', 2];//已支付

        if ($new == 0) {
            $pay_num = (new self())->where($map)->order('id desc')->value('pay_num');
            return $pay_num;
        }

        $prefix_num = '5550';//微信支付单号
        if ($pay_type == 2) $prefix_num = '6660';//余额支付单号
        if ($pay_type == 3) $prefix_num = '7770';//支付宝支付单号

        $log = [
            'openid'      => $openid,
            'order_num'   => $order_num,
            'order_id'    => $order_id,
            'order_type'  => $order_type,
            'pay_type'    => $pay_type,
            'amount'      => $amount,
            'pay_num'     => $prefix_num . cmf_order_sn(4),
            'create_time' => time(),
        ];

        (new self())->strict(false)->insert($log);

        return $log['pay_num'];
    }

}