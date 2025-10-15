<?php

namespace app\admin\controller;

use api\wxapp\controller\PayController;
use think\db\Query;
use think\facade\Db;


class WithdrawalController extends BaseController
{

    public function initialize()
    {
        parent::initialize();

        $this->type_array   = [1 => '支付宝', 2 => '微信'];
        $this->status_array = [1 => '待审核', 2 => '已审核', 3 => '已拒绝'];
        $this->assign('status_list', $this->status_array);


        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);

        }

        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);

    }


    /**
     * 提现记录查询
     */
    public function index()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $MemberInit            = new \init\MemberInit();//用户管理
        $MemberPlayInit        = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberModel           = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        $where[] = ['id', '>', 0];
        $where[] = ['identity_type', '=', 'play'];//陪玩
        if (isset($params['keyword']) && $params['keyword']) $where[] = ['ali_username|ali_account', 'like', "%{$params['keyword']}%"];
        if (isset($params['status']) && $params['status']) $where[] = ['status', '=', $params['status']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        $where[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);


        $list = $MemberWithdrawalModel
            ->where($where)
            ->order("id desc")
            ->paginate(10)
            ->each(function ($item, $key) use ($MemberInit, $MemberPlayInit, $MemberModel) {
                if ($item['create_time']) $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                if ($item['identity_type'] == 'user') {
                    $user_info         = $MemberInit->get_find($item['user_id'], ['field' => '*']);
                    $item['user_info'] = $user_info;
                } else {
                    $user_info         = $MemberPlayInit->get_find($item['user_id'], ['field' => '*']);
                    $item['user_info'] = $user_info;
                }

                $item['type_name']   = $this->type_array[$item['type']];
                $item['status_name'] = $this->status_array[$item['status']];


                //查询对应用户id
                $item['member_info'] = $MemberModel->where('openid', '=', $item['openid'])->find();


                return $item;
            });


        $list->appends($params);

        // 获取分页显示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->assign('identity_type', 'play');

        //陪玩列表
        $play_list = $MemberPlayInit->get_list(['is_block' => 2]);
        $this->assign('member_list', $play_list);


        //申请总金额
        //        $where[]           = ['price', '<', 500];
        $withdrawal_amount = $MemberWithdrawalModel->where($where)->sum('price');
        $this->assign('withdrawal_amount', $withdrawal_amount);


        return $this->fetch();
    }


    /**
     * 用户提现记录查询
     */
    public function user_index()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $MemberInit            = new \init\MemberInit();//用户管理
        $MemberPlayInit        = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberModel           = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        $where[] = ['id', '>', 0];
        $where[] = ['identity_type', '=', 'user'];//用户
        if (isset($params['keyword']) && $params['keyword']) $where[] = ['ali_username|ali_account', 'like', "%{$params['keyword']}%"];
        if (isset($params['status']) && $params['status']) $where[] = ['status', '=', $params['status']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        $where[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);

        $list = $MemberWithdrawalModel
            ->where($where)
            ->order("id desc")
            ->paginate(10)
            ->each(function ($item, $key) use ($MemberInit, $MemberPlayInit, $MemberModel) {
                if ($item['create_time']) $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                if ($item['identity_type'] == 'user') {
                    $user_info         = $MemberInit->get_find($item['user_id'], ['field' => '*']);
                    $item['user_info'] = $user_info;
                } else {
                    $user_info         = $MemberPlayInit->get_find($item['user_id'], ['field' => '*']);
                    $item['user_info'] = $user_info;
                }

                $item['type_name']   = $this->type_array[$item['type']];
                $item['status_name'] = $this->status_array[$item['status']];


                //查询对应用户id
                $item['member_info'] = $MemberModel->where('openid', '=', $item['openid'])->find();


                return $item;
            });


        $list->appends($params);

        // 获取分页显示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->assign('identity_type', 'user');


        //        $MemberModel = new \initmodel\MemberModel();//用户管理
        //        $this->assign('member_list', $MemberModel->select());


        //申请总金额
        //$where[]           = ['price', '<', 500];
        $withdrawal_amount = $MemberWithdrawalModel->where($where)->sum('price');
        $this->assign('withdrawal_amount', $withdrawal_amount);


        return $this->fetch();
    }

    /**
     * 修改状态
     */
    public function update_withdrawal()
    {
        //$this->error("暂不支持微信打款!");
        // 启动事务
         Db::startTrans();

        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $Pay                   = new \plugins\weipay\lib\PayController();

        $params                = $this->request->param();
        $params['update_time'] = time();


        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        //审核通过时间
        if ($params['status'] == 2) $params['pass_time'] = time();
        //审核驳回时间
        if ($params['status'] == 3) $params['refuse_time'] = time();


        $withdrawal_info = $MemberWithdrawalModel->where('id', $params['id'])->find();
        if ($withdrawal_info['status'] != 1) $this->error("已处理不能重复处理!");


        // if ($result) {
        $remark = "操作人[{$admin_id_and_name}];操作说明[提现驳回:{$params['refuse']}];操作类型[管理员驳回提现申请];";//管理备注

        if ($params['status'] == 3) {
            if ($withdrawal_info['identity_type'] == 'user') {
                $MemberModel->inc_balance($withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 50);
            } else {
                $MemberPlayModel->inc_balance($withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 50);
            }
            $result = $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($params);
        } else {
            //支付宝打款
            //$result = $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($params);

            if (!empty($withdrawal_info['ali_username']) && !empty($withdrawal_info['ali_account'])) {

                $res = $Pay->ali_pay_transfer($withdrawal_info['price'], $withdrawal_info['ali_account'], $withdrawal_info['ali_username'], $withdrawal_info['order_num']);
                if ($res['code']) {
                    $ali_result = json_decode($res['data'], true);
                }

                if ($ali_result['code'] == 10000) {
                    //处理成功
                    $result = $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($params);
                } else {
                    $this->error($ali_result['sub_msg']);
                }

            }

        }


        if (empty($result)) $this->error("处理失败!");


        // 提交事务
         Db::commit();

        $this->success("处理成功!");
        // } else {
        //     $this->error("处理失败!");
        // }
    }


    /**
     * 修改状态
     */
    public function update_withdrawal7777()
    {
        // 启动事务
        Db::startTrans();

        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $Pay                   = new \api\wxapp\controller\PayController();

        $params                = $this->request->param();
        $params['update_time'] = time();


        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        //审核通过时间
        if ($params['status'] == 2) $params['pass_time'] = time();
        //审核驳回时间
        if ($params['status'] == 3) $params['refuse_time'] = time();


        $withdrawal_info = $MemberWithdrawalModel->where('id', $params['id'])->find();
        if ($withdrawal_info['status'] != 1) $this->error("已处理不能重复处理!");


        $result = $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($params);


        if ($result) {
            $remark = "操作人[{$admin_id_and_name}];操作说明[提现驳回:{$params['refuse']}];操作类型[管理员驳回提现申请];";//管理备注

            if ($params['status'] == 3) {
                if ($withdrawal_info['identity_type'] == 'user') {
                    $MemberModel->inc_balance($withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 50);
                } else {
                    $MemberPlayModel->inc_balance($withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 50);
                }
            } else {
                //微信打款
                $pay_result = $Pay->transfer_batches($withdrawal_info['openid'], $withdrawal_info['price'], $withdrawal_info['order_num']);
                if (empty($pay_result['code'])) $this->error($pay_result['msg']);

                //更新日志
                $pay_data               = $pay_result['data'];
                $update['batch_id']     = $pay_data['batch_id'];
                $update['batch_status'] = $pay_data['batch_status'];
                $update['pay_result']   = serialize($pay_data);
                $MemberWithdrawalModel->where('id', $params['id'])->strict(false)->update($update);
            }

            // 提交事务
            Db::commit();

            $this->success("处理成功!");
        } else {
            $this->error("处理失败!");
        }
    }


    /**
     * 删除提现记录
     */
    public function delete_withdrawal()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $result                = $MemberWithdrawalModel->where('id', $params['id'])->delete();
        if ($result) {
            $this->success("删除成功!");
        } else {
            $this->error("删除失败!");
        }
    }


    public function refuse()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $id                    = $this->request->param('id');

        $result = $MemberWithdrawalModel->find($id);
        if (empty($result)) {
            $this->error("not found data");
        }
        $toArray = $result->toArray();

        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }
        return $this->fetch();
    }

}