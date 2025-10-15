<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"Member",
 *     "table_name"       =>"member",
 *     "model_name"       =>"MemberModel",
 *     "remark"           =>"测试生成crud",
 *     "author"           =>"",
 *     "create_time"      =>"2023-06-14 15:16:47",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\MemberModel();
 * )
 */

use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class MemberModel extends Model
{
    protected $name = 'member';//用户信息

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
     * @param $order_type 订单类型   100后台操作  200订单退款 10邀请奖励  50提现驳回  60租号
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function inc_balance($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $member_info = $MemberModel->where('id', '=', $user_id)->find();
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
        Db::name('member_balance')->strict(false)->insert($log);
        //更新当前金额
        $MemberModel->where('id', $user_id)->inc('balance', $balance)->update();
    }


    /**
     * 减少余额
     * @param $user_id    用户id
     * @param $balance    金额
     * @param $content    展示内容
     * @param $remark     管理备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型 20抵扣订单金额   100后台操作
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function dec_balance($user_id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $member_info = $MemberModel->where('id', '=', $user_id)->find();
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
        Db::name('member_balance')->strict(false)->insert($log);
        //更新当前金额
        $MemberModel->where('id', $user_id)->dec('balance', $balance)->update();
    }


    /**
     * 新增积分
     * @param $user_id    用户id
     * @param $point      积分
     * @param $content    展示内容
     * @param $remark     管理员备注
     * @param $order_id   订单id
     * @param $order_num  订单单号
     * @param $order_type 订单类型  100后台操作  30转赠积分
     * @return void
     */
    public function inc_point($user_id, $point, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $member_info = $MemberModel->where('id', '=', $user_id)->find();
        if ($point <= 0) return;

        $log = array(
            'user_id'     => $user_id,
            'type'        => 1,
            'price'       => $point,
            'before'      => $member_info['point'],
            'after'       => $member_info['point'] + $point,
            'content'     => $content,
            'remark'      => $remark,
            'order_type'  => $order_type,
            'order_id'    => $order_id,
            'create_time' => time(),
            'order_num'   => $order_num,
        );
        //写入明细
        Db::name('member_point')->strict(false)->insert($log);
        //更新当前积分
        $MemberModel->where('id', $user_id)->inc('point', $point)->update();
    }


    /**
     * 减少积分
     * @param $user_id        用户id
     * @param $point          积分
     * @param $content        展示内容
     * @param $remark         管理员备注
     * @param $order_id       订单id
     * @param $order_num      订单单号
     * @param $order_type     订单类型 100后台操作  10兑换商品  30转赠积分
     * @param $service_charge 手续费 0
     * @return void
     */
    public function dec_point($user_id, $point, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0, $service_charge = 0)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        $member_info = $MemberModel->where('id', '=', $user_id)->find();
        if ($point <= 0) return;

        $log = array(
            'user_id'        => $user_id,
            'type'           => 2,
            'price'          => $point,
            'before'         => $member_info['point'],
            'after'          => $member_info['point'] - $point,
            'content'        => $content,
            'remark'         => $remark,
            'order_type'     => $order_type,
            'order_id'       => $order_id,
            'create_time'    => time(),
            'order_num'      => $order_num,
            'service_charge' => $service_charge,
        );
        //写入明细
        Db::name('member_point')->strict(false)->insert($log);
        //更新当前积分
        $MemberModel->where('id', $user_id)->dec('point', $point)->update();
    }


}