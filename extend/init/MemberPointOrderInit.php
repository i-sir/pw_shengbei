<?php

namespace init;


/**
    * @Init(
    *     "name"            =>"MemberPointOrder",
    *     "name_underline"  =>"member_point_order",
    *     "table_name"      =>"member_point_order",
    *     "model_name"      =>"MemberPointOrderModel",
    *     "remark"          =>"充值保证金",
    *     "author"          =>"",
    *     "create_time"     =>"2025-06-09 16:21:34",
    *     "version"         =>"1.0",
    *     "use"             => new \init\MemberPointOrderInit();
    * )
    */

use think\facade\Db;


class MemberPointOrderInit extends Base
{

    public $is_pay =[1=>'否',2=>'是'];//支付 
public $status =[1=>'未支付',2=>'已支付',3=>'申请退款'];//状态 

    public $Field         = "*";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

        //本init和model
        public function _init()
        {
            $MemberPointOrderInit  = new \init\MemberPointOrderInit();//充值保证金   (ps:InitController)
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)
        }

        /**
         * 处理公共数据
         * @param array $item 单条数据
         * @param array $params 参数
         * @return array|mixed
         */
        public function common_item($item = [], $params = [])
        {
            $MemberInit= new \init\MemberInit();//会员管理 (ps:InitController)


            
//处理转文字 
$item['is_pay_text']=$this->is_pay[$item['is_pay']];//支付 
$item['status_text']=$this->status[$item['status']];//状态 

            //查询用户信息
 $user_info=$MemberInit->get_find(['id'=>$item['user_id']]);
 $item['user_info']=$user_info;



            //接口类型
            if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
                if ($this->InterfaceType == 'api') {
                //api处理文件
                
                
                
                
            }else{
                //admin处理文件
                
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
        * @param $where 条件
        * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
        * @return false|mixed
        */
        public function get_list($where=[], $params = [])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)


            //查询数据
            $result = $MemberPointOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use($params)  {

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
        * @param $where 条件
        * @param $params 扩充参数 order=排序  field=过滤字段 page_size=每页条数  InterfaceType=admin|api后端,前端
        * @return mixed
        */
        public function get_list_paginate($where=[],  $params = [])
        {
                $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)


                //查询数据
                $result = $MemberPointOrderModel
                ->where($where)
                ->order($params['order'] ?? $this->Order)
                ->field($params['field'] ?? $this->Field)
                ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
                ->each(function ($item, $key) use($params) {

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
    * @param $where 条件
    * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
    * @return false|mixed
    */
    public function get_join_list($where=[], $params = [])
    {
        $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)

        //查询数据
        $result = $MemberPointOrderModel
            ->alias('a')
            ->join('member b','a.user_id = b.id')
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use($params)  {

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
        public function get_find($where=[],$params=[])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)

            //传入id直接查询
            if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];

            //查询数据
            $item = $MemberPointOrderModel
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
        * @param $where where条件
        * @return void
        */
        public function api_edit_post($params = [],$where=[])
        {
            $result = false;

            //处理共同数据
            

            $result = $this->edit_post($params,$where);//api提交

            return $result;
        }


        /**
        * 后台  编辑&添加
        * @param $model 类
        * @param $params 参数
        * @param $where 更新提交(编辑数据使用)
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
        public function edit_post($params,$where=[])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)


            //查询数据
            if (!empty($params["id"]))  $item = $this->get_find(["id"=>$params["id"]]);
            if (empty($params["id"])&&!empty($where))  $item = $this->get_find($where);



            if (!empty($params["id"])) {
                //如传入id,根据id编辑数据
                $params["update_time"] = time();
                $result                = $MemberPointOrderModel->strict(false)->update($params);
                if($result) $result = $item["id"];
            }elseif(!empty($where)){
                //传入where条件,根据条件更新数据
                $params["update_time"] = time();
                $result                = $MemberPointOrderModel->where($where)->strict(false)->update($params);
                if ($result) $result = $item["id"];
            }else {
                //无更新条件则添加数据
                $params["create_time"] = time();
                $result                = $MemberPointOrderModel->strict(false)->insert($params,true);
            }

            return $result;
        }



        /**
        * 提交(副本,无任何操作) 编辑&添加
        * @param $params
        * @param $where where 条件
        * @return void
        */
        public function edit_post_two($params,$where=[])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)


            //查询数据
            if (!empty($params["id"]))  $item = $this->get_find(["id"=>$params["id"]]);
            if (empty($params["id"])&&!empty($where))  $item = $this->get_find($where);


            if (!empty($params["id"])) {
                //如传入id,根据id编辑数据
                $params["update_time"] = time();
                $result                = $MemberPointOrderModel->strict(false)->update($params);
                if($result) $result = $item["id"];
            } elseif(!empty($where)){
                //传入where条件,根据条件更新数据
                $params["update_time"] = time();
                $result                = $MemberPointOrderModel->where($where)->strict(false)->update($params);
                if ($result) $result = $item["id"];
            }else {
                //无更新条件则添加数据
                $params["create_time"] = time();
                $result                = $MemberPointOrderModel->strict(false)->insert($params,true);
            }

            return $result;
        }


        /**
        * 删除数据 软删除
        * @param $id   传id  int或array都可以
        * @param $type   1软删除 2真实删除
        * @param $params 扩充参数
        * @return void
        */
        public function delete_post($id, $type=1,$params=[])
        {
        $MemberPointOrderModel= new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)


        if ($type == 1) $result = $MemberPointOrderModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $MemberPointOrderModel->destroy($id,true);//真实删除

        return $result;
        }


        /**
        * 后台批量操作
        * @param $id
        * @param $params 修改值
        * @return void
        */
        public function batch_post($id, $params=[])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金  (ps:InitModel)

            $where   = [];
            $where[] = ["id", "in", $id];//$id 为数组


            $params["update_time"] = time();
            $result = $MemberPointOrderModel->where($where)->strict(false)->update($params);//修改状态

            return $result;
        }



        /**
        * 后台  排序
        * @param $list_order 排序
        * @param $params 扩充参数
        * @return void
        */
        public function list_order_post($list_order,$params=[])
        {
            $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金   (ps:InitModel)

            foreach ($list_order as $k => $v) {
                $where   = [];
                $where[] = ["id", "=", $k];
                $result  = $MemberPointOrderModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
            }

            return $result;
        }


}
