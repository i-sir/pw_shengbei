<?php

namespace init;

use api\wxapp\controller\PlayOrderController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 定时任务
 */
class TaskInit
{


    /**
     * 更新vip状态
     */
    public function operation_vip()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        //操作vip   vip_time vip到期时间
        //$MemberModel->where('vip_time', '<', time())->update(['is_vip' => 0]);
        echo("更新vip状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 处理优惠券
     */
    public function operation_coupon()
    {
        //平台优惠状态
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        $map   = [];
        $map[] = ['status', '=', 2];
        $map[] = ['start_time', '<', time()];
        $ShopCouponModel->where($map)->update(['status' => 1]);

        $map2   = [];
        $map2[] = ['end_time', '<', time()];
        $ShopCouponModel->where($map2)->update(['status' => 2]);


        //用户优惠券状态
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录  (ps:InitModel)
        $map3                = [];
        $map3[]              = ['end_time', '<', time()];
        $map3[]              = ['used', '=', 1];
        $ShopCouponUserModel->where($map3)->update(['status' => 2, 'used' => 3]);


        echo("更新优惠券状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    //自动取消订单
    public function operation_cancel_order()
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $ShopCouponUserInit    = new \init\ShopCouponUserInit();//优惠券领取记录   (ps:InitController)

        $map        = [];
        $map[]      = ['status', '=', 1];
        $map[]      = ['auto_cancel_time', '<', time()];
        $order_list = $PlayPackageOrderModel->where($map)->select();
        foreach ($order_list as $k => $v) {
            $PlayPackageOrderModel->where('id', '=', $v['id'])->update([
                'status'      => 10,
                'cancel_time' => time(),
                'update_time' => time(),
            ]);


            //取消订单,返回优惠券
            if ($v['coupon_id']) $ShopCouponUserInit->use_coupon($v['coupon_id']);

        }

        echo("自动取消订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 自动完成订单
     */
    public function operation_accomplish_order()
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $PlayOrderController   = new PlayOrderController();//陪玩端订单管理

        $map        = [];
        $map[]      = ['status', '=', 60];
        $map[]      = ['auto_accomplish_time', '<', time()];
        $order_list = $PlayPackageOrderModel->where($map)->select();
        foreach ($order_list as $k => $order_info) {

            //发送佣金
            if ($order_info['is_commission'] == 2) $PlayOrderController->send_commission($order_info['order_num']);//后台完成订单,如果没有发放佣金进行发放


            $PlayPackageOrderModel->where('id', '=', $order_info['id'])->update([
                'status'      => 50,//给陪玩发送佣金
                'cancel_time' => time(),
                'update_time' => time(),
            ]);

        }

        echo("自动完成订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


}