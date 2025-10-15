<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayEvaluate",
 *     "name_underline"          =>"play_evaluate",
 *     "controller_name"         =>"PlayEvaluate",
 *     "table_name"              =>"play_evaluate",
 *     "remark"                  =>"陪玩评价"
 *     "api_url"                 =>"/api/wxapp/play_evaluate/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-24 17:16:22",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayEvaluateController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_evaluate/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_evaluate/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayEvaluateController extends AuthController
{


    public function initialize()
    {
        //陪玩评价

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_evaluate/index
     * https://pw216.aejust.net/api/wxapp/play_evaluate/index
     */
    public function index()
    {
        $PlayEvaluateInit  = new \init\PlayEvaluateInit();//陪玩评价   (ps:InitController)
        $PlayEvaluateModel = new \initmodel\PlayEvaluateModel(); //陪玩评价   (ps:InitModel)

        $result = [];

        $this->success('陪玩评价-接口请求成功', $result);
    }


    /**
     * 评价列表
     * @OA\Post(
     *     tags={"评价管理"},
     *     path="/wxapp/play_evaluate/find_evaluate_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="pid",
     *         in="query",
     *         description="关联id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="1套餐,2陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="is_me",
     *         in="query",
     *         description="自己的评价",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="header",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_evaluate/find_evaluate_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_evaluate/find_evaluate_list
     *   api:  /wxapp/play_evaluate/find_evaluate_list
     *   remark_name: 陪玩评价 列表
     *
     */
    public function find_evaluate_list()
    {
        $PlayEvaluateInit  = new \init\PlayEvaluateInit();//陪玩评价   (ps:InitController)
        $PlayEvaluateModel = new \initmodel\PlayEvaluateModel(); //陪玩评价   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["pid"]) $where[] = ["pid", "=", $params["pid"]];
        if ($params['is_me']) $where[] = ["user_id", "=", $this->user_id];
        if ($params['is_play']) {
            $where[] = ["pid", "=", $this->user_id];
            $where[] = ["type", "=", 2];
        } elseif ($params["type"]) {
            $where[] = ["type", "=", $params["type"]];
        }

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayEvaluateInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 评价添加
     * @OA\Post(
     *     tags={"评价管理"},
     *     path="/wxapp/play_evaluate/add_evaluate",
     *
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="header",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="evaluate_type1",
     *         in="query",
     *         description="套餐",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="evaluate_type2",
     *         in="query",
     *         description="陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_evaluate/add_evaluate
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_evaluate/add_evaluate
     *   api:  /wxapp/play_evaluate/add_evaluate
     *   remark_name: 陪玩评价添加
     *
     */
    public function add_evaluate()
    {
        //is_commission

        $PlayEvaluateModel     = new \initmodel\PlayEvaluateModel(); //陪玩评价   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $PlayOrderController   = new PlayOrderController();//陪玩端订单管理


        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        $evaluate_type1 = $params['evaluate_type1'];
        $evaluate_type2 = $params['evaluate_type2'];
        $play_user_ids  = $params['play_user_ids'];


        $map10   = [];
        $map10[] = ['order_num', '=', $params['order_num']];

        //检测订单是否已经评价,是否已经发放佣金
        $order_info = $PlayPackageOrderModel->where($map10)->find();
        if ($order_info['is_commission'] == 2) $PlayOrderController->send_commission($order_info['order_num']);//用户评价,如果没有发放佣金进行发放
        if ($order_info['status'] != 50) $this->error("订单未完成,无法评价!");

        //套餐评价
        if ($evaluate_type1) {
            $evaluate_type1['order_num']   = $params['order_num'];
            $evaluate_type1['user_id']     = $params['user_id'];
            $evaluate_type1['create_time'] = time();
            $evaluate_type1['type']        = 1;
            if ($evaluate_type1['images']) $evaluate_type1['images'] = $this->setParams($evaluate_type1['images']);

            $PlayEvaluateModel->strict(false)->insert($evaluate_type1);
        }


        //陪玩评价
        if ($evaluate_type2 && $play_user_ids) {
            foreach ($play_user_ids as $k => $v) {
                $evaluate_type2['pid']         = $v;
                $evaluate_type2['order_num']   = $params['order_num'];
                $evaluate_type2['user_id']     = $params['user_id'];
                $evaluate_type2['create_time'] = time();
                $evaluate_type2['type']        = 2;
                if ($evaluate_type2['images']) $evaluate_type2['images'] = $this->setParams($evaluate_type2['images']);

                $PlayEvaluateModel->strict(false)->insert($evaluate_type2);
            }
        }


        //将订单状态改一下
        $PlayPackageOrderModel->where($map10)->update([
            'status'        => 50,//直接将订单状态改成已完成
            'is_evaluate'   => 2,
            'evaluate_time' => time(),
            'update_time'   => time(),
        ]);

        $this->success('评价成功!');
    }


}
