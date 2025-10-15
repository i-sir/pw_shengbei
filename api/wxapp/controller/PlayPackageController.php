<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayPackage",
 *     "name_underline"          =>"play_package",
 *     "controller_name"         =>"PlayPackage",
 *     "table_name"              =>"play_package",
 *     "remark"                  =>"套餐管理"
 *     "api_url"                 =>"/api/wxapp/play_package/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-14 11:18:36",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayPackageController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_package/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_package/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayPackageController extends AuthController
{


    public function initialize()
    {
        //套餐管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_package/index
     * https://pw216.aejust.net/api/wxapp/play_package/index
     */
    public function index()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)

        $result = [];

        $this->success('套餐管理-接口请求成功', $result);
    }


    /**
     * 套餐管理 列表
     * @OA\Post(
     *     tags={"套餐管理"},
     *     path="/wxapp/play_package/find_play_package_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="is_index",
     *         in="query",
     *         description="首页推荐 true",
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
     *         name="class_id",
     *         in="query",
     *         description="分类id",
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
     *    @OA\Parameter(
     *         name="tag1",
     *         in="header",
     *         description="true  必出T7单",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="tag2",
     *         in="header",
     *         description="true 必出大金单",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="tag3",
     *         in="header",
     *         description="true 翻倍单",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package/find_play_package_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package/find_play_package_list
     *   api:  /wxapp/play_package/find_play_package_list
     *   remark_name: 套餐管理 列表
     *
     */
    public function find_play_package_list()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['is_show', '=', 1];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["is_index"]) $where[] = ["is_index", "=", 1];
        //if ($params["class_id"]) $where[] = ['', 'EXP', Db::raw("FIND_IN_SET({$params['class_id']},play_ids)")];
        if ($params["class_id"]) $where[] = ["search_class_id", "=", $params['class_id']];
        if ($params["key"]) $where[] = [$params["key"], "=", 1];


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayPackageInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 首页套餐列表
     * @OA\Post(
     *     tags={"套餐管理"},
     *     path="/wxapp/play_package/find_play_package_index",
     *
     *
     *
     *
     *
     *     @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         description="分类id",
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
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_package/find_play_package_index
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package/find_play_package_index
     *   api:  /wxapp/play_package/find_play_package_index
     *   remark_name: 首页套餐id
     *
     */
    public function find_play_package_index()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理   (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['is_show', '=', 1];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["is_index"]) $where[] = ["is_index", "=", 1];
        //if ($params["class_id"]) $where[] = ['', 'EXP', Db::raw("FIND_IN_SET({$params['class_id']},play_ids)")];
        if ($params["class_id"]) $where[] = ["search_class_id", "=", $params['class_id']];


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["limit"]         = 10000000;
        $map5                    = [];
        $map5[]                  = ['tag5', '=', 1];
        $result[]                = ['name' => '压榨打手', 'key' => 'tag5', 'list' => $PlayPackageInit->get_list(array_merge($where, $map5), $params)];
        $map                     = [];
        $map[]                   = ['tag1', '=', 1];
        $result[]                = ['name' => '必出T7单', 'key' => 'tag1', 'list' => $PlayPackageInit->get_list(array_merge($where, $map), $params)];
        $map2                    = [];
        $map2[]                  = ['tag2', '=', 1];
        $result[]                = ['name' => '必出大金单', 'key' => 'tag2', 'list' => $PlayPackageInit->get_list(array_merge($where, $map2), $params)];
        $map3                    = [];
        $map3[]                  = ['tag3', '=', 1];
        $result[]                = ['name' => '翻倍单', 'key' => 'tag3', 'list' => $PlayPackageInit->get_list(array_merge($where, $map3), $params)];
        $map4                    = [];
        $map4[]                  = ['tag4', '=', 1];
        $result[]                = ['name' => '转盘单', 'key' => 'tag4', 'list' => $PlayPackageInit->get_list(array_merge($where, $map4), $params)];

        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 套餐管理 详情
     * @OA\Post(
     *     tags={"套餐管理"},
     *     path="/wxapp/play_package/find_play_package",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_package/find_play_package
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_package/find_play_package
     *   api:  /wxapp/play_package/find_play_package
     *   remark_name: 套餐管理 详情
     *
     */
    public function find_play_package()
    {
        $PlayPackageInit  = new \init\PlayPackageInit();//套餐管理    (ps:InitController)
        $PlayPackageModel = new \initmodel\PlayPackageModel(); //套餐管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["is_sku"]        = true;//规格详情
        $result                  = $PlayPackageInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


}
