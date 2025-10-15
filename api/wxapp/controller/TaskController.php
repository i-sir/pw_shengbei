<?php

namespace api\wxapp\controller;

/**
 * @ApiMenuRoot(
 *     'name'   =>'Task',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'cogs',
 *     'remark' =>'定时任务'
 * )
 */

use initmodel\MemberPlayModel;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TaskController
{

    /**
     * 执行定时任务
     *
     *   test_environment: http://pw216.ikun/api/wxapp/task/index
     *   official_environment: https://pw216.aejust.net/api/wxapp/task/index
     *   api: /wxapp/task/index
     *   remark_name: 执行定时任务
     *
     */
    public function index()
    {
        $task = new \init\TaskInit();
        //$task->operation_vip();//处理vip
        $task->operation_coupon();//处理优惠券
        $task->operation_cancel_order();//自动取消订单
        $task->operation_accomplish_order();//自动完成订单
        Db::name('play_send')->where('id', '>', 0)->delete();


        echo("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");
        return json("定时任务已执行完毕-------" . date('Y-m-d H:i:s'));
    }


    /**
     * 每周扣除保证金
     * https://pw216.aejust.net/api/wxapp/task/dec_bond
     */
    public function dec_bond()
    {
        //每日扣除保证金
        $daily_deduction_of_margin = cmf_config('daily_deduction_of_margin');


        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $play_list       = $MemberPlayModel->where('id', '<>', 0)->select();


        foreach ($play_list as $key => $play_info) {
            $point = $play_info['point'] - $daily_deduction_of_margin;
            if ($point >= 0) {
                //扣除保证金
                $remark = "操作人[每日扣除保证金];操作说明[每日扣除保证金];操作类型[每日扣除保证金];";//管理备注
                $MemberPlayModel->dec_point($play_info['id'], $daily_deduction_of_margin, '每周扣除', $remark, 0, cmf_order_sn(), 10);
            }
        }


        echo("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");
        return json("定时任务已执行完毕-------" . date('Y-m-d H:i:s'));

    }

    //通知陪玩 & 余额支付
    //https://pw216.aejust.net/api/wxapp/task/send
    public function send()
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $SendTempMsgController = new SendTempMsgController();
        $MemberPlayModel       = new MemberPlayModel(); //陪玩管理  (ps:InitModel)


        $map   = [];
        $map[] = ['is_send', '=', 0];
        $map[] = ['status', '=', 20];

        //查出套餐
        $order_list = $PlayPackageOrderModel->where($map)->field('nickname,name,area,amount,id,order_num')->select();

        //修改已发送状态
        $PlayPackageOrderModel->where($map)->update(['is_send' => 1, 'update_time' => time()]);

        foreach ($order_list as $key => $order_info) {
            //查出套餐对应陪玩
            $map100    = [];
            $map100[]  = ['area', 'like', "%{$order_info['area']}%"];
            $user_list = $MemberPlayModel->where($map100)->column('wx_openid,phone');
            foreach ($user_list as $key2 => $user_info) {
                $send_data = [
                    'thing3'   => ['value' => $order_info['nickname']],
                    'thing5'   => ['value' => $order_info['name']],
                    'amount11' => ['value' => $order_info['amount']],
                    'time2'    => ['value' => date('Y-m-d H:i:s')],
                ];

                //域名
                $url = cmf_get_domain() . "/h5/#/pages/order/pOrderDetail?order_num={$order_info['order_num']}&is_receiving=1";
                if ($user_info['wx_openid']) $SendTempMsgController->sendTempMsg($user_info['wx_openid'], '9fs7aMnl0rEdskNPlmscirG1YOzQsnTWd63He-QHnfU', $send_data, '', 1, $url);
            }
        }


        return json("公众号通知已发送-------" . date('Y-m-d H:i:s'));
    }

}