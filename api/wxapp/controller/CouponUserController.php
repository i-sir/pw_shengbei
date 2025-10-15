<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"CouponUser",
 *     "name_underline"          =>"coupon_user",
 *     "controller_name"         =>"CouponUser",
 *     "table_name"              =>"shop_coupon_user",
 *     "remark"                  =>"优惠券领取记录"
 *     "api_url"                 =>"/api/wxapp/coupon_user/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-25 14:15:06",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\CouponUserController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/coupon_user/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/coupon_user/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class CouponUserController extends AuthController
{


    public function initialize()
    {
        //优惠券领取记录

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/coupon_user/index
     * https://pw216.aejust.net/api/wxapp/coupon_user/index
     */
    public function index()
    {
        $CouponUserInit  = new \init\CouponUserInit();//优惠券领取记录   (ps:InitController)
        $CouponUserModel = new \initmodel\CouponUserModel(); //优惠券领取记录   (ps:InitModel)

        $result = [];

        $this->success('优惠券领取记录-接口请求成功', $result);
    }


    /**
     * 优惠券领取记录列表
     * @OA\Post(
     *     tags={"优惠券领取记录"},
     *     path="/wxapp/coupon_user/find_coupon_user_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="used",
     *         in="query",
     *         description="状态:1未使用,2已使用,3已过期",
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
     *   test_environment: http://pw216.ikun/api/wxapp/coupon_user/find_coupon_user_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/coupon_user/find_coupon_user_list
     *   api:  /wxapp/coupon_user/find_coupon_user_list
     *   remark_name: 优惠券领取记录 列表
     *
     */
    public function find_coupon_user_list()
    {
        $this->checkAuth();
        $CouponUserInit  = new \init\CouponUserInit();//优惠券领取记录   (ps:InitController)
        $CouponUserModel = new \initmodel\CouponUserModel(); //优惠券领取记录   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = ['used', '=', 1];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["used"]) $where[] = ["used", "=", $params["used"]];
        if ($params['price']) $where[] = ["full_amount", "<=", $params['price']];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $CouponUserInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 可领取优惠券列表
     * @OA\Post(
     *     tags={"优惠券领取记录"},
     *     path="/wxapp/coupon_user/find_coupon_list",
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
     *   test_environment: http://pw216.ikun/api/wxapp/coupon_user/find_coupon_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/coupon_user/find_coupon_list
     *   api:  /wxapp/coupon_user/find_coupon_list
     *   remark_name: 可领取优惠券列表
     *
     */
    public function find_coupon_list()
    {
        $params            = $this->request->param();
        $params['user_id'] = $this->user_id;

        $ShopCouponInit = new \init\ShopCouponInit();//优惠券    (ps:InitController)


        $where   = [];
        $where[] = ['end_time', '>', time()];
        $where[] = ['count', '>', 0];


        $result = $ShopCouponInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 领取优惠券
     * @OA\Post(
     *     tags={"优惠券领取记录"},
     *     path="/wxapp/coupon_user/add_coupon",
     *
     *
     *
     *
     *     @OA\Parameter(
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
     *   test_environment: http://pw216.ikun/api/wxapp/coupon_user/add_coupon
     *   official_environment: https://pw216.aejust.net/api/wxapp/coupon_user/add_coupon
     *   api:  /wxapp/coupon_user/add_coupon
     *   remark_name: 领取优惠券
     *
     */
    public function add_coupon()
    {
        $this->checkAuth();
        $CouponUserModel = new \initmodel\CouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $CouponUserInit  = new \init\CouponUserInit();//优惠券领取记录   (ps:InitController)


        $params            = $this->request->param();
        $params['user_id'] = $this->user_id;


        $map        = [];
        $map[]      = ['user_id', '=', $params['user_id']];
        $map[]      = ['coupon_id', '=', $params['coupon_id']];
        $is_receive = $CouponUserModel->where($map)->find();
        if ($is_receive) $this->error('请勿重复领取!');

        //查询优惠券信息
        $coupon_info = $ShopCouponModel->where('id', '=', $params['coupon_id'])->find();
        if (empty($coupon_info)) $this->error('优惠券不存在!');


        //插入领取记录
        $insert['coupon_id']   = $params['coupon_id'];
        $insert['user_id']     = $this->user_id;
        $insert['start_time']  = $coupon_info['start_time'];
        $insert['end_time']    = $coupon_info['end_time'];
        $insert['amount']      = $coupon_info['amount'];
        $insert['full_amount'] = $coupon_info['full_amount'];
        $insert['name']        = $coupon_info['name'];
        $insert['code']        = $this->get_only_num('shop_coupon_user', 'code', 20, 2);
        $CouponUserInit->api_edit_post($insert);


        //扣除优惠券剩余数量
        $ShopCouponModel->where('id', '=', $params['coupon_id'])->dec('count', 1)->update();
        $ShopCouponModel->where('id', '=', $params['coupon_id'])->inc('select_count', 1)->update();


        $this->success('领取成功!');
    }


}
