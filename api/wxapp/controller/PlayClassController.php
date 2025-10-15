<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"PlayClass",
 *     "name_underline"          =>"play_class",
 *     "controller_name"         =>"PlayClass",
 *     "table_name"              =>"play_class",
 *     "remark"                  =>"分类管理"
 *     "api_url"                 =>"/api/wxapp/play_class/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-14 10:10:28",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\PlayClassController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/play_class/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/play_class/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class PlayClassController extends AuthController
{


    public function initialize()
    {
        //分类管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/play_class/index
     * https://pw216.aejust.net/api/wxapp/play_class/index
     */
    public function index()
    {
        $PlayClassInit  = new \init\PlayClassInit();//分类管理   (ps:InitController)
        $PlayClassModel = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)

        $result = [];

        $this->success('分类管理-接口请求成功', $result);
    }


    /**
     * 分类管理 一级
     * @OA\Post(
     *     tags={"分类管理"},
     *     path="/wxapp/play_class/find_class_list",
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
     *   test_environment: http://pw216.ikun/api/wxapp/play_class/find_class_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_class/find_class_list
     *   api:  /wxapp/play_class/find_class_list
     *   remark_name: 分类管理 列表
     *
     */
    public function find_class_list()
    {
        $PlayClassInit  = new \init\PlayClassInit();//分类管理   (ps:InitController)
        $PlayClassModel = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["pid", "=", 0];


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $PlayClassInit->get_list($where, $params);


        $this->success("请求成功!", $result);
    }


    /**
     * 分类管理 二级
     * @OA\Post(
     *     tags={"分类管理"},
     *     path="/wxapp/play_class/find_two_class_list",
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
     *         name="pid",
     *         in="query",
     *         description="pid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="is_index",
     *         in="query",
     *         description="true首页",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_class/find_two_class_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_class/find_two_class_list?pid=1
     *   api:  /wxapp/play_class/find_two_class_list
     *   remark_name: 分类管理 列表
     *
     */
    public function find_two_class_list()
    {
        $PlayClassInit  = new \init\PlayClassInit();//分类管理   (ps:InitController)
        $PlayClassModel = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["pid"]) $where[] = ["pid", "=", $params["pid"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型


        if ($params["is_index"]) {
            $where[]         = ["is_index", "=", 1];
            $params['limit'] = 7;
            $result          = $PlayClassInit->get_list($where, $params);
            if (empty($result)) $this->error("暂无信息!");
        } else {
            $result = $PlayClassInit->get_list($where, $params);
            if (empty($result)) $this->error("暂无信息!");
        }


        $this->success("请求成功!", $result);
    }


    /**
     * 分类管理 一级,二级,插件格式
     * @OA\Post(
     *     tags={"分类管理"},
     *     path="/wxapp/play_class/class_list",
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
     *         name="pid",
     *         in="query",
     *         description="pid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="is_index",
     *         in="query",
     *         description="true首页",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/play_class/class_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/play_class/class_list
     *   api:  /wxapp/play_class/class_list
     *   remark_name: 分类管理 一级,二级,插架格式
     *
     */
    public function class_list()
    {
        $PlayClassModel = new \initmodel\PlayClassModel(); //分类管理   (ps:InitModel)
        $MemberPlayInit = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)

        $params = $this->request->param();
        //查询条件
        $where   = [];
        $where[] = ["pid", "=", 0];

        //获取陪玩关联的分类id
        $play_info = $MemberPlayInit->get_find($params['play_user_id'], ['field' => '*']);
        $class_ids = [];
        if ($play_info['play_ids']) {
            $class_ids = $this->getParams($play_info['play_ids']);
        }

        $class_list = $PlayClassModel->where($where)->select()
            ->each(function ($item, $key) use ($PlayClassModel, $class_ids) {
                $map   = [];
                $map[] = ['pid', '=', $item['id']];
                if ($class_ids) $map[] = ['id', 'in', $class_ids];
                $item['children'] = $PlayClassModel->where($map)->select();
                return $item;
            });


        //返回信息
        $result = [];
        foreach ($class_list as $k => $v) {
            if (count($v['children'])) $result[] = $v;
        }

        $this->success("请求成功!", array_merge($result));
    }

}
