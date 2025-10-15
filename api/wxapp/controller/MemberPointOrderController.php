<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"MemberPointOrder",
 *     "name_underline"          =>"member_point_order",
 *     "controller_name"         =>"MemberPointOrder",
 *     "table_name"              =>"member_point_order",
 *     "remark"                  =>"充值保证金"
 *     "api_url"                 =>"/api/wxapp/member_point_order/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-06-09 16:21:34",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\MemberPointOrderController();
 *     "test_environment"        =>"https://pw216.aejust.net/api/wxapp/member_point_order/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/member_point_order/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class MemberPointOrderController extends AuthController
{


    public function initialize()
    {
        //充值保证金

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/member_point_order/index
     * https://pw216.aejust.net/api/wxapp/member_point_order/index
     */
    public function index()
    {
        $MemberPointOrderInit  = new \init\MemberPointOrderInit();//充值保证金   (ps:InitController)
        $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金   (ps:InitModel)

        $result = [];

        $this->success('充值保证金-接口请求成功', $result);
    }


    /**
     * 下单
     * @OA\Post(
     *     tags={"充值保证金"},
     *     path="/wxapp/member_point_order/add_order",
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
     *    @OA\Parameter(
     *         name="point",
     *         in="query",
     *         description="充值保证金",
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
     *   test_environment: https://pw216.aejust.net/api/wxapp/member_point_order/add_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_point_order/add_order
     *   api:  /wxapp/member_point_order/add_order
     *   remark_name: 充值保证金 编辑&添加
     *
     */
    public function add_order()
    {
        $MemberPointOrderInit  = new \init\MemberPointOrderInit();//充值保证金    (ps:InitController)
        $MemberPointOrderModel = new \initmodel\MemberPointOrderModel(); //充值保证金   (ps:InitModel)

        //参数
        $params              = $this->request->param();
        $params["user_id"]   = $this->user_id;
        $params['point']     = $params['price'];
        $params['amount']    = $params['point'];
        $order_num           = $this->get_only_num('member_point_order');
        $params['order_num'] = $order_num;

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交更新
        $result = $MemberPointOrderInit->api_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");


        $this->success('下单成功,待支付', ['order_num' => $order_num, 'pay_order_type' => 90]);
    }


}
