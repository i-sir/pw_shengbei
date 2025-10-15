<?php
// +----------------------------------------------------------------------
// | 提现相关接口
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;


use think\facade\Db;

error_reporting(0);

/**
 * 1.数据库
 * 2.创建model
 * 3.后台管理
 * 4.配置管理提现金额等    提现管理最低金额   cmf_config('withdraw_amount')   提现扣除金额    cmf_config('withdraw_charges')
 */
class WithdrawalController extends AuthController
{
    public function initialize()
    {
        parent::initialize();//初始化方法

        $this->type_array   = [1 => '支付宝', 2 => '微信'];
        $this->status_array = [1 => '待审核', 2 => '已审核', 3 => '已拒绝'];
    }

    /**
     * 提现记录查询
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"小程序端提现模块"},
     *     path="/wxapp/withdrawal/find_withdrawal_list",
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/withdrawal/find_withdrawal_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/withdrawal/find_withdrawal_list
     *   api: /wxapp/withdrawal/find_withdrawal_list
     *   remark_name: 提现记录查询
     *
     */
    public function find_withdrawal_list()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理


        $this->checkAuth();
        $params = $this->request->param();

        $result = $MemberWithdrawalModel
            ->where('user_id', $this->user_id)
            ->order('id desc')
            ->paginate($params['page_size'])
            ->each(function ($item, $key) {

                $item['status_name'] = $this->status_array[$item['status']];
                $item['type_name']   = $this->type_array[$item['type']];

                return $item;
            });

        $this->success('请求成功！', $result);
    }


    /**
     * 提交提现申请
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"小程序端提现模块"},
     *     path="/wxapp/withdrawal/add_withdrawal",
     *
     *
     *
     *     @OA\Parameter(
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
     *         name="type",
     *         in="query",
     *         description="1支付宝 2微信 (不传默认为1)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="price",
     *         in="query",
     *         description="提现金额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *   @OA\Parameter(
     *         name="ali_username",
     *         in="query",
     *         description="支付宝账号名字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="ali_account",
     *         in="query",
     *         description="支付宝账号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Parameter(
     *         name="ali_image",
     *         in="query",
     *         description="ali_image",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/withdrawal/add_withdrawal
     *   official_environment: https://pw216.aejust.net/api/wxapp/withdrawal/add_withdrawal
     *   api: /wxapp/withdrawal/add_withdrawal
     *   remark_name: 提交提现申请
     *
     */
    public function add_withdrawal()
    {
        $this->checkAuth();
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理


        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $MemberModel     = new \initmodel\MemberModel();//用户管理


        // 启动事务
        Db::startTrans();

        $params                  = $this->request->param();
        $params['identity_type'] = $this->user_info['identity_type'];

        if ($params['identity_type'] == 'user') $openid = $this->openid;
        if ($params['identity_type'] == 'play') {
            $openid = $this->user_info['wx_openid'];
            if ($params['price'] < 99) $this->error('最低提现100元!');
        }

        if (empty($params['type'])) $params['type'] = 1;
        if (empty($params['price'])) $this->error('请填写正确的金额!');
        if (empty($openid)) $this->error('请重新登录账号!');

        //先扣除指定金额
        if ($params['price'] > $this->user_info['balance']) $this->error('提现金额超出了可提现金额！');
        if ($params['price'] < 1) $this->error('最低提现1元!');
        if ($params['price'] >= 500) $this->error('提现金额不能超过500,请分批次提现!');

        //计算手续费
        //$withdraw_amount = cmf_config('withdraw_amount');
        //if ($withdraw_amount > $params['price']) $this->error('提现金额不能低于' . $withdraw_amount . '元！');


        $charges = 0;//手续费
        // $withdraw_charges = cmf_config('withdraw_charges') / 100;
        // if ($withdraw_charges) {
        //     $charges = $params['price'] * $withdraw_charges;
        //     $charges = round($charges, 2);
        // }


        $recharge = array(
            'identity_type' => $params['identity_type'],
            'type'          => $params['type'],
            'price'         => $params['price'],
            'user_id'       => $this->user_id,
            'openid'        => $openid,
            'charges'       => $charges,
            'create_time'   => time(),
            'ali_username'  => $params['ali_username'],
            'ali_account'   => $params['ali_account'],
            'ali_image'     => cmf_get_asset_url($params['ali_image']),
            'order_num'     => cmf_order_sn(6),
            'status'        => 1,
        );

        $result = $MemberWithdrawalModel->strict(false)->insert($recharge, true);


        if ($result) {
            $remark = "操作人[{$this->user_id}-{$this->user_info['nickname']}];操作说明[申请提现:{$params['price']}];操作类型[用户申请提现];";//管理备注

            if ($params['identity_type'] == 'user') {
                $MemberModel->dec_balance($this->user_id, $recharge['price'], '用户提现', $remark, 0, $recharge['order_num']);
            } else {
                $MemberPlayModel->dec_balance($this->user_id, $recharge['price'], '用户提现', $remark, 0, $recharge['order_num']);
            }
            // 提交事务
            Db::commit();

            $this->success('提交成功!');
        } else {
            $this->error('提交失败，请稍后再试！');
        }
    }


    /**
     * 获取总提现金额
     */
    public function withdrawal_total()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理

        //获取提现总金额
        $map2                  = [];
        $map2[]                = ['user_id', '=', $this->user_id];
        $map2[]                = ['status', 'in', [1, 2]];
        $withdrawal_pass_total = $MemberWithdrawalModel->where($map2)->sum('price');

        $map3                    = [];
        $map3[]                  = ['user_id', '=', $this->user_id];
        $map3[]                  = ['status', '=', 3];
        $withdrawal_refuse_total = $MemberWithdrawalModel->where($map3)->sum('price');

        $user['withdrawal_total'] = round($withdrawal_pass_total - $withdrawal_refuse_total, 2);
    }

}
