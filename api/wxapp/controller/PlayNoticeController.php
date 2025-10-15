<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayNotice",
 *     "name_underline"          =>"play_notice",
 *     "controller_name"         =>"PlayNotice",
 *     "table_name"              =>"play_notice",
 *     "remark"                  =>"通知管理"
 *     "api_url"                 =>"/api/wxapp/play_notice/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-14 10:00:42",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayNoticeController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_notice/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_notice/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayNoticeController extends AuthController
{


    public function initialize()
    {
        //通知管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_notice/index
     * https://pw216.aejust.net/api/wxapp/play_notice/index
     */
    public function index()
    {
        $PlayNoticeInit  = new \init\PlayNoticeInit();//通知管理   (ps:InitController)
        $PlayNoticeModel = new \initmodel\PlayNoticeModel(); //通知管理   (ps:InitModel)

        $result = [];

        $this->success('通知管理-接口请求成功', $result);
    }


    /**
     * 通知管理 列表
     * @OA\Post(
     *     tags={"通知管理"},
     *     path="/wxapp/play_notice/find_play_notice_list",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_notice/find_play_notice_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_notice/find_play_notice_list
     *   api:  /wxapp/play_notice/find_play_notice_list
     *   remark_name: 通知管理 列表
     *
     */
    public function find_play_notice_list()
    {
        $PlayNoticeInit  = new \init\PlayNoticeInit();//通知管理   (ps:InitController)
        $PlayNoticeModel = new \initmodel\PlayNoticeModel(); //通知管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["title|introduce", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayNoticeInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 通知管理 详情
     * @OA\Post(
     *     tags={"通知管理"},
     *     path="/wxapp/play_notice/find_play_notice",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_notice/find_play_notice
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_notice/find_play_notice
     *   api:  /wxapp/play_notice/find_play_notice
     *   remark_name: 通知管理 详情
     *
     */
    public function find_play_notice()
    {
        $PlayNoticeInit  = new \init\PlayNoticeInit();//通知管理    (ps:InitController)
        $PlayNoticeModel = new \initmodel\PlayNoticeModel(); //通知管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayNoticeInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


}
