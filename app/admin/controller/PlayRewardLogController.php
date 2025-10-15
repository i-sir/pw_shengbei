<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"PlayRewardLog",
 *     "name_underline"      =>"play_reward_log",
 *     "controller_name"     =>"PlayRewardLog",
 *     "table_name"          =>"play_reward_log",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"打赏记录",
 *     "author"              =>"",
 *     "create_time"         =>"2024-06-27 18:04:42",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\PlayRewardLogController();
 * )
 */


use think\facade\Db;


class PlayRewardLogController extends BaseController
{


    public function initialize()
    {
        //打赏记录

        parent::initialize();

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
     * 展示
     * @adminMenu(
     *     'name'             => 'PlayRewardLog',
     *     'name_underline'   => 'play_reward_log',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '打赏记录',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录    (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        $where[] = ["status", "=", 2];


        $params["InterfaceType"] = "admin";//接口类型


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $PlayRewardLogInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        //打赏总金额
        $reward_amount = $PlayRewardLogModel->where($where)->sum('amount');
        $this->assign("reward_amount", $reward_amount);



        return $this->fetch();
    }

    //编辑详情
    public function edit()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录  (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayRewardLogInit->get_find($where, $params);
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
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $PlayRewardLogInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $PlayRewardLogInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //通过,给钱
    public function pass_post()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $MemberPlayModel    = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        $params = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        $order  = $PlayRewardLogModel->where($where)->find();
        $remark = "操作人[打赏完成];操作说明[订单已完成-{$order['order_num']}];操作类型[用户打赏陪玩];";//管理备注
        $MemberPlayModel->inc_balance($order['play_user_id'], $order['commission'], '用户打赏', $remark, $order['id'], $order['order_num'], 40);


        $params['is_send'] = 1;

        //提交数据
        $result = $PlayRewardLogInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功", "index{$this->params_url}");
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        //插入数据
        $result = $PlayRewardLogInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录    (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayRewardLogInit->get_find($where, $params);
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
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $PlayRewardLogInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $PlayRewardLogInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)
        $params             = $this->request->param("list_order/a");

        //提交更新
        $result = $PlayRewardLogInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录   (ps:InitModel)


        $result = $PlayRewardLogInit->get_list($where, $params);

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
            ["rowName" => "名字", "rowVal" => "name", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "测试", "rowVal" => "test", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "导出"]);
    }


}
