<?php

namespace init;


use initmodel\PlayClassModel;
use initmodel\PlayPackageModel;
use initmodel\PlayPackageOrderModel;

/**
 * @Init(
 *     "name"            =>"PlayPackage",
 *     "name_underline"  =>"play_package",
 *     "table_name"      =>"play_package",
 *     "model_name"      =>"PlayPackageModel",
 *     "remark"          =>"套餐管理",
 *     "author"          =>"",
 *     "create_time"     =>"2024-04-14 11:18:36",
 *     "version"         =>"1.0",
 *     "use"             => new \init\PlayPackageInit();
 * )
 */
class PlayPackageInit extends Base
{

    public $is_index = [1 => '是', 2 => '否'];//首页推荐

    public $Field         = "*";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "list_order,id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $PlayPackageInit  = new PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)
    }

    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)


        //查询数据
        $result = $PlayPackageModel
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
        if ($this->InterfaceType == 'api' && empty(count($result))) return [];

        return $result;
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $PlayPackageSkuInit    = new PlayPackageSkuInit();//规格管理   (ps:InitController)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $PlayClassModel        = new PlayClassModel(); //分类管理   (ps:InitModel)


        //处理转文字
        $item['is_index_text'] = $this->is_index[$item['is_index']];//首页推荐


        $one_info = $PlayClassModel->where('id', '=', $item['class_id'])->find();
        $two_info = $PlayClassModel->where('id', '=', $item['second_class_id'])->find();
        if ($one_info) $item['class_name'] = $one_info['name'];
        if ($two_info) $item['class_name'] = $two_info['name'];
        if ($two_info && $one_info) $item['class_name'] = $one_info['name'] . '-' . $two_info['name'];
        $item['send1'] = $two_info['name'];//不支出斜杠符号,文字不支持那么多


        //规格
        if ($params['is_sku']) {
            $sku_info         = $PlayPackageSkuInit->get_list(['pid' => $item['id']]);
            $item['sku_info'] = $sku_info;
        }


        //销量
        $map                = [];
        $map[]              = ['package_id', '=', $item['id']];
        $map[]              = ['status', 'in', [20, 30, 40, 50, 60, 62, 65]];
        $item['sell_count'] = $PlayPackageOrderModel->where($map)->sum('count');
        $item['sell_count'] += $item['virtual_stock'];


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件
            if ($item['image']) $item['image'] = cmf_get_asset_url($item['image']);
            if ($item['images']) $item['images'] = $this->getImagesUrl($item['images']);
            if ($item['video']) $item['video'] = cmf_get_asset_url($item['video']);

        } else {
            //admin处理文件
            if ($item['images']) $item['images'] = $this->getParams($item['images']);
        }


        //导出数据处理
        if (isset($params["is_export"]) && $params["is_export"]) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
        }

        return $item;
    }

    /**
     * 分页查询
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 page_size=每页条数  InterfaceType=admin|api后端,前端
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)


        //查询数据
        $result = $PlayPackageModel
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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)

        //查询数据
        $result = $PlayPackageModel
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
     * 前端  编辑&添加
     * @param $params 参数
     * @param $where  where条件
     * @return void
     */
    public function api_edit_post($params = [], $where = [])
    {
        $result = false;

        //处理共同数据
        if ($params['images']) $params['images'] = $this->setParams($params['images']);

        $result = $this->edit_post($params, $where);//api提交

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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if ($params['class_id']) $params['search_class_id'] = $params['class_id'];
        if ($params['second_class_id']) $params['search_class_id'] = $params['second_class_id'];

        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $PlayPackageModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $PlayPackageModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $PlayPackageModel->strict(false)->insert($params, true);
        }

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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];

        //查询数据
        $item = $PlayPackageModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->find();


        if (empty($item)) return false;


        //处理公共数据
        $item = $this->common_item($item, $params);

        //富文本处理
        if ($item['content']) $item['content'] = htmlspecialchars_decode(cmf_replace_content_file_url($item['content']));

        return $item;
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
        if ($params['images']) $params['images'] = $this->setParams($params['images']);

        $result = $this->edit_post($params, $where);//admin提交

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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);

        if ($params['play_ids']) {
            $play_ids           = array_keys($params['play_ids']);//提取key
            $params['play_ids'] = $this->setParams($play_ids);
        }

        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $PlayPackageModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $PlayPackageModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $PlayPackageModel->strict(false)->insert($params, true);
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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)


        if ($type == 1) $result = $PlayPackageModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $PlayPackageModel->destroy($id, true);//真实删除

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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $PlayPackageModel->where($where)->strict(false)->update($params);//修改状态

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
        $PlayPackageModel = new PlayPackageModel(); //套餐管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $PlayPackageModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
