<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayPackageOrder",
 *     "name_underline"          =>"play_package_order",
 *     "controller_name"         =>"PlayPackageOrder",
 *     "table_name"              =>"play_package_order",
 *     "remark"                  =>"套餐订单"
 *     "api_url"                 =>"/api/wxapp/play_package_order/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-15 14:19:38",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayPackageOrderController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_package_order/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_package_order/index",
 * )
 */


use init\PlayPackageInit;
use init\PlayPackageOrderInit;
use init\PlayPackageSkuInit;
use init\PlayUserOrderInit;
use init\ShopCouponUserInit;
use initmodel\MemberModel;
use initmodel\MemberPlayModel;
use initmodel\OrderPayModel;
use initmodel\PlayClassModel;
use initmodel\PlayPackageOrderModel;
use initmodel\PlayUserOrderModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;


error_reporting(0);


class PlayPackageOrderController extends AuthController
{


    public function initialize()
    {
        //套餐订单

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_package_order/index
     * https://pw216.aejust.net/api/wxapp/play_package_order/index
     */
    public function index()
    {
        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单   (ps:InitModel)

        $result = [];

        $this->success('套餐订单-接口请求成功', $result);
    }


    /**
     * 订单列表
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/find_order_list",
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
     *         name="status",
     *         in="query",
     *         description="状态:0全部 1待付款 20待接单  30待服务,40服务中,50已完成 10已取消",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/find_order_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/find_order_list
     *   api:  /wxapp/play_package_order/find_order_list
     *   remark_name: 套餐订单 列表
     *
     */
    public function find_order_list()
    {
        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单   (ps:InitController)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['user_id', '=', $this->user_id];
        if ($params["keyword"]) $where[] = ["order_num|package_id|map|paly_name|code|area|wx|remark", "like", "%{$params['keyword']}%"];

        //状态:0全部 1待付款 20待接单  30待服务,40服务中,50已完成 10已取消
        if ($params["status"]) $where[] = ["status", "in", $PlayPackageOrderInit->status_api_where[$params["status"]]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayPackageOrderInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 套餐订单 详情
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/find_order",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/find_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/find_order
     *   api:  /wxapp/play_package_order/find_order
     *   remark_name: 套餐订单 详情
     *
     */
    public function find_order()
    {
        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

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
     * 下单
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/add_order",
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
     *    @OA\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="订单类型:1套餐,2陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="play_type",
     *         in="query",
     *         description="陪玩类型:1指定陪玩,2随机陪玩",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="play_id",
     *         in="query",
     *         description="游戏id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
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
     *    @OA\Parameter(
     *         name="goods_price",
     *         in="query",
     *         description="单价",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="count",
     *         in="query",
     *         description="下单数量",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="package_id",
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
     *    @OA\Parameter(
     *         name="sku_id",
     *         in="query",
     *         description="规格",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="coupon_id",
     *         in="query",
     *         description="优惠券",
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
     *         name="paly_name",
     *         in="query",
     *         description="游戏名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="area",
     *         in="query",
     *         description="区服",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="wx",
     *         in="query",
     *         description="微信号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="remark",
     *         in="query",
     *         description="订单备注",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="支付方式  1微信支付,2余额支付",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/add_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/add_order
     *   api:  /wxapp/play_package_order/add_order
     *   remark_name: 下单
     *
     */
    public function add_order()
    {
        $this->checkAuth();

        // 启动事务
        Db::startTrans();

        Log::write("下单请求" . time());

        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $PlayPackageInit       = new PlayPackageInit();//套餐管理    (ps:InitController)
        $PlayPackageSkuInit    = new PlayPackageSkuInit();//规格管理   (ps:InitController)
        $ShopCouponUserInit    = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)
        //        $PlayClassModel        = new PlayClassModel(); //分类管理   (ps:InitModel)
        //        $PlayUserOrderInit     = new PlayUserOrderInit();//陪玩管理   (ps:InitController)
        //        $MemberPlayModel       = new MemberPlayModel(); //陪玩管理   (ps:InitModel)


        //参数
        $params              = $this->request->param();
        $params["user_id"]   = $this->user_id;
        $params["nickname"]  = $this->user_info['nickname'];
        $order_num           = $this->get_only_num('play_package_order');
        $params['order_num'] = $order_num;

        //过滤字段
        if ($params['play_type'] != 1) unset($params['play_user_id'], $params['play_type']);

        //自动取消订单时间/分钟
        $automatic_cancellation_order = cmf_config('automatic_cancellation_order');
        $params['auto_cancel_time']   = time() + ($automatic_cancellation_order * 30);//折中30秒


        //套餐下单
        if ($params['order_type'] == 1) {
            $package_info = $PlayPackageInit->get_find($params['package_id']);
            $sku_info     = $PlayPackageSkuInit->get_find($params['sku_id']);
            if (empty($package_info) || empty($sku_info)) $this->error('参数错误!');
            $params['name']            = $package_info['name'];
            $params['class_id']        = $package_info['class_id'];
            $params['class_two_id']    = $package_info['second_class_id'];
            $params['second_class_id'] = $package_info['second_class_id'];
            $params['search_class_id'] = $package_info['search_class_id'];
            $params['map']             = $package_info['map'];
            $params['min_number']      = $package_info['number'];
            $params['play_number']     = $package_info['number_max'];//最多接单人数
            $params['send1']           = $package_info['send1'];
            $params['goods_price']     = $sku_info['price'];
            $params['sku_name']        = $sku_info['name'];
            $params['total_amount']    = $params['amount'];
            $params['image']           = cmf_get_asset_url($package_info['image']);
            $params['amount']          = round($sku_info['price'] * $params['count'], 2);


            //陪玩
            //            $map30         = [];
            //            $map30[]       = ['id', '>', 0];
            //            $map30[]       = ['is_line', '=', 1];//只通知已在线陪玩
            //            $map30[]       = ['is_block', '=', 2];//正常状态
            //            $map30[]       = ['', 'EXP', Db::raw("FIND_IN_SET({$params['class_id']},play_ids)")];//通知已添加该游戏分类的陪玩
            //            $play_user_ids = $MemberPlayModel->where($map30)->field('id,wx_openid')->orderRaw('rand()')->select();//随机查询陪玩
            //            foreach ($play_user_ids as $k => $v) {
            //                Db::name('play_send')->strict(false)->insert([
            //                    'user_id'     => $v['id'],
            //                    'wx_openid'   => $v['wx_openid'],
            //                    'order_num'   => $order_num,
            //                    'create_time' => time()
            //                ]);
            //            }
        }


        //陪玩下单
        //        if ($params['order_type'] == 2) {
        //            $play_info = $PlayClassModel->where('id', '=', $params['play_id'])->find();
        //            if (empty($play_info)) $this->error('参数错误!');
        //            $params['name']        = $play_info['name'];
        //            $params['image']       = cmf_get_asset_url($play_info['image']);
        //            $params['goods_price'] = $play_info['price'];
        //            $params['play_time']   = $params['count'];
        //
        //            //模板消息
        //            $class_info      = $PlayClassModel->where('id', '=', $play_info['pid'])->find();
        //            $class_name      = $class_info['name'] . '-' . $play_info['name'];//不支出斜杠符号,文字不支持那么多
        //            $class_name      = $play_info['name'];//不支出斜杠符号,文字不支持那么多
        //            $params['send1'] = $class_name;
        //
        //
        //            //默认1人
        //            if (empty($params['play_number'])) $params['play_number'] = 1;
        //            $params['min_number'] = $params['play_number'];//最少人数,和陪玩人数一致即可
        //
        //
        //            //指定陪玩
        //            if ($params['play_user_id']) {
        //                $params['where_play_user_ids'] = "/{$params['play_user_id']}/";
        //                //陪玩n分钟不接单自动取消,将订单返回大厅
        //                $play_order = cmf_config('automatic_cancellation_accompanying_play_order');
        //
        //
        //                //陪玩记录添加
        //                $insert['user_id']          = $params['play_user_id'];
        //                $insert['order_num']        = $params['order_num'];
        //                $insert['status']           = 30;//默认待服务状态
        //                $params['is_begin']         = 1;//可以开始服务
        //                $insert['auto_cancel_time'] = time() + (60 * $play_order);
        //
        //
        //                //检测陪玩是否在线,是否忙碌  && 不检测了
        //                //                if ($params['play_user_id']) {
        //                //                    //1.是否离线
        //                //                    $play_user_info = $MemberPlayModel->where('id', '=', $params['play_user_id'])->find();
        //                //                    //if ($play_user_info['is_line'] == 2) $this->error('陪玩已离线!');
        //                //
        //                //                    //2.是否忙碌
        //                //                    $map20   = [];
        //                //                    $map20[] = ['where_play_user_ids', 'like', "%/{$params['play_user_id']}/%"];
        //                //                    //$map20[]  = ['status', 'in', [30, 40, 60, 62]];//只要不在服务中,待服务就可以接单
        //                //                    $map20[]  = ['status', 'in', [30, 40]];//只要不在服务中,待服务就可以接单
        //                //                    $is_order = $PlayPackageOrderModel->where($map20)->count();
        //                //                    //if ($is_order) $this->error('陪玩正在对局中,请选择新的陪玩!');
        //                //                }
        //
        //                //检测陪玩是否已被拉黑
        //                if ($params['play_user_id']) {
        //                    $play_user_info = $MemberPlayModel->where('id', '=', $params['play_user_id'])->find();
        //                    if ($play_user_info['is_block'] == 1) $this->error('陪玩已被拉黑,请选择新的陪玩!');
        //                }
        //
        //                $PlayUserOrderInit->api_edit_post($insert);
        //            }
        //
        //
        //            //这里给陪玩人数乘上去了
        //            $params['amount'] = round($play_info['price'] * $params['count'] * $params['play_number'], 2);
        //        }


        //余额支付
        if ($params['pay_type'] == 2) $params['balance'] = $params['amount'];

        //项目总金额
        $params['total_amount'] = $params['amount'];

        //核销优惠券  & 如取消订单,优惠券返回
        if (!empty($params['coupon_id'])) {
            $map10       = [];
            $map10[]     = ['id', '=', $params['coupon_id']];
            $coupon_info = $ShopCouponUserInit->get_find($map10);
            if ($coupon_info['used'] != 1) $this->error('优惠券错误!');
            $params['coupon_amount'] = $coupon_info['amount'];
            $params['amount']        = round($params['amount'] - $params['coupon_amount'], 2);

            //核销优惠券
            $ShopCouponUserInit->use_coupon($params['coupon_id'], $order_num);
        }


        $result = $PlayPackageOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试!');


        // 提交事务
        Db::commit();


        $this->success('下单成功', ['order_num' => $order_num]);
    }


    /**
     * 取消订单
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/cancel_order",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/cancel_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/cancel_order
     *   api:  /wxapp/play_package_order/cancel_order
     *   remark_name: 取消订单
     *
     */
    public function cancel_order()
    {
        $PlayPackageOrderInit = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $ShopCouponUserInit   = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (in_array($order_info['status'], [30, 40, 50, 60, 62, 10])) $this->error('非法操作!');


        $params['status']      = 10;
        $params['cancel_time'] = time();

        //如使用优惠,将优惠券返回
        if ($order_info['coupon_id']) $ShopCouponUserInit->cancel_use_coupon($order_info['coupon_id']);


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");

        $this->success("取消成功");
    }


    /**
     * 取消订单 退款操作
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/cancel_order2",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/cancel_order2
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/cancel_order2
     *   api:  /wxapp/play_package_order/cancel_order2
     *   remark_name: 取消订单   退款操作
     *
     */
    public function cancel_order2()
    {
        $PlayPackageOrderInit = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $ShopCouponUserInit   = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (in_array($order_info['status'], [30, 40, 50, 60, 62, 10])) $this->error('非法操作!');


        $params['status']      = 14;
        $params['cancel_time'] = time();

        //如使用优惠,将优惠券返回
        //if ($order_info['coupon_id']) $ShopCouponUserInit->cancel_use_coupon($order_info['coupon_id']);


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");

        $this->success("提交成功,待审核!");
    }


    /**
     * 申请退款
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/refund_order",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/refund_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/refund_order?id=36795
     *   api:  /wxapp/play_package_order/refund_order
     *   remark_name: 申请退款
     *
     */
    public function refund_order()
    {
        $PlayPackageOrderInit = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $ShopCouponUserInit   = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        if (!in_array($order_info['status'], [20])) $this->error('打手已接单,请联系客服处理!');


        $params['status']      = 15;//申请退款
        $params['refund_time'] = time();

        //退款操作
        if ($order_info['pay_type'] == 1) {
            //给用户退款 & 微信支付
            $Pay = new PayController();

            $map            = [];
            $map[]          = ['order_num', '=', $order_info['order_num']];//实际订单号
            $map[]          = ['status', '=', 2];//已支付
            $pay_info       = Db::name('order_pay')->where($map)->find();//支付记录表
            $order_num      = $pay_info['pay_num'];//支付单号
            $transaction_id = $pay_info['trade_num'];//第三方单号


            $refund_result = $Pay->wx_pay_refund($transaction_id, $order_num, $order_info['amount']);
            $refund_result = $refund_result['data'];
            if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
        }


        if ($order_info['pay_type'] == 2) {
            //退款余额
            $remark = "操作人[{$this->user_info['nickname']}];操作说明[订单退款-{$order_info['order_num']}];操作类型[退款订单增加余额];";//管理备注
            MemberModel::inc_balance($order_info['user_id'], $order_info['amount'], '订单退款', $remark, $order_info['id'], $order_info['order_num'], 200);
        }

        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");


        //如使用优惠,将优惠券返回
        if ($order_info['coupon_id']) $ShopCouponUserInit->cancel_use_coupon($order_info['coupon_id']);


        $this->success("退款成功!");
    }


    /**
     * 完成订单
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/accomplish_order",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/accomplish_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/accomplish_order?id=33
     *   api:  /wxapp/play_package_order/accomplish_order
     *   remark_name: 完成订单,用户
     *
     */
    public function accomplish_order()
    {
        $this->checkAuth();
        $PlayPackageOrderInit = new PlayPackageOrderInit();//陪玩订单    (ps:InitController)
        $PlayOrderController  = new PlayOrderController();//陪玩端订单管理


        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        $order_info = $PlayPackageOrderInit->get_find($where);
        if (empty($order_info)) $this->error('订单不存在!');
        //状态必须是服务中
        //if ($order_info['status'] > 40) $this->error('非法操作!');


        $params['status']          = 50;//用户完成直接完成,给陪玩发送佣金
        $params['update_time']     = time();
        $params['accomplish_time'] = time();


        //发送佣金
        if ($order_info['is_commission'] == 2) $PlayOrderController->send_commission($order_info['order_num']); //后台发送佣金 &&  给陪玩发放佣金


        $result = $PlayPackageOrderInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试!");

        $this->success("操作成功!");
    }


    /**
     * 再来一单
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/encore_order",
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
     *    @OA\Parameter(
     *         name="p_order_num",
     *         in="query",
     *         description="订单",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="encore_type",
     *         in="query",
     *         description="1指定上次打手,2随机新的打手",
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
     *         name="play_user_ids",
     *         in="query",
     *         description="陪玩列表",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="coupon_id",
     *         in="query",
     *         description="优惠券id",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/encore_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/encore_order
     *   api:  /wxapp/play_package_order/encore_order
     *   remark_name: 再来一单
     *
     */
    public function encore_order()
    {
        $this->error('失败,暂未开放!');
        $this->checkAuth();

        // 启动事务
        Db::startTrans();


        $PlayPackageOrderInit  = new PlayPackageOrderInit();//套餐订单    (ps:InitController)
        $PlayPackageInit       = new PlayPackageInit();//套餐管理    (ps:InitController)
        $PlayPackageSkuInit    = new PlayPackageSkuInit();//规格管理   (ps:InitController)
        $ShopCouponUserInit    = new ShopCouponUserInit();//优惠券领取记录   (ps:InitController)
        $PlayClassModel        = new PlayClassModel(); //分类管理   (ps:InitModel)
        $PlayUserOrderModel    = new PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params              = $this->request->param();
        $params["user_id"]   = $this->user_id;
        $order_num           = $this->get_only_num('play_package_order');
        $params['order_num'] = $order_num;
        //自动取消订单时间/分钟
        $automatic_cancellation_order = cmf_config('automatic_cancellation_order');
        $params['auto_cancel_time']   = time() + ($automatic_cancellation_order * 60);


        //查询上个订单
        $map        = [];
        $map[]      = ['order_num', '=', $params['p_order_num']];
        $order_info = $PlayPackageOrderModel->where($map)->find();


        //创建订单
        $params['order_type']   = $order_info['order_type'];
        $params['class_id']     = $order_info['class_id'];
        $params['send1']        = $order_info['send1'];
        $params['play_type']    = $order_info['play_type'];
        $params['receive_type'] = $order_info['receive_type'];
        $params['play_id']      = $order_info['play_id'];
        $params['play_number']  = $order_info['play_number'];
        $params['package_id']   = $order_info['package_id'];
        $params['sku_id']       = $order_info['sku_id'];
        $params['name']         = $order_info['name'];
        $params['sku_name']     = $order_info['sku_name'];
        $params['count']        = $order_info['count'];
        $params['map']          = $order_info['map'];
        $params['image']        = $order_info['image'];
        $params['is_encore']    = 2;//再来一单


        //套餐下单
        if ($order_info['order_type'] == 1) {
            $package_info = $PlayPackageInit->get_find($order_info['package_id']);
            $sku_info     = $PlayPackageSkuInit->get_find($order_info['sku_id']);
            if (empty($package_info) || empty($sku_info)) $this->error('参数错误!');
            $params['goods_price']  = $sku_info['price'];
            $params['amount']       = round($sku_info['price'] * $order_info['count'], 2);
            $params['total_amount'] = $params['amount'];
        }


        //陪玩下单
        if ($order_info['order_type'] == 2) {
            $play_info = $PlayClassModel->where('id', '=', $order_info['play_id'])->find();
            if (empty($play_info)) $this->error('参数错误!');
            $params['goods_price'] = $play_info['price'];

            //这里给陪玩人数乘上去了
            $params['amount']       = round($play_info['price'] * $order_info['count'] * $order_info['play_number'], 2);
            $params['total_amount'] = $params['amount'];
        }


        //余额支付
        if ($params['pay_type'] == 2) $params['balance'] = $params['amount'];


        //encore_type  1指定上次打手,2随机新的打手
        if ($params['encore_type'] == 1) {
            $params['play_user_id'] = $params['play_user_ids'][0];//第一个人当主陪玩
            $params['is_begin']     = 1;//开始按钮展示

            //处理陪玩
            $play_user_ids       = $params['play_user_ids'];
            $where_play_user_ids = '';
            foreach ($play_user_ids as $k => $v) {
                $where_play_user_ids .= "/$v/,";


                //插入记录表
                $PlayUserOrderModel->strict(false)->insert([
                    'order_num'   => $order_num,
                    'user_id'     => $v,
                    'status'      => 30,
                    'create_time' => time(),
                ]);

            }
            $params['where_play_user_ids'] = rtrim($where_play_user_ids, ',');
        }


        //核销优惠券  & 如取消订单,优惠券返回
        if (!empty($params['coupon_id'])) {
            $map10       = [];
            $map10[]     = ['id', '=', $params['coupon_id']];
            $coupon_info = $ShopCouponUserInit->get_find($map10);
            if ($coupon_info['used'] != 1) $this->error('优惠券错误!');
            $params['coupon_amount'] = $coupon_info['amount'];
            $params['amount']        = round($params['amount'] - $params['coupon_amount'], 2);

            //核销优惠券
            $ShopCouponUserInit->use_coupon($params['coupon_id'], $order_num);
        }


        $result = $PlayPackageOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试!');


        // 提交事务
        Db::commit();


        $this->success('下单成功', ['order_num' => $order_num]);
    }


    /**
     * 检测是否可以再来一单
     * @OA\Post(
     *     tags={"套餐订单"},
     *     path="/wxapp/play_package_order/check_encore_order",
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
     *    @OA\Parameter(
     *         name="p_order_num",
     *         in="query",
     *         description="订单",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="encore_type",
     *         in="query",
     *         description="1指定上次打手,如果随机匹配新陪玩直接下单就行",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package_order/check_encore_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package_order/check_encore_order
     *   api:  /wxapp/play_package_order/check_encore_order
     *   remark_name: 检测是否可以再来一单
     *
     */
    public function check_encore_order()
    {
        $this->error('失败,暂未开放!');
        $MemberPlayModel       = new MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayUserOrderModel    = new PlayUserOrderModel(); //陪玩管理  (ps:InitModel)
        $PlayPackageOrderModel = new PlayPackageOrderModel(); //套餐订单  (ps:InitModel)


        //参数
        $params = $this->request->param();


        //判断陪玩是否正在对局中
        $map10     = [];
        $map10[]   = ['status', '=', 30];
        $map10[]   = ['order_num', '=', $params['p_order_num']];
        $user_list = $PlayUserOrderModel->where($map10)->select();

        $total_user     = count($user_list);//所有陪玩
        $busy_user      = 0;//忙碌人数
        $free_user_list = [];//空闲人数

        foreach ($user_list as $k => $v) {
            $map11     = [];
            $map11[]   = ['where_play_user_ids', 'like', "/%{$v['user_id']}%/"];
            $map11[]   = ['status', 'in', [30, 40, 60, 62]];
            $user_info = $PlayPackageOrderModel->where($map11)->find();
            if ($user_info) {
                $busy_user++;
            } else {
                $free_user_list[] = $v['user_id'];
            }
        }

        //有陪玩在游戏中  &&  不在检测
        //        if ($total_user > count($free_user_list)) $this->error('部分打手暂时忙碌不能接单，建议继续下单，剩余打手随机分配', $free_user_list);
        //        if ($total_user <= count($free_user_list)) $this->error('全部打手都在忙碌中，暂时不能接单，建议分配全新打手继续。', []);


        $this->success('可以下单', $free_user_list);
    }


    /**
     * 下单通知,通知陪玩有新订单
     * @param $order
     */
    public function send_msg($order)
    {
        $SendTempMsgController = new SendTempMsgController();
        $MemberPlayModel       = new MemberPlayModel(); //陪玩管理  (ps:InitModel)


        //再来一单通知,之前陪玩
        if ($order['is_encore'] == 2) {
            $send_data = [
                'short_thing4' => ['value' => $order['send1']],
                'short_thing5' => ['value' => '再来一单'],
                'thing11'      => ['value' => $order['area']],
                'time6'        => ['value' => date('Y-m-d H:i:s')],
            ];
            //通知团队所有人有单子
            $play_user_ids = $this->getParams($order['where_play_user_ids']);
            $not_user_ids  = [];
            foreach ($play_user_ids as $k => $v) {
                $user_id        = (int)str_replace('/', '', $v);
                $not_user_ids[] = $user_id;//不在通知这几个陪玩了
                //发送通知
                $openid = $MemberPlayModel->where('id', $user_id)->value('wx_openid');
                if ($openid) $SendTempMsgController->sendTempMsg($openid, 'HLdJtNyjR3NTqpEHCcBHMFRw6vBR4OShQcNPu1SNf7o', $send_data);
            }
        }


        //陪玩下单,给指定陪玩发送模板消息
        if ($order['order_type'] == 2) {
            //发送模板消息,只给指定人发信息即可
            if ($order['play_type'] == 1) {
                $short_thing5 = '预约单';
                if ($order['is_encore'] == 2) $short_thing5 = '再来一单';
                $send_data = [
                    'short_thing4' => ['value' => $order['send1']],
                    'short_thing5' => ['value' => $short_thing5],
                    'thing11'      => ['value' => $order['area']],
                    'time6'        => ['value' => date('Y-m-d H:i:s')],
                ];

                $map40   = [];
                $map40[] = ['id', '=', $order['play_user_id']];
                $map40[] = ['is_block', '=', 2];//正常状态
                $map40[] = ['', 'EXP', Db::raw("FIND_IN_SET({$order['class_id']},play_ids)")];//通知已添加该游戏分类的陪玩
                if ($not_user_ids) $map40[] = ['id', 'not in', $not_user_ids];//上方通知过不再进行通知
                $openid = $MemberPlayModel->where($map40)->value('wx_openid');
                if ($openid) $SendTempMsgController->sendTempMsg($openid, 'HLdJtNyjR3NTqpEHCcBHMFRw6vBR4OShQcNPu1SNf7o', $send_data);
            }


            //如果团队单子,人数没有达到,或者随机陪玩,将给所有陪玩发送模板消息
            if ($order['status'] != 30 && $order['play_type'] == 2) {
                $send_data = [
                    'short_thing4' => ['value' => $order['send1']],
                    'short_thing5' => ['value' => '预约单'],
                    'thing11'      => ['value' => $order['area']],
                    'time6'        => ['value' => date('Y-m-d H:i:s')],
                ];

                $map20   = [];
                $map20[] = ['id', '>', 0];
                $map20[] = ['is_line', '=', 1];//只通知已在线陪玩
                $map20[] = ['is_block', '=', 2];//正常状态
                $map20[] = ['', 'EXP', Db::raw("FIND_IN_SET({$order['class_id']},play_ids)")];//通知已添加该游戏分类的陪玩
                if ($not_user_ids) $map20[] = ['id', 'not in', $not_user_ids];//上方通知过不再进行通知
                $user_list = $MemberPlayModel->where($map20)->field('wx_openid')->orderRaw('rand()')->select();//随机查询
                foreach ($user_list as $k => $v) {
                    if ($v['wx_openid']) $SendTempMsgController->sendTempMsg($v['wx_openid'], 'HLdJtNyjR3NTqpEHCcBHMFRw6vBR4OShQcNPu1SNf7o', $send_data);
                }
            }

        }

        //        //套餐通知所有陪玩
        //        if ($order['order_type'] == 1) {
        //            $send_data = [
        //                'short_thing4' => ['value' => $order['send1']],
        //                'short_thing5' => ['value' => '套餐单'],
        //                'thing11'      => ['value' => $order['area']],
        //                'time6'        => ['value' => date('Y-m-d H:i:s')],
        //            ];
        //
        //
        //            $map30   = [];
        //            $map30[] = ['id', '>', 0];
        //            $map30[] = ['is_line', '=', 1];//只通知已在线陪玩
        //            $map30[] = ['is_block', '=', 2];//正常状态
        //            $map30[] = ['', 'EXP', Db::raw("FIND_IN_SET({$order['class_id']},play_ids)")];//通知已添加该游戏分类的陪玩
        //            if ($not_user_ids) $map30[] = ['id', 'not in', $not_user_ids];//通知过的陪玩不在通知
        //            $user_list = $MemberPlayModel->where($map30)->field('wx_openid')->orderRaw('rand()')->select();//随机查询陪玩
        //            foreach ($user_list as $k => $v) {
        //                if ($v['wx_openid']) $SendTempMsgController->sendTempMsg($v['wx_openid'], 'HLdJtNyjR3NTqpEHCcBHMFRw6vBR4OShQcNPu1SNf7o', $send_data);
        //            }
        //        }

    }


    /**
     * 订单已开始通知,通知用户(陪玩操作开始游戏)
     * @param $order
     */
    public function send_msg2($order)
    {
        $SendTempMsgController = new SendTempMsgController();

        $send_data = [
            'character_string6' => ['value' => $order['order_num']],
            'thing10'           => ['value' => $order['send1']],
            'time4'             => ['value' => date('Y-m-d H:i:s')],
        ];


        $member_info = MemberModel::where('id', '=', $order['user_id'])->find();
        $openid      = $member_info['openid'];
        $SendTempMsgController->sendTempMsg($openid, 'dioGmaagOgampFs9XtmI5H_QKJRgK5Pv81nCHlhH2SQ', $send_data);
    }


    /**
     * 订单已完成通知,通知用户(陪玩完成后通知)
     * @param $order
     */
    public function send_msg3($order)
    {
        $SendTempMsgController = new SendTempMsgController();

        $send_data = [
            'character_string8' => ['value' => $order['order_num']],
            'thing13'           => ['value' => $order['send1']],
            'time18'            => ['value' => date('Y-m-d H:i:s')],
        ];


        $member_info = MemberModel::where('id', '=', $order['user_id'])->find();
        $openid      = $member_info['openid'];
        $SendTempMsgController->sendTempMsg($openid, 'GfBuqQCqxTNkdQNtbbGCwtNhlDwQWEMsUCI_M-6JMWc', $send_data);
    }

}
