<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"PlayPackageOrder",
 *     "name_underline"  =>"play_package_order",
 *     "table_name"      =>"play_package_order",
 *     "model_name"      =>"PlayPackageOrderModel",
 *     "remark"          =>"套餐订单",
 *     "author"          =>"",
 *     "create_time"     =>"2024-04-15 14:19:38",
 *     "version"         =>"1.0",
 *     "use"             => new \init\PlayPackageOrderInit();
 * )
 */

use think\facade\Db;


class PlayPackageOrderInit extends Base
{

    public $order_type   = [1 => "套餐", 2 => "陪玩"];//订单类型
    public $play_type    = [1 => "指定陪玩", 2 => "随机陪玩"];//陪玩类型
    public $receive_type = [1 => "个人", 2 => "团队"];//接单类型
    public $pay_type     = [1 => "微信支付", 2 => "余额支付", 3 => "支付宝"];//支付方式

    //用户端状态
    public $status_api_where = [1 => [1], 14 => [14], 20 => [2, 20, 25], 30 => [30], 40 => [40, 60], 50 => [50, 62], 10 => [10, 15]];//状态条件
    public $status_api       = [1 => "待付款", 2 => "待接单", 20 => "待接单", 25 => "待接单", 30 => "待服务", 40 => "服务中", 50 => "已完成", 60 => "服务中", 62 => "已完成", 10 => "已取消", 14 => "申请退款", 15 => "已退款"];//状态

    //陪玩状态
    public $status_play_where = [1000 => [25, 30, 40, 50, 60, 62], 14 => [14], 30 => [30], 40 => [40], 50 => [50], 60 => [60, 62]];//状态条件
    public $status_play       = [14 => "申请退款", 25 => "待服务", 30 => "待服务", 40 => "服务中", 50 => "已完成", 60 => "待审核", 62 => "已驳回"];//状态

    //后台状态
    public $status_admin_where = [1 => [1], 20 => [20, 25], 30 => [30], 40 => [40], 50 => [50], 60 => [60], 62 => [62], 10 => [10], 14 => [14], 15 => [15]];//状态
    public $status_admin       = [1 => "待付款", 20 => "待接单", 30 => "待服务", 40 => "服务中", 50 => "已完成", 60 => "陪玩结束,平台审核", 62 => "已驳回", 10 => "已取消", 14 => "申请退款", 15 => "已退款"];//状态
    public $status_admin_text  = [1 => "待付款", 20 => "待接单", 25 => "待接单", 30 => "待服务", 40 => "服务中", 50 => "已完成", 60 => "陪玩结束,平台审核", 62 => "已驳回", 10 => "已取消", 14 => "申请退款", 15 => "已退款"];//状态
    public $status_color       = [1 => "purple", 2 => "orange", 3 => "blue", 4 => "blue", 5 => "green", 10 => "red"];//状态


    public $Field         = "*";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $MemberInit         = new \init\MemberInit();//会员管理 (ps:InitController)
        $PlayUserOrderInit  = new \init\PlayUserOrderInit();//陪玩管理   (ps:InitController)
        $PlayUserOrderModel = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $MemberLevelModel   = new \initmodel\MemberLevelModel(); //陪玩等级  (ps:InitModel)
        $OrderPayModel      = new \initmodel\OrderPayModel();


        //默认游戏名称
        //        $default_game_name = cmf_config('default_game_name');
        //        //默认游戏图片
        //        $default_game_image = cmf_config('default_game_image');

        //处理转文字
        $item['pay_type_text']     = $this->pay_type[$item['pay_type']];//支付方式
        $item['play_type_text']    = $this->play_type[$item['play_type']];//陪玩类型
        $item['receive_type_text'] = $this->receive_type[$item['receive_type']];//接单类型
        $item['order_type_text']   = $this->order_type[$item['order_type']];//订单类型
        $item['status_color']      = $this->status_color[$item['status']];//状态颜色


        //接单大厅不显示 游戏名,虚拟图
        //        if ($params["is_receive"]) {
        //            if ($default_game_name) $item['name'] = $default_game_name;
        //            if ($default_game_image) $item['image'] = $default_game_image;
        //        }


        //查询用户信息
        $user_info         = $MemberInit->get_find(['id' => $item['user_id']], ['field' => '*']);
        $item['user_info'] = $user_info;


        //陪玩列表
        $map10   = [];
        $map10[] = ["order_num", "=", $item["order_num"]];


        //判断权限
        $item['is_but'] = true;
        if ($item['play_user_id'] != $params['user_id']) $item['is_but'] = false;


