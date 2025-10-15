<?php

namespace app\admin\controller;

use think\facade\Db;

/**
 * @adminMenuRoot(
 *     'name'   =>'Member',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'cogs',
 *     'remark' =>'会员管理',
 * )
 */
class MemberController extends BaseController
{

    public function initialize()
    {

        //会员管理

        parent::initialize();


        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

    }


    /**
     * 展示
     * @adminMenu(
     *     'name'   => 'Member',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '会员管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $this->assign("excel", $params);//导出使用


        $where = [];
        if ($params["keyword"]) $where[] = ["nickname|phone", "like", "%{$params["keyword"]}%"];
        if ($params["u_id"]) $where[] = ["id", "=", $params['u_id']];


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);


        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';//身份类型,后台
        if (empty($params['is_order'])) {
            $result = $MemberInit->get_list_paginate($where, $params);
        }

        //排序
        if ($params['is_order'] == 1) {
            $where = [];
            if ($params["keyword"]) $where[] = ["m.nickname|m.phone", "like", "%{$params["keyword"]}%"];
            if ($params["u_id"]) $where[] = ["m.id", "=", $params['u_id']];
            $result = $MemberInit->get_list_join($where, $params);
        }

        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来


        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $id         = $this->request->param('id');
        $MemberInit = new \init\MemberInit();//会员管理


        $where           = [];
        $where[]         = ['id', '=', $id];
        $params['field'] = '*';//身份类型,后台

        $result = $MemberInit->get_find($where, $params);


        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $result = $MemberInit->edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index');
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //导入
    public function member_import()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理


        $result = $MemberInit->edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index');
    }


    //查看详情
    public function find()
    {
        $id         = $this->request->param('id');
        $MemberInit = new \init\MemberInit();//会员管理


        $where           = [];
        $where[]         = ['id', '=', $id];
        $params['field'] = '*';//身份类型,后台

        $result = $MemberInit->get_find($where, $params);


        if (empty($result)) $this->error("暂无数据");


        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }


    //删除
    public function delete()
    {
        $id         = $this->request->param('id/a');
        $MemberInit = new \init\MemberInit();//会员管理

        if (empty($id)) $id = $this->request->param('ids/a');


        $result = $MemberInit->delete_post($id);
        if (empty($result)) $this->error('失败请重试');


        $this->success("删除成功");
    }


    //更新排序
    public function list_order_post()
    {
        $params     = $this->request->param('list_order/a');
        $MemberInit = new \init\MemberInit();//会员管理


        $result = $MemberInit->list_order_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功");
    }

    //更改状态
    public function batch_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理


        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        $result = $MemberInit->batch_post($id, $params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index');
    }

    /******************************************   余额操作 & 积分操作  ********************************************************/
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
        $MemberModel       = new \initmodel\MemberModel();//用户管理
        $params            = $this->request->param();
        $member            = $MemberModel->where('id', '=', $params['id'])->find();
        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        if ($params['operate_type'] == 1) {
            //余额

            if ($params['type'] == 1) {
                //增加
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户余额];";//管理备注
                $MemberModel->inc_balance($params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }

            if ($params['type'] == 2) {
                //扣除
                if ($member['balance'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户余额];";//管理备注
                $MemberModel->dec_balance($params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }
        }


        if ($params['operate_type'] == 2) {
            //积分

            if ($params['type'] == 1) {
                //增加
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户积分];";//管理备注
                $MemberModel->inc_point($params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }

            if ($params['type'] == 2) {
                //扣除
                if ($member['point'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户积分];";//管理备注
                $MemberModel->dec_point($params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
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
        if ($params['type'] == 1) $name = 'member_balance';
        if ($params['type'] == 2) $name = 'member_point';
        if (empty($name)) $name = 'member_balance';

        if ($name == 'member_balance') {
            $this->assign('type', 1);
            $this->assign('type1', 'class="active"');
        }
        if ($name == 'member_point') {
            $this->assign('type', 2);
            $this->assign('type2', 'class="active"');
        }


        //筛选条件
        $map   = [];
        $map[] = ["user_id", "=", $params["user_id"]];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params['name']) $map[] = ["content|order_num", "like", "%{$params['name']}%"];


        //导出数据
        if ($params["is_export"]) $this->export_excel_use($map, $params, $name);


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


    public function children()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $where = [];
        if ($params['pid']) {
            $where[] = ['pid', '=', $params['pid']];
            $this->assign("pid", $params['pid']);
        }

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $result                  = $MemberInit->get_list_paginate($where, $params);


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来


        return $this->fetch();
    }


    /**
     * 导出数据 export_excel_use ,积分-余额导出
     * @param array $where 条件
     */
    public function export_excel_use($where = [], $params = [], $name)
    {
        $type   = [1 => '收入', 2 => '支出'];
        $result = Db::name($name)
            ->where($where)
            ->order('id desc')
            ->select();

        $result = $result->toArray();

        foreach ($result as $k => &$item) {
            //用户信息
            $item['user_info'] = Db::name('member')->find($item['user_id']);

            //导出基本信息
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
            $item['type_name']   = $type[$item['type']];

            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "类型", "rowVal" => "type_name", "width" => 10],
            ["rowName" => "说明", "rowVal" => "content", "width" => 10],
            ["rowName" => "变得值", "rowVal" => "price", "width" => 10],
            ["rowName" => "变动前", "rowVal" => "before", "width" => 10],
            ["rowName" => "变动后", "rowVal" => "after", "width" => 10],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => 2],
        //            ["rowName" => "列2", "acrossCells" => 2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "操作记录"]);
    }


    /**
     * 导出数据--用户导出
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $MemberInit = new \init\MemberInit();//会员管理

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';
        $result                  = $MemberInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);

            //订单号过长问题
            if ($item["identity_number"]) $item["identity_number"] = $item["identity_number"] . "\t";
            if ($item["number_bank"]) $item["number_bank"] = $item["number_bank"] . "\t";

            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';


        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "nickname", "width" => 30],
            ["rowName" => "余额", "rowVal" => "balance", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "微信", "rowVal" => "wx", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];

        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "用户管理"]);
    }


}
