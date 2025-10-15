<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"MemberPlay",
 *     "name_underline"  =>"member_play",
 *     "table_name"      =>"member_play",
 *     "model_name"      =>"MemberPlayModel",
 *     "remark"          =>"陪玩管理",
 *     "author"          =>"",
 *     "create_time"     =>"2024-04-23 14:33:08",
 *     "version"         =>"1.0",
 *     "use"             => new \init\MemberPlayInit();
 * )
 */

use think\facade\Db;


class MemberPlayInit extends Base
{
    public $level   = [1 => '初级', 2 => '中极', 3 => '高级'];
    public $is_line = [1 => '在线', 2 => '离线'];

    public $Field         = "id,avatar,nickname,phone,openid";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "list_order,id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $MemberLevelInit       = new \init\MemberLevelInit();//陪玩等级    (ps:InitController)

        //处理转文字
        if ($item['avatar']) $item['avatar'] = cmf_get_asset_url($item['avatar']);
        if ($item['is_line']) $item['is_line_name'] = $this->is_line[$item['is_line']];


        //等级信息
        $level_info          = $MemberLevelInit->get_find($item['level_id']);
        $item['level_name']  = $level_info['name'];
        $item['level_image'] = $level_info['image'];
        $item['level_info']  = $level_info;

        //提现总金额
        $map10                    = [];
        $map10[]                  = ['identity_type', '=', 'play'];
        $map10[]                  = ['user_id', '=', $item['id']];
        $item['total_withdrawal'] = $MemberWithdrawalModel->where($map10)->sum('price');


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件
            if ($item['tag']) $item['tag'] = $this->getParams($item['tag'], '/');
            if ($item['images']) $item['images'] = $this->getImagesUrl($item['images']);

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
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)


        //查询数据
        $result = $MemberPlayModel
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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)


        //查询数据
        $result = $MemberPlayModel
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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)

        //查询数据
        $result = $MemberPlayModel
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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];

        //查询数据
        $item = $MemberPlayModel
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
        $MemberPlayModel  = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)
        $MemberLevelModel = new \initmodel\MemberLevelModel(); //陪玩等级   (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);

        //如调整,接单数随之变化,接单数为等级设置值
        if ($params['old_level_id'] != $params['level_id']) {
            $level_info            = $MemberLevelModel->where('id', '=', $params['level_id'])->find();
            $params['order_total'] = $level_info['number'];
        }


        //密码
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);
        if (empty($params['pass'])) unset($params['pass']);


        if ($params['images']) $params['images'] = $this->setParams($params['images']);


        if (!empty($params["id"])) {
            //检测,如果手机号发生变化,手机号是否已经存在问题
            if ($params['old_phone'] != $params['phone']) {
                $member_info = $MemberPlayModel->where('phone', '=', $params['phone'])->find();
                if (!empty($member_info)) $this->error('手机号已经存在');
            }

            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $MemberPlayModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //检测,如果手机号发生变化,手机号是否已经存在问题
            if ($params['old_phone'] != $params['phone']) {
                $member_info = $MemberPlayModel->where('phone', '=', $params['phone'])->find();
                if (!empty($member_info)) $this->error('手机号已经存在');
            }

            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $MemberPlayModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params['openid']      = 'o2x' . md5(cmf_random_string(60, 3));
            $params['ip']          = get_client_ip();
            $params['invite_code'] = $this->get_only_num('member_play', 'invite_code', 20, 2);

            //检测手机号是否已经存在
            $map      = [];
            $map[]    = ['phone', '=', $params['phone']];
            $is_phone = $MemberPlayModel->where($map)->count();
            if ($is_phone) $this->error('手机号已存在!');


            $params["create_time"] = time();
            $result                = $MemberPlayModel->strict(false)->insert($params, true);
        }

        return $result;
    }

    /**
     * 获取微信昵称
     * @return void
     */
    public function get_member_wx_nickname()
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)
        $max_id          = $MemberPlayModel->max('id');
        $name            = '陪玩_';
        return $name . ($max_id + 1);
    }

    /**
     * 提交(副本,无任何操作) 编辑&添加
     * @param $params
     * @param $where where 条件
     * @return void
     */
    public function edit_post_two($params, $where = [])
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);
        if ($params['images']) $params['images'] = $this->setParams($params['images']);


        //处理结果值
        if ($params['play_ids']) {
            $play_ids           = array_keys($params['play_ids']);//提取key
            $params['play_ids'] = $this->setParams($play_ids);
        }



        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $MemberPlayModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $MemberPlayModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $MemberPlayModel->strict(false)->insert($params, true);
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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)


        if ($type == 1) $result = $MemberPlayModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $MemberPlayModel->destroy($id, true);//真实删除

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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $MemberPlayModel->where($where)->strict(false)->update($params);//修改状态

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
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $MemberPlayModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