        //查询出所有陪玩列表
        $trimmedInput = trim($item['where_play_user_ids'], '/');
        $parts        = explode('/,/', $trimmedInput);
        // 使用 array_map 将每个部分中的数字提取出来
        $play_user_ids         = array_map('intval', $parts);
        $item['play_user_ids'] = $play_user_ids;

        //陪玩等级
        $item['level_name'] = $MemberLevelModel->where('id', '=', $item['level_id'])->value('name');

        //查出支付单号
        $map11           = [];
        $map11[]         = ['status', '=', 2];
        $pay_order_num   = $OrderPayModel->where(array_merge($map10, $map11))->value('pay_num');
        $item['pay_num'] = $pay_order_num;


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件
            if ($item['image']) $item['image'] = cmf_get_asset_url($item['image']);
            if ($item['accomplish_images']) $item['accomplish_images'] = $this->getImagesUrl($item['accomplish_images']);


            if ($params['is_user']) {
                $item['status_name'] = $this->status_api[$item['status']];//状态
            } elseif ($params['is_play']) {
                $item['status_name'] = $this->status_play[$item['status']];//状态
            } else {
                $item['status_name'] = $this->status_admin[$item['status']];//状态
            }

            //前台只看未取消的陪玩列表
            // $map10[] = ["status", "in", [30]];


            //隐藏内容
            if ($item['user_id'] != $params['user_id'] && $item['play_user_id'] != $params['user_id']) {
                $item['play_name'] = '*';
                $item['code']      = '*';
            }


            //陪玩列表
            $map10[]                 = ["status", "in", [30]];
            $user_order_list         = $PlayUserOrderInit->get_list($map10, ['field' => '*']);
            $item['user_order_list'] = $user_order_list;

        } else {
            //admin处理文件
            $item['status_name'] = $this->status_admin_text[$item['status']];//状态
            if ($item['accomplish_images']) $item['accomplish_images'] = $this->getParams($item['accomplish_images']);

            //后台陪玩列表查询全部信息
            $user_order_list         = $PlayUserOrderInit->get_list($map10, ['field' => '*']);
            $item['user_order_list'] = $user_order_list;
        }


        //导出数据处理
        if (isset($params["is_export"]) && $params["is_export"]) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
        }


        return $item;
    }


    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //查询数据
        $result = $PlayPackageOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 分页查询
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 page_size=每页条数  InterfaceType=admin|api后端,前端
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //查询数据
        $result = $PlayPackageOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && $result->isEmpty()) return false;


        return $result;
    }

    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_join_list($where = [], $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)

        //查询数据
        $result = $PlayPackageOrderModel
            ->alias('a')
            ->join('member b', 'a.user_id = b.id')
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);


                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 获取详情
     * @param $where     条件 或 id值
     * @param $params    扩充参数 field=过滤字段  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];

        //查询数据
        $item = $PlayPackageOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->find();


        if (empty($item)) return false;


        //处理公共数据
        $item = $this->common_item($item, $params);

        //富文本处理


        return $item;
    }


    /**
     * 前端  编辑&添加
     * @param $params 参数
     * @param $where  where条件
     * @return void
     */
    public function api_edit_post($params = [], $where = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params, $where);//api提交

        return $result;
    }


    /**
     * 后台  编辑&添加
     * @param $model  类
     * @param $params 参数
     * @param $where  更新提交(编辑数据使用)
     * @return void
     */
    public function admin_edit_post($params = [], $where = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params, $where);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @param $where where条件
     * @return void
     */
    public function edit_post($params, $where = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $PlayPackageOrderModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $PlayPackageOrderModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $PlayPackageOrderModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 提交(副本,无任何操作) 编辑&添加
     * @param $params
     * @param $where where 条件
     * @return void
     */
    public function edit_post_two($params, $where = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $PlayPackageOrderModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $PlayPackageOrderModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $PlayPackageOrderModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 删除数据 软删除
     * @param $id     传id  int或array都可以
     * @param $type   1软删除 2真实删除
     * @param $params 扩充参数
     * @return void
     */
    public function delete_post($id, $type = 1, $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        if ($type == 1) $result = $PlayPackageOrderModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $PlayPackageOrderModel->destroy($id, true);//真实删除

        return $result;
    }


    /**
     * 后台批量操作
     * @param $id
     * @param $params 修改值
     * @return void
     */
    public function batch_post($id, $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $PlayPackageOrderModel->where($where)->strict(false)->update($params);//修改状态

        return $result;
    }


    /**
     * 后台  排序
     * @param $list_order 排序
     * @param $params     扩充参数
     * @return void
     */
    public function list_order_post($list_order, $params = [])
    {
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $PlayPackageOrderModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
