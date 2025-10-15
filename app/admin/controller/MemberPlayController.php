<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"MemberPlay",
 *     "name_underline"      =>"member_play",
 *     "controller_name"     =>"MemberPlay",
 *     "table_name"          =>"member_play",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"陪玩管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-04-23 14:33:08",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\MemberPlayController();
 * )
 */


use think\facade\Db;


class MemberPlayController extends BaseController
{


    public function initialize()
    {
        //陪玩管理

        parent::initialize();

        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);


        $MemberLevelInit = new \init\MemberLevelInit();//陪玩等级    (ps:InitController)
        $this->assign('level_list', $MemberLevelInit->get_list());
    }


    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'MemberPlay',
     *     'name_underline'   => 'member_play',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '陪玩管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["openid|nickname|phone", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        //$where[]=["type","=", 1];


        $params["InterfaceType"] = "admin";//接口类型
        $params["field"]         = "*";
        $params["order"]         = "list_order,is_index,id desc";
        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $MemberPlayInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来


        //总余额
        $total_balance = $MemberPlayModel->where($where)->sum("balance");
        $this->assign("total_balance", $total_balance);


        return $this->fetch();
    }

    //编辑详情
    public function edit()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理  (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $params["field"]         = "*";
        $result                  = $MemberPlayInit->get_find($where, $params);
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
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $MemberPlayInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $MemberPlayInit->edit_post_two($params, $where);
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
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //插入数据
        $result = $MemberPlayInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //评价管理
    public function evaluate()
    {
        $PlayEvaluateInit  = new \init\PlayEvaluateInit();//陪玩评价    (ps:InitController)
        $PlayEvaluateModel = new \initmodel\PlayEvaluateModel(); //陪玩评价   (ps:InitModel)
        $params            = $this->request->param();

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["pid|user_id|order_num|evaluate", "like", "%{$params["keyword"]}%"];
        if ($params["pid"]) $where[] = ["pid", "=", $params["pid"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        $where[] = ["type", "=", 2];//套餐


        $params["InterfaceType"] = "admin";//接口类型


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $PlayEvaluateInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        return $this->fetch();
    }


    //评价删除
    public function evaluate_delete()
    {
        $PlayEvaluateInit  = new \init\PlayEvaluateInit();//陪玩评价   (ps:InitController)
        $PlayEvaluateModel = new \initmodel\PlayEvaluateModel(); //陪玩评价   (ps:InitModel)
        $params            = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $PlayEvaluateInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "evaluate{$this->params_url}");
    }

    //查看详情
    public function find()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $params["field"]         = "*";
        $result                  = $MemberPlayInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //分类管理
    public function class()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayClassModel  = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)


        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $params["field"]         = "*";
        $result                  = $MemberPlayInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        //陪玩关联游戏分类
        $play_ids = $this->getParams($result['play_ids']);


        //分类列表
        $map        = [];
        $map[]      = ["pid", "=", 0];
        $class_list = $PlayClassModel->where($map)
            ->select()
            ->each(function ($item, $key) use ($PlayClassModel, $play_ids) {
                $map              = [];
                $map[]            = ['pid', '=', $item['id']];
                $item['children'] = $PlayClassModel->where($map)
                    ->select()
                    ->each(function ($item2, $key2) use ($play_ids) {
                        $item2['checked'] = false;
                        if (in_array($item2['id'], $play_ids)) $item2['checked'] = true;
                        return $item2;
                    });
                return $item;
            });

        //分类
        $this->assign("class_list", $class_list);


        return $this->fetch();
    }


    //提交(副本,无任何操作) 编辑&添加
    public function class_post()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $MemberPlayInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功");
    }

    //删除
    public function delete()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $MemberPlayInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $MemberPlayInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param("list_order/a");

        //提交更新
        $result = $MemberPlayInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)


        $result = $MemberPlayInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {

            //订单号过长问题
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";
            if ($item["id_number"]) $item["id_number"] = $item["id_number"] . "\t";
            if ($item["phone"]) $item["phone"] = $item["phone"] . "\t";

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
            ["rowName" => "用户信息", "rowVal" => "nickname", "width" => 30],
            ["rowName" => "手机号", "rowVal" => "phone", "width" => 20],
            ["rowName" => "余额", "rowVal" => "balance", "width" => 20],
            ["rowName" => "性别", "rowVal" => "gender", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "等级", "rowVal" => "level_name", "width" => 20],
            ["rowName" => "身份证号", "rowVal" => "id_number", "width" => 30],
            ["rowName" => "擅长游戏", "rowVal" => "hall", "width" => 20],
            ["rowName" => "擅长位置", "rowVal" => "great", "width" => 20],
            ["rowName" => "擅长英雄", "rowVal" => "hero", "width" => 20],
            ["rowName" => "简介", "rowVal" => "introduce", "width" => 30],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "陪玩导出"]);
    }


    //操作 积分 或余额
    public function operate()
    {
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }
        return $this->fetch();
    }

    //提交
    public function operate_post()
    {
        $MemberPlayModel   = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params            = $this->request->param();
        $play_info         = $MemberPlayModel->where('id', '=', $params['id'])->find();
        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        if ($params['operate_type'] == 1) {
            //余额

            if ($params['type'] == 1) {
                if(empty($params['content'])) $params['content'] ='管理员增加';
                //增加
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加陪玩余额];";//管理备注
                $MemberPlayModel->inc_balance($params['id'], $params['price'], $params['content'] , $remark, 0, cmf_order_sn(), 100);
            }

            if ($params['type'] == 2) {
                if(empty($params['content'])) $params['content'] ='管理员扣除';
                //扣除
                if ($play_info['balance'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除陪玩余额];";//管理备注
                $MemberPlayModel->dec_balance($params['id'], $params['price'], $params['content'] ?? '管理员扣除', $remark, 0, cmf_order_sn(), 100);
            }
        }


        if ($params['operate_type'] == 2) {
            //保证金

            if ($params['type'] == 1) {
                if(empty($params['content'])) $params['content'] ='管理员增加';
                //增加
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加陪玩积分];";//管理备注
                $MemberPlayModel->inc_point($params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(), 100);
            }

            if ($params['type'] == 2) {
                if(empty($params['content'])) $params['content'] ='管理员扣除';
                //扣除
                if ($play_info['point'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除陪玩积分];";//管理备注
                $MemberPlayModel->dec_point($params['id'], $params['price'], $params['content'] , $remark, 0, cmf_order_sn(), 100);
            }
        }

        $this->success('操作成功');
    }


    //编辑详情
    public function log()
    {
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }



        //数据库
        if ($params['type'] == 1) $name = 'member_balance_play';
        if ($params['type'] == 2) $name = 'member_point_play';
        if (empty($name)) $name = 'member_balance_play';

        if ($name == 'member_balance_play') {
            $this->assign('type', 1);
            $this->assign('type1', 'class="active"');
        }
        if ($name == 'member_point_play') {
            $this->assign('type', 2);
            $this->assign('type2', 'class="active"');
        }



        //筛选条件
        $map   = [];
        $map[] = ["user_id", "=", $params["user_id"]];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params['name']) $map[] = ["content|order_num", "like", "%{$params['name']}%"];


        $type   = [1 => '收入', 2 => '支出'];
        $result = Db::name($name)
            ->where($map)
            ->order('id desc')
            ->paginate(['list_rows' => 15, 'query' => $params])
            ->each(function ($item, $key) use ($type) {

                $item['type_name'] = $type[$item['type']];

                return $item;
            });


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来

        return $this->fetch();
    }
}
