<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayOrder",
 *     "name_underline"          =>"play_order",
 *     "controller_name"         =>"PlayOrder",
 *     "table_name"              =>"play_order",
 *     "remark"                  =>"陪玩订单"
 *     "api_url"                 =>"/api/wxapp/play_order/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-15 14:19:38",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayPackageOrderController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_order/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_order/index",
 * )
 */


use init\ShopCouponUserInit;
use initmodel\MemberModel;
use plugins\weipay\lib\PayController;
use think\captcha\facade\Captcha;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayOrderController extends AuthController
{

    public $code_key = 'PWKF356893YOPSQW';

    public function initialize()
    {
        //陪玩订单

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_order/index
     * https://pw216.aejust.net/api/wxapp/play_order/index
     */
    public function index()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //陪玩订单   (ps:InitModel)

        $result = [];

        $this->success('陪玩订单-接口请求成功', $result);
    }


    /**
     * 订单列表 & 接单大厅
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/find_order_list",
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
     *         name="is_receive",
     *         in="query",
     *         description="true 接单大厅",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态:1000全部,30待服务,40服务中,60审核中,50已完成,10已取消",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/find_order_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/find_order_list?is_receive=true
     *   api:  /wxapp/play_order/find_order_list
     *   remark_name: 陪玩订单 列表
     *
     */
    public function find_order_list()
    {
        $this->checkAuth(3);

        //Log::write("接单大厅请求-----" . round(microtime(true) * 1000) . '-----' . $this->openid);
        //Log::write("接单大厅请求-----" . time() . '-----' . $this->openid);
        //Log::write("接单大厅请求-----" . get_client_ip() . '-----' . $this->openid);


        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单   (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //陪玩订单   (ps:InitModel)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)


        //时间设个10s之后开放接单
        $reception_hall_seconds = $this->user_info['reception_hall_seconds'] ?? cmf_config('reception_hall_seconds');

        //参数
        $params            = $this->request->param();
        $params['user_id'] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["order_num|package_id|map|paly_name|code|area|wx|remark", "like", "%{$params['keyword']}%"];
        if ($params["is_receive"]) {
            $where[] = ["status", "in", [20, 25]];
            //匹配,性别,匹配对应等级
            $where[] = ['gender', 'in', [0, $this->user_info['gender']]];
            $where[] = ['level_id', 'in', [0, $this->user_info['level_id']]];
            //必须筛选区服
            //$where[] = ['area', 'in', $this->getParams($this->user_info['area'])];

            //去除下已经接单的列表.
            $map10   = [];
            $map10[] = ['user_id', '=', $this->user_id];
            $map10[] = ['status', 'in', [30]];//待服务

            $list           = $PlayUserOrderModel->where($map10)->group('order_num')->select();
            $order_num_list = [];
            foreach ($list as $k => $v) {
                $order_num_list[] = $v['order_num'];
            }
            if ($order_num_list) $where[] = ['order_num', 'not in', $order_num_list];
            $where[] = ['class_two_id', 'in', $this->getParams($this->user_info['play_ids'])];//通知已添加该游戏分类的陪玩


            //接单大厅10秒后才可以接单
            $where[]         = ['pay_time', '<', time() - $reception_hall_seconds];
            $params["order"] = "id";//排序
        }

        if ($params["status"]) {
            $where[] = ["where_play_user_ids", "like", "%/{$this->user_id}/%"];
            $where[] = ["status", "in", $PlayPackageOrderInit->status_play_where[$params["status"]]];
        }


        //已取消订单
        if ($params["status"] == 10) {
            unset($where);
            $map            = [];
            $map[]          = ['user_id', '=', $this->user_id];
            $map[]          = ['status', '=', $params["status"]];
            $list           = $PlayUserOrderModel->where($map)->group('order_num')->select();
            $order_num_list = [];
            foreach ($list as $k => $v) {
                $order_num_list[] = $v['order_num'];
            }
            $where   = [];
            $where[] = ['order_num', 'in', $order_num_list];
        }

        //dump($where);exit();


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["page_size"]     = 20;//分页数量限制
        $result                  = $PlayPackageOrderInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");


        if ($params['is_receive']) {
            //记录陪玩最新的订单id
            $max_id = $PlayPackageOrderModel->where($where)->max('id');
            Cache::set("playUserMaxId" . $this->user_id, $max_id);
        }


        $this->success("请求成功!", $result);
    }


    /**
     * 检测是否有新订单
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/is_new_order",
     *
     *
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/is_new_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/is_new_order
     *   api:  /wxapp/play_order/is_new_order
     *   remark_name: 新订单
     *
     */
    public function is_new_order()
    {
        $this->checkAuth(3);

        //Log::write("检测是否有新订单-----" . round(microtime(true) * 1000) . '-----' . $this->openid);
        //        Log::write("检测是否有新订单-----" . time() . '-----' . $this->openid);

        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //陪玩订单   (ps:InitModel)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)

        //时间设个10s之后开放接单
        $reception_hall_seconds = cmf_config('reception_hall_seconds');

        $old_max_id = Cache::get("playUserMaxId" . $this->user_id);


        //参数
        $params            = $this->request->param();
        $params['user_id'] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];


        $where[] = ["status", "in", [20, 25]];
        //匹配,性别,匹配对应等级
        $where[] = ['gender', 'in', [0, $this->user_info['gender']]];
        $where[] = ['level_id', 'in', [0, $this->user_info['level_id']]];
        //必须筛选区服
        //$where[] = ['area', 'in', $this->getParams($this->user_info['area'])];

        //去除下已经接单的列表.
        $map10          = [];
        $map10[]        = ['user_id', '=', $this->user_id];
        $map10[]        = ['status', 'in', [30]];//待服务
        $list           = $PlayUserOrderModel->where($map10)->group('order_num')->select();
        $order_num_list = [];
        foreach ($list as $k => $v) {
            $order_num_list[] = $v['order_num'];
        }
        if ($order_num_list) $where[] = ['order_num', 'not in', $order_num_list];
        $where[] = ['class_two_id', 'in', $this->getParams($this->user_info['play_ids'])];//通知已添加该游戏分类的陪玩
        $where[] = ['pay_time', '<', time() - $reception_hall_seconds]; //接单大厅10秒后才可以接单

        $max_id = $PlayPackageOrderModel->where($where)->max('id');
        if ($max_id) Cache::set("playUserMaxId" . $this->user_id, $max_id);


        $new_order = false;
        if ($old_max_id < $max_id) $new_order = true;     //有新订单


        $this->success('请求成功', $new_order);
    }


    /**
     * 陪玩订单详情
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/find_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
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
     *         name="order_num",
     *         in="query",
     *         description="order_num 二选一",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/find_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/find_order
     *   api:  /wxapp/play_order/find_order
     *   remark_name: 陪玩订单 详情
     *
     */
    public function find_order()
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //陪玩订单   (ps:InitModel)

        //参数
        $params = $this->request->param();


        //查询条件
        $where = [];
        if ($params["id"]) $where[] = ["id", "=", $params["id"]];
        if ($params["order_num"]) $where[] = ["order_num", "=", $params["order_num"]];


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayPackageOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 接单
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/receive_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="receive_type",
     *         in="query",
     *         description="接单类型:1个人,2团队",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/receive_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/receive_order
     *   api:  /wxapp/play_order/receive_order
     *   remark_name: 接单
     *
     */
    public function receive_order()
    {
        // 启动事务
        Db::startTrans();


        $this->checkAuth(2);

        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderInit     = new \init\PlayUserOrderInit();//陪玩管理   (ps:InitController)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params = $this->request->param();


        Log::write("接单-----" . round(microtime(true) * 1000) . '-----' . $this->openid);
        //        Log::write("接单-----" . time() . '-----' . $this->openid);


        //获取key
        //        $key        = cache($this->code_key . $params['only_code_id']);
        //        $check_code = password_verify(mb_strtolower($params['check_code'], 'UTF-8'), $key);
        //        if (!$check_code) $this->error('验证码错误!');

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (!in_array($order_info['status'], [20, 25])) $this->error('非法操作!');

        //接单时候必须大于设定的保证金金额才可以接单
        $minimum_deposit = cmf_config('minimum_deposit');
        if ($this->user_info['point'] < $minimum_deposit) $this->error('保证金余额不足,请充值!');

        //判断陪玩是否有正在进行的订单
        if ($this->user_id) {
            //1.是否离线
            //if ($this->user_info['is_line'] == 2) $this->error('您已离线无法接单!');


            //2.是否忙碌
            $map20    = [];
            $map20[]  = ['where_play_user_ids', 'like', "%/{$this->user_id}/%"];
            $map20[]  = ['status', 'in', [30, 40]];//服务中,待服务和不可以接单
            $is_order = $PlayPackageOrderModel->where($map20)->count();
            if ($is_order) $this->error('您有未完成的订单!');


        }


        //如果个人接单,就无法团队接单了
        if ($order_info['play_user_id'] && $order_info['receive_type'] != $params['receive_type']) $this->error('非法接单!');

        //检测陪玩是否已接单
        $map      = [];
        $map[]    = ['order_num', '=', $order_info['order_num']];
        $map[]    = ['user_id', '=', $this->user_id];
        $is_order = $PlayUserOrderModel->where($map)->count();
        if ($is_order) $this->error('请勿重复接单!');


        //添加到陪玩记录
        $insert['user_id']   = $this->user_id;
        $insert['order_num'] = $order_info['order_num'];
        $insert['status']    = 30;//默认未取消
        $PlayUserOrderInit->api_edit_post($insert);

        //如果个人接单,订单状态进入中间状态
        $params['receive_time'] = time();
        $params['status']       = 25;


        //检测陪玩是否足够了,订单状态改一下
        $map10      = [];
        $map10[]    = ['order_num', '=', $order_info['order_num']];
        $map10[]    = ['status', 'in', [30]];
        $play_count = $PlayUserOrderModel->where($map10)->count();
        //订单类型为套餐,满足在范围人数内,就可以开始订单
        if ($order_info['order_type'] == 1 && $play_count >= $order_info['min_number']) {
            $params['is_begin'] = 1;//可以开始服务
        }

        //陪玩下单,必须满足指定人数
        if ($order_info['order_type'] == 2 && $play_count >= $order_info['play_number']) {
            $params['status']       = 30;
            $params['is_begin']     = 1;//可以开始服务
            $params['receive_time'] = time();
        }

        //团队接单,直接将订单状态改下
        if ($params['receive_type'] == 2) {
            $params['status'] = 30;
        }

        //个人接单也直接,将订单状态改下,直接可以开始服务
        if ($params['receive_type'] == 1) {
            $params['status']   = 30;
            $params['is_begin'] = 1;//可以开始服务
        }

        //不管套餐还是团队,如果接单人超过最大人数,订单直接改为30
        if ($play_count >= $order_info['play_number']) $params['status'] = 30;

        if (empty($order_info['play_user_id'])) {
            //订单信息
            $params['play_user_id']        = $this->user_id;
            $params['where_play_user_ids'] = "/{$this->user_id}/";
        } else {
            $params['where_play_user_ids'] = "{$order_info['where_play_user_ids']},/{$this->user_id}/";
        }


        //更改订单信息
        unset($params["user_id"]);
        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        $this->update_commission($order_info['order_num']);

        // 提交事务
        Db::commit();


        $this->success("接单成功");
    }

    //获取验证码 https://pw216.aejust.net/api/wxapp/play_order/verify
    public function verify()
    {
        $uniqid = md5(cmf_random_string(30, 3));
        $rs     = Captcha::create();

        $base64_image = "data:image/png;base64," . base64_encode($rs->getData());
        $key          = session('captcha.key');

        cache($this->code_key . $uniqid, $key);//PWKF356893YOPSQW 这里在加密一下
        $this->success('请求成功', ['warn_illegality' => $uniqid, 'image' => $base64_image, 'warn' => '禁止使用脚本,如发现将追究法律责任!']);
    }


    //验证码验证
    // https://pw216.aejust.net/api/wxapp/play_order/check?code=fp2hb&uniqid=###bd067e6e3610345bbc7a972f249328ed
    public function check()
    {
        $params = $this->request->param();
        //获取key
        $key = cache($this->code_key . $params['only_code_id']);

        $aaa = password_verify(mb_strtolower($params['check_code'], 'UTF-8'), $key);
        dump($aaa);//true  或  false
        exit();
    }


    /**
     * 开始服务
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/begin_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="query",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/begin_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/begin_order
     *   api:  /wxapp/play_order/begin_order
     *   remark_name: 开始服务
     *
     */
    public function begin_order()
    {
        $this->checkAuth(3);

        $PlayPackageOrderInit       = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayPackageOrderController = new PlayPackageOrderController();
        $PlayPackageModel           = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)
        $PlayUserOrderModel         = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)

        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        //必须待服务中才可以开始服务
        if (!in_array($order_info['status'], [25, 30])) $this->error('非法操作!');


        //检测陪玩人数是否在设定范围内,否则不可以开始服务
        if ($order_info['order_type'] == 1) {
            $map              = [];
            $map[]            = ['id', '=', $order_info['package_id']];
            $package_info     = $PlayPackageModel->where($map)->find();
            $play_number_list = range($package_info['number'], $package_info['number_max']);//必须在规定人数内

            //当前陪玩人数  &&  检测陪玩是否已接单
            $map10       = [];
            $map10[]     = ['order_num', '=', $order_info['order_num']];
            $map10[]     = ['status', '=', 30];
            $user_number = $PlayUserOrderModel->where($map10)->count();
            if (!in_array($user_number, $play_number_list) && $order_info['receive_type'] == 2) $this->error('陪玩人数不在规定范围内,请等待');
        }

        //陪玩接单,必须满足制定人数   && 不需要判断人数
        if ($order_info['order_type'] == 2) {
            //当前陪玩人数  &&  检测陪玩是否已接单
            $map20       = [];
            $map20[]     = ['order_num', '=', $order_info['order_num']];
            $map20[]     = ['status', '=', 30];
            $user_number = $PlayUserOrderModel->where($map20)->count();
            //if ($order_info['play_number'] > $user_number) $this->error('陪玩人数不在规定范围内,请等待');
        }


        $params['status']       = 40;
        $params['service_time'] = time();
        $params['update_time']  = time();


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        //发送消息通知用户
        $PlayPackageOrderController->send_msg2($order_info);


        $this->success("操作成功");
    }


    /**
     * 团队接单增加陪玩
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/add_paly_user",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="陪玩id 一次一个人",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/add_paly_user
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/add_paly_user
     *   api:  /wxapp/play_order/add_paly_user
     *   remark_name: 团队接单,增加陪玩
     *
     */
    public function add_paly_user()
    {
        $this->checkAuth(3);

        $PlayPackageOrderInit = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderInit    = new \init\PlayUserOrderInit();//陪玩管理   (ps:InitController)
        $PlayUserOrderModel   = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)

        //参数
        $params = $this->request->param();
        if (empty($params['user_id'])) $this->error('请选择陪玩');

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (!in_array($order_info['status'], [30, 40])) $this->error('非法操作!');


        //订单的陪玩人数
        $map20       = [];
        $map20[]     = ['order_num', '=', $order_info['order_num']];
        $map20[]     = ['status', '=', 30];
        $order_count = $PlayUserOrderModel->where($map20)->count();
        if ($order_count >= $order_info['play_number']) $this->error('陪玩人数已上限!');


        //检测陪玩是否已接单
        $map      = [];
        $map[]    = ['order_num', '=', $order_info['order_num']];
        $map[]    = ['user_id', '=', $params['user_id']];
        $map[]    = ['status', '=', 30];
        $is_order = $PlayUserOrderModel->where($map)->count();
        if ($is_order) $this->error('请勿重复接单!');


        //添加到陪玩记录
        $insert['user_id']   = $params['user_id'];
        $insert['order_num'] = $order_info['order_num'];
        $insert['status']    = 30;//默认未取消
        $PlayUserOrderInit->api_edit_post($insert);


        //陪玩id
        $params['where_play_user_ids'] = "{$order_info['where_play_user_ids']},/{$params['user_id']}/";


        //检测陪玩是否足够了,订单状态改一下
        $map10      = [];
        $map10[]    = ['order_num', '=', $order_info['order_num']];
        $map10[]    = ['status', 'in', [30]];
        $play_count = $PlayUserOrderModel->where($map10)->count();
        //陪玩单子,人数够了,状态待接单,将订单状态改下  如果资源单手动开始
        if ($order_info['order_type'] == 2 && ($play_count) >= $order_info['play_number'] && $order_info['status'] == 30) {
            $params['status']       = 40;
            $params['service_time'] = time();//服务中
        }

        //如果套餐订单,人数在规定范围内,开始服务按钮出现
        if ($order_info['order_type'] == 1 && $play_count >= $order_info['min_number']) $params['is_begin'] = 1;


        //编辑
        unset($params["user_id"]);
        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        // 提交事务
        Db::commit();


        $this->success("添加成功!");
    }


    /**
     * 订单陪玩列表
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/order_user_list",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 订单id",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/order_user_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/order_user_list
     *   api:  /wxapp/play_order/order_user_list
     *   remark_name: 订单陪玩列表
     *
     */
    public function order_user_list()
    {
        $PlayPackageOrderInit = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderModel   = new \initmodel\PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $MemberPlayInit       = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)


        //参数
        $params = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //订单详情
        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        //陪玩列表
        $map       = [];
        $map[]     = ['order_num', '=', $order_info['order_num']];
        $map[]     = ['status', 'in', [30]];
        $map[]     = ['user_id', '<>', $order_info['play_user_id']];
        $user_list = $PlayUserOrderModel->where($map)->select();
        //陪玩id
        $user_ids = [];
        foreach ($user_list as $k => $v) {
            $user_ids[] = $v['user_id'];
        }

        //陪玩列表
        $map10   = [];
        $map10[] = ['id', 'in', $user_ids];
        $result  = $MemberPlayInit->get_list($map10);


        if (empty($result)) $this->error('暂无数据!');

        $this->success('请求成功!', $result);
    }


    /**
     * 修改更改陪玩
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/update_order_user",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 订单id",
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
     *         name="user_id",
     *         in="query",
     *         description="替换新陪玩的id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="old_user_id",
     *         in="query",
     *         description="所要替换的陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="is_user",
     *         in="query",
     *         description="true,老板更改陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/update_order_user
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/update_order_user
     *   api:  /wxapp/play_order/update_order_user
     *   remark_name: 修改更换陪玩
     *
     */
    public function update_order_user()
    {
        $this->error('非法请求');
        $this->checkAuth(3);

        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $MemberPlayModel       = new \initmodel\MemberPlayModel();//陪玩管理
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        //如果已经开始服务不可以操作了
        if (!in_array($order_info['status'], [30, 40])) $this->error('非法操作!');
        if (empty($params['is_user']) && $order_info['play_user_id'] == $params['old_user_id']) $this->error('主陪玩不能更换!');


        //检测旧陪玩是否存在
        $map        = [];
        $map[]      = ['order_num', '=', $order_info['order_num']];
        $map[]      = ['status', '=', 30];
        $map[]      = ['user_id', '=', $params['old_user_id']];
        $user_order = $PlayUserOrderModel->where($map)->find();
        if (empty($user_order)) $this->error('更换的陪玩信息不存在!!');


        //检测所要修改的陪玩是否在队伍中
        $map2        = [];
        $map2[]      = ['order_num', '=', $order_info['order_num']];
        $map2[]      = ['status', '=', 30];
        $map2[]      = ['user_id', '=', $params['user_id']];
        $user_order2 = $PlayUserOrderModel->where($map2)->find();
        if ($user_order2) $this->error('陪玩已存在!!');


        //查询其他陪玩id,不查询旧陪玩
        $map10           = [];
        $map10[]         = ['order_num', '=', $order_info['order_num']];
        $map10[]         = ['status', 'in', [30]];
        $map10[]         = ['user_id', '<>', $params['old_user_id']];
        $user_order_list = $PlayUserOrderModel->where($map10)->select();
        $user_ids        = '';//更新陪玩id
        foreach ($user_order_list as $k => $v) {
            $user_ids .= "/{$v['user_id']}/,";
        }


        //更改订单,陪玩id,将修改的陪玩加上去
        $user_ids .= "/{$params['user_id']}/";
        //用户可以换主陪玩
        if ($params['is_user']) {
            $play_user_id = $params['user_id'];
            $PlayPackageOrderModel->where($where)->strict(false)->update([
                'where_play_user_ids' => $user_ids,
                'play_user_id'        => $play_user_id,
            ]);
        } else {
            $PlayPackageOrderModel->where($where)->strict(false)->strict(false)->update(['where_play_user_ids' => $user_ids]);
        }


        //更改陪玩已取消状态
        $map20   = [];
        $map20[] = ['order_num', '=', $order_info['order_num']];
        $map20[] = ['status', '=', 30];
        $map20[] = ['user_id', '=', $params['old_user_id']];
        $PlayUserOrderModel->where($map20)->strict(false)->update([
            'remark'      => "替换陪玩ID:{$params['user_id']};修改时间:" . date("Y-m-d H:i:s"),//换人备注
            'status'      => 10,//已取消
            'cancel_time' => time(),
            'update_time' => time()
        ]);


        //将新的陪玩添加到记录
        $PlayUserOrderModel->strict(false)->insert([
            'user_id'     => $params['user_id'],
            'order_num'   => $order_info['order_num'],
            'create_time' => time()
        ]);


        //编辑
        unset($params["user_id"]);
        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        $this->success("更换成功");
    }


    /**
     * 删除陪玩
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/delete_order_user",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 订单id",
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
     *         name="user_id",
     *         in="query",
     *         description="删除陪玩的id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/delete_order_user
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/delete_order_user
     *   api:  /wxapp/play_order/delete_order_user
     *   remark_name: 删除陪玩
     *
     */
    public function delete_order_user()
    {
        $this->checkAuth(3);

        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');


        //如果已经开始服务不可以操作了
        if (!in_array($order_info['status'], [30, 40])) $this->error('非法操作!');
        if ($order_info['play_user_id'] == $params['user_id']) $this->error('主陪玩不能被删除!');


        //查询其他陪玩id,不查询旧陪玩
        $map10           = [];
        $map10[]         = ['order_num', '=', $order_info['order_num']];
        $map10[]         = ['status', 'in', [30]];
        $map10[]         = ['user_id', '<>', $params['user_id']];
        $user_order_list = $PlayUserOrderModel->where($map10)->select();
        $user_ids        = '';//更新陪玩id
        foreach ($user_order_list as $k => $v) {
            $user_ids .= "/{$v['user_id']}/,";
        }


        $PlayPackageOrderModel->where($where)->strict(false)->update([
            'where_play_user_ids' => $user_ids,
            'update_time'         => time(),
        ]);


        //删除陪玩,状态改为已取消
        $map20   = [];
        $map20[] = ['order_num', '=', $order_info['order_num']];
        $map20[] = ['status', '=', 30];
        $map20[] = ['user_id', '=', $params['user_id']];
        $PlayUserOrderModel->where($map20)->strict(false)->update([
            'remark'      => "删除陪玩ID:{$params['user_id']};删除时间:" . date("Y-m-d H:i:s"),//换人备注
            'status'      => 10,
            'cancel_time' => time(),
            'update_time' => time()
        ]);


        //编辑
        //        unset($params["user_id"]);
        //        dump($params);exit();
        //        $result = $PlayPackageOrderInit->edit_post_two($params);
        //        if (empty($result)) $this->error("失败请重试!");


        $this->success("删除成功");
    }


    /**
     * 切换主陪玩
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/main_order_user",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 订单id",
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
     *         name="user_id",
     *         in="query",
     *         description="切换主陪玩的id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/main_order_user
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/main_order_user
     *   api:  /wxapp/play_order/main_order_user
     *   remark_name: 切换主陪玩
     *
     */
    public function main_order_user()
    {
        $this->checkAuth(3);

        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        $result = $PlayPackageOrderModel->where($where)->update([
            'play_user_id' => $params['user_id'],
            'update_time'  => time(),
        ]);
        if (empty($result)) $this->error("失败请重试!");


        $this->success("更换成功");
    }


    /**
     * 陪玩取消接单
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/cancel_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="query",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/cancel_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/cancel_order
     *   api:  /wxapp/play_order/cancel_order
     *   remark_name: 取消订单
     *
     */
    public function cancel_order()
    {
        // 启动事务
        Db::startTrans();

        $this->checkAuth(2);
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $MemberPlayModel       = new \initmodel\MemberPlayModel();//陪玩管理
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $ShopCouponUserInit    = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)
        $Pay                   = new PayController();


        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (!in_array($order_info['status'], [20, 30])) $this->error('非法操作!');     //如果已经开始服务不可以操作了


        //团队接单,或者普通接单,第一人不可以取消
        if ($order_info['play_user_id'] == $this->user_id) {
            //主陪玩取消订单,该订单直接被取消,给用户钱退了
            $params['status'] = 20;


            //指定陪玩将订单取消
            if ($order_info['play_type'] == 1) {
                //如使用优惠,将优惠券返回
                if ($order_info['coupon_id']) $ShopCouponUserInit->cancel_use_coupon($order_info['coupon_id']);
                //退款    &  支付方式:1微信支付,2余额支付
                if ($order_info['pay_type'] == 1) {
                    //获取支付单号
                    $map            = [];
                    $map[]          = ['order_num', '=', $order_info['order_num']];//实际订单号
                    $map[]          = ['status', '=', 2];//已支付
                    $pay_info       = Db::name('order_pay')->where($map)->find();//支付记录表
                    $amount         = $pay_info['amount'];//支付金额&全部退款
                    $order_num      = $pay_info['pay_num'];//支付单号
                    $transaction_id = $pay_info['trade_num'];//第三方单号


                    $refund_result = $Pay->wx_pay_refund($transaction_id, $order_num, $order_info['amount']);
                    $refund_result = $refund_result['data'];
                    if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
                } else {
                    $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
                    $remark            = "操作人[{$admin_id_and_name}];操作说明[同意退款订单:{$order_info['order_num']};金额:{$order_info['balance']}];操作类型[陪玩取消订单];";//管理备注
                    MemberModel::inc_balance($order_info['user_id'], $order_info['balance'], '陪玩取消订单', $remark, $order_info['id'], $order_info['order_num'], 200);
                }

                $params['status']      = 10;
                $params['cancel_time'] = time();
            }


            //如果团队接单,所有人都扣钱,订单回退大厅
            $map100         = [];
            $map100[]       = ['order_num', '=', $order_info['order_num']];
            $map100[]       = ['status', '=', 30];
            $play_user_list = $PlayUserOrderModel->where($map100)->select();


            //扣除陪玩余额
            $remark       = "操作人[{$this->user_info['nickname']}];操作说明[陪玩取消订单];操作类型[扣除陪玩余额];";//管理备注
            $not_user_ids = [];

            //取消订单是否扣除押金
            $cancellation_order = cmf_config('cancellation_order');
            //打手取消订单扣除押金
            $cancel_order_deduct = cmf_config('cancel_order_deduct');
            foreach ($play_user_list as $k => $v) {

                //扣除押金
                if ($cancellation_order == 'true') {
                    $MemberPlayModel->dec_point($v['user_id'], $cancel_order_deduct, '陪玩取消订单', $remark, $order_info['id'], $order_info['order_num'], 50);
                }

                $not_user_ids[] = $v['user_id'];//不在通知这几个人
            }


            //陪玩取消状态
            $map20   = [];
            $map20[] = ['order_num', '=', $order_info['order_num']];
            $map20[] = ['status', '=', 30];
            $PlayUserOrderModel->where($map20)->update([
                'remark'      => "陪玩取消接单:{$this->user_id};操作时间:" . date("Y-m-d H:i:s"),//换人备注
                'status'      => 10,
                'cancel_time' => time(),
                'update_time' => time()
            ]);


            //清空
            $params['play_user_id']        = null;
            $params['where_play_user_ids'] = null;


            $result = $PlayPackageOrderInit->edit_post_two($params);
            if (empty($result)) $this->error("失败请重试!");

            //需要通知的陪玩  &&  不在通知
            //            $map30   = [];
            //            $map30[] = ['id', '>', 0];
            //            $map30[] = ['is_line', '=', 1];//只通知已在线陪玩
            //            $map30[] = ['is_block', '=', 2];//正常状态
            //            if ($not_user_ids) $map20[] = ['id', 'not in', $not_user_ids];//上方通知过不再进行通知
            //            $map30[]       = ['', 'EXP', Db::raw("FIND_IN_SET({$order_info['class_two_id']},play_ids)")];//通知已添加该游戏分类的陪玩
            //            $play_user_ids = $MemberPlayModel->where($map30)->field('id,wx_openid')->orderRaw('rand()')->select();//随机查询陪玩
            //            foreach ($play_user_ids as $k => $v) {
            //                Db::name('play_send')->strict(false)->insert([
            //                    'user_id'     => $v['id'],
            //                    'wx_openid'   => $v['wx_openid'],
            //                    'order_num'   => $order_info['order_num'],
            //                    'create_time' => time()
            //                ]);
            //            }

            // 提交事务
            Db::commit();


            $this->success("取消成功");
        }


        //将该陪玩状态改了,订单那边id删除
        $map        = [];
        $map[]      = ['order_num', '=', $order_info['order_num']];
        $map[]      = ['status', '=', 30];
        $map[]      = ['user_id', '=', $this->user_id];
        $user_order = $PlayUserOrderModel->where($map)->find();
        if (empty($user_order)) $this->error('非法操作!!');


        //查询其他陪玩id,更新陪玩id记录字段
        $map10           = [];
        $map10[]         = ['order_num', '=', $order_info['order_num']];
        $map10[]         = ['status', '=', 30];
        $map10[]         = ['user_id', '<>', $this->user_id];
        $user_order_list = $PlayUserOrderModel->where($map10)->select();
        $user_ids        = '';//更新陪玩id
        foreach ($user_order_list as $k => $v) {
            $user_ids .= "/{$v['user_id']}/,";
        }


        //更改订单,陪玩id
        $PlayPackageOrderModel->where($where)->update(['where_play_user_ids' => $user_ids]);


        //更改陪玩已取消状态
        $map20   = [];
        $map20[] = ['order_num', '=', $order_info['order_num']];
        $map20[] = ['status', '=', 30];
        $map20[] = ['user_id', '=', $this->user_id];
        $PlayUserOrderModel->where($map20)->update([
            'status'      => 10,
            'cancel_time' => time(),
            'update_time' => time()
        ]);


        //取消订单是否扣除押金
        $cancellation_order = cmf_config('cancellation_order');
        //打手取消订单扣除押金
        $cancel_order_deduct = cmf_config('cancel_order_deduct');
        //扣除押金
        if ($cancellation_order == 'true') {
            $MemberPlayModel->dec_point($this->user_id, $cancel_order_deduct, '陪玩取消订单', $remark, $order_info['id'], $order_info['order_num'], 50);
        }

        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        // 提交事务
        Db::commit();


        $this->success("取消成功");
    }


    /**
     * 完成订单,如状态为62可以再次编辑,62后台不通过
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/accomplish_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="accomplish_images",
     *         in="query",
     *         description="完成图片  数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="accomplish_text",
     *         in="query",
     *         description="完成 描述文字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="query",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/accomplish_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/accomplish_order
     *   api:  /wxapp/play_order/accomplish_order
     *   remark_name: 完成订单
     *
     */
    public function accomplish_order()
    {
        $this->checkAuth(3);

        $PlayPackageOrderInit       = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayPackageOrderController = new PlayPackageOrderController();

        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        //状态必须是服务中,已驳回才可以操作
        if (!in_array($order_info['status'], [40, 62])) $this->error('非法操作!');


        //打手提交平台审核之后，系统n分钟之后自动确认
        $automatic_review = cmf_config('automatic_review');


        $params['status']               = 60;//陪玩完成,后台审核
        $params['update_time']          = time();
        $params['accomplish_time']      = time();
        $params['auto_accomplish_time'] = time() + ($automatic_review * 60);
        if ($params['accomplish_images']) $params['accomplish_images'] = $this->setParams($params['accomplish_images']);

        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        //发送消息通知用户
        $PlayPackageOrderController->send_msg3($order_info);


        $this->success("操作成功!");
    }


    /**
     * 更新截图
     * @OA\Post(
     *     tags={"陪玩订单"},
     *     path="/wxapp/play_order/accomplish_images",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="accomplish_images",
     *         in="query",
     *         description="完成图片  数组",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_order/accomplish_images
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_order/accomplish_images
     *   api:  /wxapp/play_order/accomplish_images
     *   remark_name: 更新截图
     *
     */
    public function accomplish_images()
    {

        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)

        //参数
        $params = $this->request->param();


        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderModel->where('id', '=', $params['id'])->find();
        if (empty($order_info)) $this->error('订单信息错误!');

        //追加截图
        if ($params['accomplish_images']) {
            $params['accomplish_images'] = $this->setParams($params['accomplish_images']);
            $params['accomplish_images'] = $order_info['accomplish_images'] . ',' . $params['accomplish_images'];
        }


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        $this->success("操作成功!");
    }


    /**
     * 更新订单佣金
     * @param $order_num
     */
    public function update_commission($order_num)
    {
        $PlayPackageOrderInit = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderModel   = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $MemberLevelModel     = new \initmodel\MemberLevelModel(); //陪玩等级   (ps:InitModel)
        $MemberPlayModel      = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageModel     = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)


        //查询条件
        $where   = [];
        $where[] = ["order_num", "=", $order_num];


        //订单详情
        $order_info = $PlayPackageOrderInit->get_find($where);

        $map             = [];
        $map[]           = ['order_num', '=', $order_num];
        $map[]           = ['status', '=', 30];
        $order_user_list = $PlayUserOrderModel->where($map)->select();


        //陪玩人数计算一下,套餐的话没有人数
        $play_number = count($order_user_list);

        foreach ($order_user_list as $k => $v) {
            //得出陪玩等级
            $user_info = $MemberPlayModel->where('id', '=', $v['user_id'])->find();

            //算出陪玩佣金比例
            $level_info = $MemberLevelModel->where('id', '=', $user_info['level_id'])->find();

            //套餐信息
            $package_info = $PlayPackageModel->where('id', '=', $order_info['package_id'])->find();//套餐信息

            //平台佣金
            $platform_commission = 0;
            if ($order_info['area'] == '微信') $platform_commission = $package_info['wx_platform_commission'];
            if ($order_info['area'] == 'QQ') $platform_commission = $package_info['qq_platform_commission'];

            if ($order_info['order_type'] == 1 && $platform_commission > 0) {
                //平台抽成,剩余直接平分佣金
                $amount     = round($order_info['total_amount'] - ($order_info['total_amount'] * ($platform_commission / 100)), 2);
                $commission = round($amount / $play_number, 2);
            } else {
                //订单金额 , 优惠券金额 平台承担 (扣除下平台抽成)
                $amount = $order_info['total_amount'];
                //等级 (100/3)*对应等级比例
                //$commission = ($amount - ($amount * ($level_info['commission'] / 100))) / $play_number;
                $commission = ($amount / $play_number) * ($level_info['commission'] / 100);
                $commission = round($commission, 2);
            }


            //更新佣金信息,发放时间
            $PlayUserOrderModel->where('id', '=', $v['id'])->update([
                'commission_amount' => $commission,
                'update_time'       => time(),
            ]);
        }

    }


    /**
     * 订单完成发放佣金
     * @param $order_num
     */
    public function send_commission($order_num)
    {
        $PlayPackageOrderInit  = new \init\PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayUserOrderModel    = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)
        $MemberLevelModel      = new \initmodel\MemberLevelModel(); //陪玩等级   (ps:InitModel)
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单  (ps:InitModel)
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $PlayPackageModel      = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)


        //佣金是否开启  开启[1] 关闭[2]
        $invitation_commission_activation = cmf_config('invitation_commission_activation');

        //邀请好友得订单佣金(%)
        $invitation_commission_order = cmf_config('invitation_commission_order') / 100;

        //查询条件
        $where   = [];
        $where[] = ["order_num", "=", $order_num];


        //订单详情
        $order_info = $PlayPackageOrderInit->get_find($where);

        $map             = [];
        $map[]           = ['order_num', '=', $order_num];
        $map[]           = ['status', '=', 30];
        $order_user_list = $PlayUserOrderModel->where($map)->select();


        //陪玩人数计算一下,套餐的话没有人数
        $play_number = count($order_user_list);

        foreach ($order_user_list as $k => $v) {
            //得出陪玩等级
            $user_info = $MemberPlayModel->where('id', '=', $v['user_id'])->find();

            //算出陪玩佣金比例
            $level_info = $MemberLevelModel->where('id', '=', $user_info['level_id'])->find();

            //套餐信息
            $package_info = $PlayPackageModel->where('id', '=', $order_info['package_id'])->find();//套餐信息

            //平台佣金
            $platform_commission = 0;
            if ($order_info['area'] == '微信') $platform_commission = $package_info['wx_platform_commission'];
            if ($order_info['area'] == 'QQ') $platform_commission = $package_info['qq_platform_commission'];

            if ($order_info['order_type'] == 1 && $platform_commission > 0) {
                //平台抽成,剩余直接平分佣金
                $amount     = round($order_info['total_amount'] - ($order_info['total_amount'] * ($platform_commission / 100)), 2);
                $commission = round($amount / $play_number, 2);
            } else {
                //订单金额 , 优惠券金额 平台承担 (扣除下平台抽成)
                $amount = $order_info['total_amount'];
                //等级 (100/3)*对应等级比例
                //$commission = ($amount - ($amount * ($level_info['commission'] / 100))) / $play_number;
                $commission = ($amount / $play_number) * ($level_info['commission'] / 100);
                $commission = round($commission, 2);
            }


            //更新佣金信息,发放时间
            $PlayUserOrderModel->where('id', '=', $v['id'])->update([
                'commission_amount' => $commission,
                'send_time'         => time(),
            ]);


            //发放佣金
            if ($commission) {
                $remark = "操作人[订单完成];操作说明[订单已完成-{$order_info['order_num']}];操作类型[订单完成陪玩增加余额];";//管理备注
                if ($v['user_id']) $MemberPlayModel->inc_balance($v['user_id'], $commission, '订单完成', $remark, $order_info['id'], $order_info['order_num'], 20);
            }
        }


        //更改佣金发放状态
        $PlayPackageOrderModel->where($where)->update([
            'is_commission'   => 1,
            'commission_time' => time(),
            'update_time'     => time(),
        ]);

        //150   佣金计算
        //(150-(150*20%))/3=40
        //(150-(150*22%))/3=39
        //(150-(150*25%))/3=37.5

        //上级佣金
        if ($invitation_commission_activation == 1) {
            $member_info = $MemberModel->where('id', '=', $order_info['user_id'])->find();
            if ($member_info['pid']) {
                //计算佣金
                $commission = round($order_info['total_amount'] * $invitation_commission_order, 2);
                $remark     = "操作人[邀请奖励];操作说明[邀请好友得佣金];操作类型[佣金奖励];";//管理备注
                MemberModel::inc_balance($member_info['pid'], $commission, '邀请奖励', $remark, $order_info['id'], $order_info['order_num'], 10);
            }
        }


    }


}
