<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"PlayHire",
 *     "name_underline"      =>"play_hire",
 *     "controller_name"     =>"PlayHire",
 *     "table_name"          =>"play_hire",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"租号管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-12-09 16:14:48",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\PlayHireController();
 * )
 */


use initmodel\MemberModel;
use think\facade\Db;


class PlayHireController extends BaseController
{


    public function initialize()
    {
        //租号管理

        parent::initialize();

        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);


        $PlayListInit = new \init\PlayListInit();//游戏列表    (ps:InitController)
        $this->assign("play_list", $PlayListInit->get_list());

        //区服
        $hire_district_server = cmf_config('hire_district_server');
        $this->assign('area_list', $this->getParams($hire_district_server, '/'));

        //系统
        $hire_system = cmf_config('hire_system');
        $this->assign('service_list', $this->getParams($hire_system, '/'));

        //客服类型
        $PlayHireInit = new \init\PlayHireInit();//租号管理    (ps:InitController)
        $this->assign('customer_type_list', $PlayHireInit->customer_type);

        //状态
        $this->assign('status_list', $PlayHireInit->status);
    }


    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'PlayHire',
     *     'name_underline'   => 'play_hire',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '租号管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理    (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();


        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["title|remark|area|service|id", "like", "%{$params["keyword"]}%"];
        if ($params["code"]) $where[] = ["code", "like", "%{$params["code"]}%"];
        if ($params["customer_id"]) $where[] = ["customer_id", "=", $params["customer_id"]];
        if ($params["account_id"]) $where[] = ["account_id", "=", $params["account_id"]];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];
        $where[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params['completeBeginTimecompleteBeginTime'] || $params['completeEndTime']) $where[] = $this->getBetweenTime($params['completeBeginTimecompleteBeginTime'], $params['completeEndTime'], 'complete_time');
        //$where[]=["type","=", 1];


        $params["InterfaceType"] = "admin";//接口类型


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $PlayHireInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理  (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayHireInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //编辑详情
    public function status()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理  (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayHireInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $PlayHireInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $PlayHireInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //审核
    public function audit_post()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $item                    = $PlayHireInit->get_find($where);
        if (empty($item)) $this->error("暂无数据");


        //已结算给用户增加余额
        if ($params['status'] == 4) {
            $params['complete_time'] = time();//结算时间
            $account_id              = $item['account_id'];//号主
            $cost_price              = $item['cost_price'];//成本价,增加余额

            $remark = "操作人[租号管理];操作说明[租号佣金];操作类型[租号管理];";//管理备注
            MemberModel::inc_balance($account_id, $cost_price, '租号', $remark, $item['id'], $item['code'], 60);
        }


        //提交数据
        $result = $PlayHireInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        //插入数据
        $result = $PlayHireInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理    (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayHireInit->get_find($where, $params);
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
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $PlayHireInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $PlayHireInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)
        $params        = $this->request->param("list_order/a");

        //提交更新
        $result = $PlayHireInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)


        $result = $PlayHireInit->get_list($where, $params);

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
            ["rowName" => "游戏名", "rowVal" => "play_name", "width" => 30],
            ["rowName" => "区服", "rowVal" => "area", "width" => 20],
            ["rowName" => "系统", "rowVal" => "service", "width" => 20],
            ["rowName" => "标题", "rowVal" => "title", "width" => 20],
            ["rowName" => "价格", "rowVal" => "price", "width" => 20],
            ["rowName" => "押金", "rowVal" => "deposit", "width" => 20],
            ["rowName" => "账号唯一编号", "rowVal" => "code", "width" => 20],
            ["rowName" => "特殊备注", "rowVal" => "remark", "width" => 20],
            ["rowName" => "状态", "rowVal" => "status_text", "width" => 20],
            ["rowName" => "租期", "rowVal" => "lease_term", "width" => 20],
            ["rowName" => "成本价", "rowVal" => "cost_price", "width" => 20],
            ["rowName" => "外显价", "rowVal" => "show_price", "width" => 20],
            ["rowName" => "号主ID", "rowVal" => "account_id", "width" => 20],
            ["rowName" => "客服ID", "rowVal" => "customer_id", "width" => 20],
            ["rowName" => "结算时间", "rowVal" => "complete_time", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "租号管理"]);
    }


}
