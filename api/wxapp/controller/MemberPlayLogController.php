<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"MemberPlayLog",
 *     "name_underline"          =>"member_play_log",
 *     "controller_name"         =>"MemberPlayLog",
 *     "table_name"              =>"member_play_log",
 *     "remark"                  =>"陪玩注册"
 *     "api_url"                 =>"/api/wxapp/member_play_log/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-05-15 16:55:42",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\MemberPlayLogController();
 *     "test_environment"        =>"http://pw214.ikun:9090/api/wxapp/member_play_log/index",
 *     "official_environment"    =>"https://pw214.wxselling.com/api/wxapp/member_play_log/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class MemberPlayLogController extends AuthController
{


    public function initialize()
    {
        //陪玩注册

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/member_play_log/index
     * https://pw214.wxselling.com/api/wxapp/member_play_log/index
     */
    public function index()
    {
        $MemberPlayLogInit  = new \init\MemberPlayLogInit();//陪玩注册   (ps:InitController)
        $MemberPlayLogModel = new \initmodel\MemberPlayLogModel(); //陪玩注册   (ps:InitModel)

        $result = [];

        $this->success('陪玩注册-接口请求成功', $result);
    }


    /**
     * 陪玩注册 添加
     * @OA\Post(
     *     tags={"陪玩注册"},
     *     path="/wxapp/member_play_log/add_play",
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
     *         name="nickname",
     *         in="query",
     *         description="昵称",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="avatar",
     *         in="query",
     *         description="头像",
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
     *         name="phone",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="密码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="性别",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="age",
     *         in="query",
     *         description="年龄",
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
     *         name="introduce",
     *         in="query",
     *         description="介绍",
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
     *         name="great",
     *         in="query",
     *         description="擅长位置",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="hero",
     *         in="query",
     *         description="擅长英雄",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="id_number",
     *         in="query",
     *         description="身份证号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="images",
     *         in="query",
     *         description="相册  数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="play_ids",
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
     *         name="price",
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
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw214.ikun:9090/api/wxapp/member_play_log/add_play
     *   official_environment: https://pw214.wxselling.com/api/wxapp/member_play_log/add_play
     *   api:  /wxapp/member_play_log/add_play
     *   remark_name: 陪玩注册 编辑&添加
     *
     */
    public function add_play()
    {
        $this->checkAuth();
        $MemberPlayLogInit  = new \init\MemberPlayLogInit();//陪玩注册    (ps:InitController)
        $MemberPlayLogModel = new \initmodel\MemberPlayLogModel(); //陪玩注册   (ps:InitModel)
        $MemberPlayModel    = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        //参数
        $params                = $this->request->param();
        $params["user_id"]     = $this->user_id;
        $params["openid"]      = 'o2x' . md5(cmf_random_string(60, 3));
        $params["wx_openid"]   = $this->openid;
        $order_num             = $this->get_only_num('member_play_log');
        $params['order_num']   = $order_num;
        $params['is_block']    = 1;//审核
        $params['create_time'] = time();
        //陪玩入驻支付金额
        $accompany_check  = cmf_config('accompany_check');
        $params['amount'] = $accompany_check;

        //加密密码
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);


        //检测手机号是否存在
        $map       = [];
        $map[]     = ['phone', '=', $params['phone']];
        $play_info = $MemberPlayModel->where($map)->count();
        if ($play_info) $this->error("手机号已存在");


        //提交更新
        $result = $MemberPlayLogInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试");


        $result = $MemberPlayModel->strict(false)->insert($params);
        if (!$result) $this->error("失败请重试");


        $this->success('注册成功,等待审核!');
    }


}
