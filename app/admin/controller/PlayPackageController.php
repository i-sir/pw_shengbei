<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"PlayPackage",
 *     "name_underline"      =>"play_package",
 *     "controller_name"     =>"PlayPackage",
 *     "table_name"          =>"play_package",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"套餐管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-04-14 11:18:36",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\PlayPackageController();
 * )
 */


use think\facade\Db;


class PlayPackageController extends BaseController
{


    public function initialize()
    {
        //套餐管理

        parent::initialize();

        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);


        $PlayClassModel = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)
        $map            = [];
        $map[]          = ['pid', '=', 0];
        $this->assign("class_list", $PlayClassModel->where($map)->select());

    }


    public function get_class_list()
    {
        $PlayClassModel   = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)


        $params    = $this->request->param();
        $parent_id = $PlayClassModel->where('pid', '=', 0)->order('id desc')->value('id');//默认值
        if ($params['class_id']) $parent_id = $params['class_id'];
        $where   = [];
        $where[] = ['pid', '=', $parent_id];


        /** 查询数据库信息,匹配二级分类选中状态 **/
        $info = $PlayPackageModel->where('id', '=', $params['id'])->find();


        $result = $PlayClassModel
            ->where($where)
            ->select()
            ->each(function ($item, $key) use ($info) {
                //如果详情中,分类存在,回显
                if ($info['second_class_id'] == $item['id']) $item['selected'] = 'selected';
                return $item;
            });
        if (empty(count($result))) $this->error('暂无数据!');
        $this->success("list", '', $result);
    }

    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'PlayPackage',
     *     'name_underline'   => 'play_package',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '套餐管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理    (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        //$where[]=["type","=", 1];


        $params["InterfaceType"] = "admin";//接口类型


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $PlayPackageInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        return $this->fetch();
    }

    //编辑详情
    public function edit()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理  (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageInit->get_find($where, $params);
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
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $PlayPackageInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $PlayPackageInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //添加
    public function add()
    {
        return $this->fetch();
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
        $where[] = ["type", "=", 1];//套餐


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


    //添加提交
    public function add_post()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //插入数据
        $result = $PlayPackageInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //分类管理
    public function class()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $PlayClassModel   = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)


        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $params["field"]         = "*";
        $result                  = $PlayPackageInit->get_find($where, $params);
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
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $PlayPackageInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功");
    }


    //查看详情
    public function find()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理    (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $PlayPackageInit->get_find($where, $params);
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
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $PlayPackageInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $PlayPackageInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $params           = $this->request->param("list_order/a");

        //提交更新
        $result = $PlayPackageInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)


        $result = $PlayPackageInit->get_list($where, $params);

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
