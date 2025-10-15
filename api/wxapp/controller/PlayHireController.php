<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayHire",
 *     "name_underline"          =>"play_hire",
 *     "controller_name"         =>"PlayHire",
 *     "table_name"              =>"play_hire",
 *     "remark"                  =>"租号管理"
 *     "api_url"                 =>"/api/wxapp/play_hire/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-12-09 16:14:48",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayHireController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_hire/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_hire/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayHireController extends AuthController
{


    public function initialize()
    {
        //租号管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_hire/index
     * https://pw216.aejust.net/api/wxapp/play_hire/index
     */
    public function index()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)

        $result = [];

        $this->success('租号管理-接口请求成功', $result);
    }


    /**
     * 游戏分类列表
     * @OA\Post(
     *     tags={"租号管理"},
     *     path="/wxapp/play_hire/find_play_list",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_hire/find_play_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_hire/find_play_list
     *   api:  /wxapp/play_hire/find_play_list
     *   remark_name: 游戏分类列表
     *
     */
    public function find_play_list()
    {
        $PlayListInit  = new \init\PlayListInit();//游戏列表   (ps:InitController)
        $PlayListModel = new \initmodel\PlayListModel(); //游戏列表   (ps:InitModel)

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
        $result                  = $PlayListInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 租号管理 列表
     * @OA\Post(
     *     tags={"租号管理"},
     *     path="/wxapp/play_hire/find_hire_list",
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
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="系统",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="price",
     *         in="query",
     *         description="价格排序 1正序,2倒序",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态 1待租,2已锁定",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_hire/find_hire_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_hire/find_hire_list
     *   api:  /wxapp/play_hire/find_hire_list
     *   remark_name: 租号管理 列表
     *
     */
    public function find_hire_list()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理   (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["title|remark", "like", "%{$params['keyword']}%"];
        if ($params["play_id"]) $where[] = ["play_id", "=", $params["play_id"]];
        if ($params["area"]) $where[] = ["area", "=", $params["area"]];
        if ($params["service"]) $where[] = ["service", "=", $params["service"]];
        if ($params["price"] == 1) $params['order'] = 'price';
        if ($params["price"] == 2) $params['order'] = 'price desc';
        if ($params["status"] == 1) $where[] = ["status", "=", $params["status"]];
        if ($params["status"] == 2) $where[] = ["status", "<>", 1];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayHireInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 租号管理 详情
     * @OA\Post(
     *     tags={"租号管理"},
     *     path="/wxapp/play_hire/find_hire",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_hire/find_hire
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_hire/find_hire
     *   api:  /wxapp/play_hire/find_hire
     *   remark_name: 租号管理 详情
     *
     */
    public function find_hire()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理    (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayHireInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


}
