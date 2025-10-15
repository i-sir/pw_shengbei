<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"MemberPlay",
 *     "name_underline"   =>"member_play",
 *     "table_name"       =>"member_play",
 *     "model_name"       =>"MemberPlayModel",
 *     "remark"           =>"陪玩管理",
 *     "author"           =>"",
 *     "create_time"      =>"2024-04-23 14:33:08",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\MemberPlayModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberPlayModel extends Model
{

    protected $name = 'member_play';//陪玩管理

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;


    /**
     * 新增余额
     * @param $user_id    用户id
     * @param $balance    金额
     * @param $content    展示内容
     * @param $remark     管理员备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型   20发放佣金  200订单退款 50提现驳回 40用户打赏
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function inc_balance($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel();//陪玩管理


        $member_info = $MemberPlayModel->where('id', '=', $user_id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'     => $user_id,
            'type'        => 1,
            'price'       => $balance,
            'before'      => $member_info['balance'],
            'after'       => $member_info['balance'] + $balance,
            'content'     => $content,
            'remark'      => $remark,
            'order_id'    => $order_id,
            'order_type'  => $order_type,
            'create_time' => time(),
            'order_num'   => $order_num,
        );

        //写入明细
        Db::name('member_balance_play')->strict(false)->insert($log);

        //更新当前金额
        $MemberPlayModel->where('id', $user_id)->inc('balance', $balance)->update();
    }


    /**
     * 减少余额
     * @param $user_id    用户id
     * @param $balance    金额
     * @param $content    展示内容
     * @param $remark     管理备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型 20抵扣订单金额   100后台操作   50陪玩取消订单
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function dec_balance($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel();//陪玩管理


        $member_info = $MemberPlayModel->where('id', '=', $user_id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'     => $user_id,
            'type'        => 2,
            'price'       => $balance,
            'before'      => $member_info['balance'],
            'after'       => $member_info['balance'] - $balance,
            'content'     => $content,
            'remark'      => $remark,
            'order_id'    => $order_id,
            'order_type'  => $order_type,
            'create_time' => time(),
            'order_num'   => $order_num,
        );
        //写入明细
        Db::name('member_balance_play')->strict(false)->insert($log);
        //更新当前金额
        $MemberPlayModel->where('id', $user_id)->dec('balance', $balance)->update();
    }


    /**
     * 新增保证金
     * @param $user_id    用户id
     * @param $balance    金额
     * @param $content    展示内容
     * @param $remark     管理员备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型   90充值保证金
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function inc_point($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel();//陪玩管理


        $member_info = $MemberPlayModel->where('id', '=', $user_id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'     => $user_id,
            'type'        => 1,
            'price'       => $balance,
            'before'      => $member_info['point'],
            'after'       => $member_info['point'] + $balance,
            'content'     => $content,
            'remark'      => $remark,
            'order_id'    => $order_id,
            'order_type'  => $order_type,
            'create_time' => time(),
            'order_num'   => $order_num,
        );

        //写入明细
        Db::name('member_point_play')->strict(false)->insert($log);

        //更新当前金额
        $MemberPlayModel->where('id', $user_id)->inc('point', $balance)->update();
    }


    /**
     * 减少保证金
     * @param $user_id    用户id
     * @param $balance    金额
     * @param $content    展示内容
     * @param $remark     管理备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型 10每日扣除保证金  50取消订单
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function dec_point($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel();//陪玩管理


        $member_info = $MemberPlayModel->where('id', '=', $user_id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'     => $user_id,
            'type'        => 2,
            'price'       => $balance,
            'before'      => $member_info['point'],
            'after'       => $member_info['point'] - $balance,
            'content'     => $content,
            'remark'      => $remark,
            'order_id'    => $order_id,
            'order_type'  => $order_type,
            'create_time' => time(),
            'order_num'   => $order_num,
        );


        //写入明细
        Db::name('member_point_play')->strict(false)->insert($log);
        //更新当前金额
        $MemberPlayModel->where('id', $user_id)->dec('point', $balance)->update();
    }


}

