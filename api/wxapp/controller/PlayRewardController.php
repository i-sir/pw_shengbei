<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayReward",
 *     "name_underline"          =>"play_reward",
 *     "controller_name"         =>"PlayReward",
 *     "table_name"              =>"play_reward",
 *     "remark"                  =>"打赏管理"
 *     "api_url"                 =>"/api/wxapp/play_reward/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-06-27 17:55:03",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayRewardController();
 *     "test_environment"        =>"https://pw216.aejust.net/api/wxapp/play_reward/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_reward/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayRewardController extends AuthController
{


    public function initialize()
    {
        //打赏管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_reward/index
     * https://pw216.aejust.net/api/wxapp/play_reward/index
     */
    public function index()
    {
        $PlayRewardInit  = new \init\PlayRewardInit();//打赏管理   (ps:InitController)
        $PlayRewardModel = new \initmodel\PlayRewardModel(); //打赏管理   (ps:InitModel)

        $result = [];

        $this->success('打赏管理-接口请求成功', $result);
    }


    /**
     * 打赏管理 列表
     * @OA\Post(
     *     tags={"打赏管理"},
     *     path="/wxapp/play_reward/find_play_reward_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="(选填)关键字搜索",
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
     *   test_environment: https://pw216.aejust.net/api/wxapp/play_reward/find_play_reward_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_reward/find_play_reward_list
     *   api:  /wxapp/play_reward/find_play_reward_list
     *   remark_name: 打赏管理 列表
     *
     */
    public function find_play_reward_list()
    {
        $PlayRewardInit  = new \init\PlayRewardInit();//打赏管理   (ps:InitController)
        $PlayRewardModel = new \initmodel\PlayRewardModel(); //打赏管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayRewardInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 打赏
     * @OA\Post(
     *     tags={"打赏管理"},
     *     path="/wxapp/play_reward/add_order",
     *
     *
     *
     *     @OA\Parameter(
     *         name="reward_id",
     *         in="query",
     *         description="礼物id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="play_user_id",
     *         in="query",
     *         description="陪玩id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Parameter(
     *         name="amount",
     *         in="query",
     *         description="自定义打赏金额",
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
     *   test_environment: https://pw216.aejust.net/api/wxapp/play_reward/add_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_reward/add_order
     *   api:  /wxapp/play_reward/add_order
     *   remark_name: 打赏
     *
     */
    public function add_order()
    {
        $this->checkAuth();

        $PlayRewardLogInit = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardModel   = new \initmodel\PlayRewardModel(); //打赏管理   (ps:InitModel)

        //送礼物平台抽成(%)
        $gift_commission = cmf_config('gift_commission') / 100;

        $params            = $this->request->param();
        $params['user_id'] = $this->user_id;
        $order_num         = $this->get_only_num('play_reward_log');


        //礼物
        if ($params['reward_id']) {
            $map         = [];
            $map[]       = ['id', '=', $params['reward_id']];
            $reward_info = $PlayRewardModel->where($map)->find();
            if (empty($reward_info)) $this->error("打赏信息不存在!");

            //插入数据库
            $params['name']           = $reward_info['name'];
            $params['image']          = cmf_get_asset_url($reward_info['image']);
            $params['price']          = $reward_info['price'];
            $params['amount']         = $reward_info['price'];
            $params['order_num']      = $order_num;
            $params['is_send']        = 1;//支付完成,直接发放给陪玩
            $params['service_charge'] = round($reward_info['price'] * $gift_commission, 2);
            $params['commission']     = round($reward_info['price'] - $params['service_charge'], 2);
        } elseif ($params['amount']) {
            //自定义打赏金额
            $params['name']           = "打赏金额";
            $params['price']          = $params['amount'];
            $params['order_num']      = $order_num;
            $params['is_send']        = 1;//支付完成,直接发放给陪玩
            $params['service_charge'] = round($params['amount'] * $gift_commission, 2);
            $params['commission']     = round($params['amount'] - $params['service_charge'], 2);
        }


        //提交更新
        $result = $PlayRewardLogInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success('下单成功', ['order_num' => $order_num, 'order_type' => 20]);
    }


    /**
     * 支付记录
     * @OA\Post(
     *     tags={"打赏管理"},
     *     path="/wxapp/play_reward/find_order_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="(选填)关键字搜索",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="ym",
     *         in="query",
     *         description="年月",
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
     *   test_environment: https://pw216.aejust.net/api/wxapp/play_reward/find_order_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_reward/find_order_list?openid=olS7D6q9Yz-5xzFJxUpQfuVdgTBQ
     *   api:  /wxapp/play_reward/find_order_list
     *   remark_name: 支付记录
     *
     */
    public function find_order_list()
    {
        $this->checkAuth();
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录  (ps:InitModel)


        $params = $this->request->param();

        // 获取本月第一天
        $firstDayOfMonth = date("{$params['ym']}-01");

        // 获取本月最后一天
        $lastDayOfMonth = date("{$params['ym']}-t");


        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['status', '=', 2];
        $map[] = $this->getBetweenTime($params['begin_time'] ?? $firstDayOfMonth, $params['end_time'] ?? $lastDayOfMonth);


        $result = $PlayRewardLogInit->get_list_paginate($map, $params);
        if (empty($result)) $this->error("暂无数据");


        $this->success('成功', $result);
    }

    /**
     * 计算累计打赏
     * @OA\Post(
     *     tags={"打赏管理"},
     *     path="/wxapp/play_reward/total_amount",
     *
     *
     *
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="(选填)关键字搜索",
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
     *   test_environment: https://pw216.aejust.net/api/wxapp/play_reward/total_amount
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_reward/total_amount?openid=olS7D6q9Yz-5xzFJxUpQfuVdgTBQ
     *   api:  /wxapp/play_reward/total_amount
     *   remark_name: 计算累计打赏
     *
     */
    public function total_amount()
    {
        $this->checkAuth();
        $PlayRewardLogInit  = new \init\PlayRewardLogInit();//打赏记录   (ps:InitController)
        $PlayRewardLogModel = new \initmodel\PlayRewardLogModel(); //打赏记录  (ps:InitModel)

        $params = $this->request->param();

        // 获取本月第一天
        $firstDayOfMonth = date("{$params['ym']}-01");

        // 获取本月最后一天
        $lastDayOfMonth = date("{$params['ym']}-t");


        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['status', '=', 2];
        $map[] = $this->getBetweenTime($params['begin_time'] ?? $firstDayOfMonth, $params['end_time'] ?? $lastDayOfMonth);


        $total_amount = $PlayRewardLogModel->where($map)->sum('amount');


        $this->success('成功', $total_amount);
    }
}
