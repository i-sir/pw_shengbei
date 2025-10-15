<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"PlayPackageOrder",
 *     "name_underline"      =>"play_package_order",
 *     "controller_name"     =>"PlayPackageOrder",
 *     "table_name"          =>"play_package_order",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"套餐订单",
 *     "author"              =>"",
 *     "create_time"         =>"2024-04-15 14:19:38",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\PlayPackageOrderController();
 * )
 */


use api\wxapp\controller\PlayOrderController;
use init\PlayPackageOrderInit;
use init\ShopCouponUserInit;
use plugins\weipay\lib\PayController;
use think\App;
use think\facade\Db;
use think\facade\Log;


class PlayPackageOrderController extends BaseController
{


    public function initialize()
    {
        //套餐订单

        parent::initialize();

        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);

        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $this->assign('play_list', $MemberPlayModel->where('is_block', '=', 2)->select());


        //获取一级分类
        $PlayClassInit = new \init\PlayClassInit();//分类管理    (ps:InitController)
        $map           = [];
        $map[]         = ['pid', '=', 0];
        $this->assign('class_list', $PlayClassInit->get_list($map));
    }


    //获取二级分类列表
    public function get_class_list()
    {
        $params = $this->request->param();
        //分类
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayClassInit         = new \init\PlayClassInit();//分类管理    (ps:InitController)

        //查询二级条件.
        $map = [];
        if ($params['one_id']) $map[] = ['pid', '=', $params['one_id']];//查询二级列表
        if (empty(count($map))) $this->success('请求成功');

        //二级列表
        $two_list = $PlayClassInit->get_list($map);
        //查询数据,方便展示二级下拉框选中值
        if ($params['item_id']) {
            $item_info = $PlayPackageOrderModel->where(['id' => $params['item_id']])->find();
            if ($item_info) {
                foreach ($two_list as $k => &$v) {
                    $v['selected'] = false;
                    //这里的branch_id 更改为二级,三级id
                    if ($params['one_id'] && $item_info['class_two_id'] == $v['id']) $v['selected'] = 'selected';//选中
                }
            }
        }
        $this->success('请求成功', '', $two_list);
    }


    /**
     * 获取二分类,回显使用
     */
    public function get_class_two_list()
    {
        $PlayClassInit = new \init\PlayClassInit();//分类管理    (ps:InitController)


        $params = $this->request->param();
        $map    = [];
        $map[]  = ['pid', '=', $params['one_id']];
        $result = $PlayClassInit->get_list($map);

        foreach ($result as $k => &$v) {
            if ($v['id'] == $params['two_id']) $v['selected'] = 'selected';
        }

        $this->success('成功', '', $result);
    }

    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'PlayPackageOrder',
     *     'name_underline'   => 'play_package_order',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '套餐订单',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();


        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["order_num|package_id|map|paly_name|code|area|wx|remark", "like", "%{$params["keyword"]}%"];
        if ($params["order_num"]) $where[] = ["order_num", "like", "%{$params["order_num"]}%"];
        if ($params["goods_name"]) $where[] = ["name", "like", "%{$params["goods_name"]}%"];
        if ($params["sku_name"]) $where[] = ["sku_name", "like", "%{$params["sku_name"]}%"];
        if ($params["play_user_id"]) $where[] = ["where_play_user_ids", "like", "%/{$params['play_user_id']}/%"];
        if ($params['pay_type']) $where[] = ['pay_type', '=', $params['pay_type']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        if ($params['class_id']) $where[] = ['class_id', '=', $params['class_id']];
        if ($params['class_two_id']) $where[] = ['class_two_id', '=', $params['class_two_id']];
        if ($params['order_date']) {
            $order_date_arr = explode(' - ', $params['order_date']);
            $where[]        = $this->getBetweenTime($order_date_arr[0], $order_date_arr[1]);
        }


        $where_status = [];//状态统计
        if ($params["status"]) $where_status[] = ["status", "in", $PlayPackageOrderInit->status_admin_where[$params["status"]]];


        $params["InterfaceType"] = "admin";//接口类型

        //导出数据
        if ($params["is_export"]) $this->export_excel(array_merge($where_status, $where), $params);

        //查询数据
        //dump(array_merge($where_status, $where),$params);exit();
        $result = $PlayPackageOrderInit->get_list_paginate(array_merge($where_status, $where), $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来
        $this->assign("total", $PlayPackageOrderModel->where($where)->count());//总数量

        //总订单金额
        $total_amount = $PlayPackageOrderModel->where(array_merge($where_status, $where))->sum("amount");
        $this->assign("total_amount", $total_amount);

        //数据统计
        $status_arr       = $PlayPackageOrderInit->status_admin_where;
        $status_name_list = $PlayPackageOrderInit->status_admin;
        $count            = [];
        foreach ($status_arr as $key => $val) {
            $map                    = [];
            $map[]                  = ['status', 'in', $val];
            $map                    = array_merge($map, $where);
            $count[$key]['count']   = $PlayPackageOrderModel->where($map)->count();
            $count[$key]['key']     = $key;
            $count[$key]['name']    = $status_name_list[$key];
            $count[$key]['is_ture'] = false;
            if ($params['status'] == $key) $count[$key]['is_ture'] = true;
        }

        $this->assign('count', $count);

        return $this->fetch();
    }

    //编辑详情
    public function edit()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单  (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //派单
    public function distribution()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单  (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $this->assign("user_list", $MemberPlayModel->order('list_order,id desc')->select());


        return $this->fetch();
    }


    //派单提交
    public function distribution_post()
    {
        // 启动事务
        Db::startTrans();


        $params = $this->request->param();


        $this->receive_order($params['play_user_id'], $params['id']);


        // 提交事务
        Db::commit();


        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 派单接单
     * @param int $play_user_id 陪玩id
     * @param int $id           订单id
     */
    public function receive_order($play_user_id = 0, $id = 0)
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderInit     = new \init\PlayUserOrderInit();//陪玩管理   (ps:InitController)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayOrderController   = new PlayOrderController();

        //参数
        $params = $this->request->param();
        unset($params["play_user_id"]);

        $user_info = $MemberPlayModel->where('id', $play_user_id)->find();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $id];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (!in_array($order_info['status'], [20, 25, 30, 40])) $this->error('非法操作!');

        //接单时候必须大于设定的保证金金额才可以接单
        $minimum_deposit = cmf_config('minimum_deposit');
        if ($user_info['point'] < $minimum_deposit) $this->error('保证金余额不足,请充值!');


        //将之前的陪玩全部改为已取消
        $map   = [];
        $map[] = ['order_num', '=', $order_info['order_num']];
        $PlayUserOrderModel->where($map)->strict(false)->update([
            'remark'      => "派单新陪玩:{$play_user_id};修改时间:" . date("Y-m-d H:i:s"),//换人备注
            'status'      => 10,
            'cancel_time' => time(),
            'update_time' => time(),
            'delete_time' => time(),
        ]);


        //添加到陪玩记录
        $insert['user_id']   = $play_user_id;
        $insert['order_num'] = $order_info['order_num'];
        $insert['status']    = 30;//默认未取消
        $PlayUserOrderInit->api_edit_post($insert);


        $params['status']       = 30;
        $params['is_begin']     = 1;//可以开始服务
        $params['receive_time'] = time();


        //订单信息
        $params['play_user_id']        = $play_user_id;
        $params['where_play_user_ids'] = "/{$play_user_id}/";


        //更改订单信息
        unset($params["user_id"]);
        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        //更新订单佣金
        $PlayOrderController->update_commission($order_info['order_num']);

    }


    //refuse
    public function refuse()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单  (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        unset($toArray['order_num'], $toArray['user_id'], $toArray['play_user_id'], $toArray['pay_type']);
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $PlayPackageOrderInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //audit_post
    public function audit_post()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        $update['status'] = $params['status'];
        $update['refuse'] = $params['refuse'];

        //提交数据
        $result = $PlayPackageOrderInit->edit_post_two($update, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index?status={$params['show_status']}&user_id={$params['user_id']}&order_num={$params['order_num']}&play_user_id={$params['play_user_id']}&pay_type={$params['pay_type']}&goods_name={$params['goods_name']}&order_date={$params['order_date']}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $PlayPackageOrderInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //取消订单
    public function cancel_order()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $ShopCouponUserInit    = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)


        $params = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        $params['cancel_time'] = time();
        $params['status']      = 10;


        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        //提交数据
        $result = $PlayPackageOrderInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");


        //如使用优惠,将优惠券返回
        if ($order_info['coupon_id']) $ShopCouponUserInit->cancel_use_coupon($order_info['coupon_id']);


        $this->success("保存成功", "index{$this->params_url}");
    }


    //退款
    public function refund_pass()
    {
        $PlayPackageOrderInit = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $Pay = new PayController();


        $params = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        $params['refund_time'] = time();
        $params['status']      = 15;


        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');



        $map            = [];
        $map[]          = ['order_num', '=', $order_info['order_num']];//实际订单号
        $map[]          = ['status', '=', 2];//已支付
        $pay_info       = Db::name('order_pay')->where($map)->find();//支付记录表
        $order_num      = $pay_info['pay_num'];//支付单号
        $transaction_id = $pay_info['trade_num'];//第三方单号


        $refund_result = $Pay->wx_pay_refund($transaction_id, $order_num, $order_info['amount']);
        $refund_result = $refund_result['data'];
        if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);

        //提交数据
        $result = $PlayPackageOrderInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");


        $this->success("退款成功");
    }

    //添加
    public function add()
    {
        return $this->fetch();
    }


    //完成订单,给陪玩增加余额
    public function play_accomplish()
    {
        // 启动事务
        Db::startTrans();


        $PlayPackageOrderInit = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayOrderController  = new PlayOrderController();//陪玩端订单管理


        //参数
        $params = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        $params['status']          = 50;//给陪玩发送佣金
        $params['update_time']     = time();
        $params['accomplish_time'] = time();


        //发送佣金
        if ($order_info['is_commission'] == 2) $PlayOrderController->send_commission($order_info['order_num']);//后台完成订单,如果没有发放佣金进行发放


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        // 提交事务
        Db::commit();


        $this->success("操作成功!", "index?status={$params['show_status']}");
    }


    //添加提交
    public function add_post()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //插入数据
        $result = $PlayPackageOrderInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function details()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();

        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //删除
    public function delete()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $PlayPackageOrderInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $PlayPackageOrderInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $params                = $this->request->param("list_order/a");

        //提交更新
        $result = $PlayPackageOrderInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)


        $result = $PlayPackageOrderInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {

            //订单号过长问题
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";

            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';


            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";


            //背景颜色
            if ($item['unit'] == '测试8') $item['BackgroundColor'] = 'red';
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "订单号", "rowVal" => "order_num", "width" => 20],
            ["rowName" => "订单类型", "rowVal" => "order_type_text", "width" => 20],
            ["rowName" => "接单类型", "rowVal" => "receive_type_text", "width" => 20],
            ["rowName" => "支付金额", "rowVal" => "amount", "width" => 20],
            ["rowName" => "状态", "rowVal" => "status_name", "width" => 20],
            ["rowName" => "编号", "rowVal" => "code", "width" => 20],
            ["rowName" => "区服", "rowVal" => "area", "width" => 20],
            ["rowName" => "微信号", "rowVal" => "wx", "width" => 20],
            ["rowName" => "游戏名", "rowVal" => "name", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "订单导出"]);
    }


}
