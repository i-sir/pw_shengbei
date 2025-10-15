<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Draw",
 *     "name_underline"          =>"draw",
 *     "controller_name"         =>"Draw",
 *     "table_name"              =>"draw",
 *     "remark"                  =>"奖池管理"
 *     "api_url"                 =>"/api/wxapp/draw/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-05-14 09:37:18",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\DrawController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/draw/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/draw/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class DrawController extends AuthController
{

    //public function initialize(){
    //	//奖池管理
    //	parent::initialize();
    //}


    /**
     * 默认接口
     * /api/wxapp/draw/index
     * https://pw216.aejust.net/api/wxapp/draw/index
     */
    public function index()
    {
        $DrawInit  = new \init\DrawInit();//奖池管理   (ps:InitController)
        $DrawModel = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)

        $result = [];

        $this->success('奖池管理-接口请求成功', $result);
    }


    /**
     * 奖池管理 列表 (废弃)
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/find_draw_list",
     *
     *
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
     *
     *
     *
     *
     *
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/draw/find_draw_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/find_draw_list
     *   api:  /wxapp/draw/find_draw_list
     *   remark_name: 奖池管理 列表
     *
     */
    public function find_draw_list()
    {
        $DrawInit  = new \init\DrawInit();//奖池管理   (ps:InitController)
        $DrawModel = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段
        $result                  = $DrawInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 奖池管理 列表
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/find_draw_list2",
     *
     *
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
     *
     *
     *
     *
     *
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/draw/find_draw_list2
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/find_draw_list2
     *   api:  /wxapp/draw/find_draw_list2
     *   remark_name: 奖池管理 列表
     *
     */
    public function find_draw_list2()
    {
        $DrawInit  = new \init\DrawInit();//奖池管理   (ps:InitController)
        $DrawModel = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段
        $list                    = $DrawInit->get_list($where, $params);
        if (empty($list)) $this->error("暂无信息!");
        $result    = [];
        $positions = [
            ['x' => 0, 'y' => 0],
            ['x' => 1, 'y' => 0],
            ['x' => 2, 'y' => 0],
            ['x' => 2, 'y' => 1],
            ['x' => 2, 'y' => 2],
            ['x' => 1, 'y' => 2],
            ['x' => 0, 'y' => 2],
            ['x' => 0, 'y' => 1],
        ];

        foreach ($positions as $index => $pos) {
            $result[] = [
                'x'     => $pos['x'],
                'y'     => $pos['y'],
                'id'    => $list[$index]['id'],
                'fonts' => [['text' => $list[$index]['name'], 'top' => '25%', 'fontColor' => 'transparent']],
                'imgs'  => [['src' => $list[$index]['image'], 'width' => '70px', 'height' => '70px', 'top' => '8px']],
            ];
        }


        $this->success("请求成功!", $result);
    }


    /**
     * 奖池管理 详情  (废弃)
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/find_draw",
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/find_draw
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/find_draw
     *   api:  /wxapp/draw/find_draw
     *   remark_name: 奖池管理 详情
     *
     */
    public function find_draw()
    {
        $DrawInit  = new \init\DrawInit();//奖池管理    (ps:InitController)
        $DrawModel = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $DrawInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 抽奖  (废弃)
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/draw",
     *
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/draw
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/draw
     *   api:  /wxapp/draw/draw
     *   remark_name: 抽奖
     *
     */
    public function draw()
    {
        $DrawModel = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)


        //奖品列表
        $goods_list = $DrawModel->order('id desc')->select();


        //算出库存为0的总概率
        $empty_probability = 0;
        $effective_count   = 0;//有效奖品数量
        foreach ($goods_list as $goods) {
            if ($goods['stock'] == 0) $empty_probability += $goods['probability'];
            if ($goods['stock'] > 0) $effective_count++;
        }

        //如果有库存为0 重新计算概率
        if ($empty_probability) {
            $avg_probability   = $empty_probability / $effective_count;//平均概率
            $total_probability = 0;
            $tmp_goods_list    = [];//过滤库存为0的奖项
            foreach ($goods_list as $key => $goods) {
                if ($goods['stock'] > 0) {
                    $goods['probability'] = round($goods['probability'] + $avg_probability, 2);
                    if ($key == count($goods_list) - 1) {
                        //让总概率为100  最后一个奖项概率=100-之前奖项总和概率
                        $goods['probability'] = round(100 - $total_probability, 2);
                    } else {
                        $total_probability += $goods['probability'];
                    }
                    $tmp_goods_list[] = $goods;
                }
            }
            $goods_list = $tmp_goods_list;
        }

        // 生成一个0到100之间(带两位小数)的随机数
        $randomNum          = mt_rand(0, 10000) / 100;
        $currentProbability = 0;
        foreach ($goods_list as $goods) {
            $currentProbability += $goods['probability'];
            if ($randomNum <= $currentProbability) {
                $goods['currentProbability'] = $currentProbability;
                $goods['randomNum']          = $randomNum;
                $result['id']                = $goods['id'];
                $result['name']              = $goods['name'];
                $result['image']             = cmf_get_asset_url($goods['image']);
                break;
            }
        }


        //奖池库存减一
        $DrawModel->where('id', $result['id'])->dec('stock', 1)->update();


        return $result;
        //$this->success("请求成功!", $result);
    }


    /**
     * 兑换奖励 (废弃)
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/draw_code",
     *
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
     *    @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code 秘钥",
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/draw_code
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/draw_code
     *   api:  /wxapp/draw/draw_code
     *   remark_name: 兑换奖励
     *
     */
    public function draw_code()
    {
        $this->checkAuth();

        $DrawCodeModel = new \initmodel\DrawCodeModel(); //抽奖秘钥   (ps:InitModel)
        $DrawModel     = new \initmodel\DrawModel(); //奖池管理   (ps:InitModel)
        $DrawLogModel  = new \initmodel\DrawLogModel(); //抽奖记录   (ps:InitModel)


        $params = $this->request->param();


        $map       = [];
        $map[]     = ['code', '=', $params['code']];
        $code_info = $DrawCodeModel->where($map)->find();
        if (empty($code_info)) $this->error("抽奖码不存在");
        if ($code_info['status'] != 1) $this->error("抽奖码已使用");

        //奖品详情
        $draw_info = $DrawModel->where('id', '=', $code_info['draw_id'])->find();
        if (empty($draw_info)) $this->error("奖品错误!");


        //将抽奖码状态修改
        $DrawCodeModel->where($map)->strict(false)->update([
            'status'       => 2,
            'user_id'      => $this->user_id,
            'name'         => $draw_info['name'],
            'image'        => $draw_info['image'],
            'receive_time' => time(),
            'create_time'  => time(),
        ]);

        //抽奖记录
        $DrawLogModel->strict(false)->insert([
            'user_id'     => $this->user_id,
            'stock'       => 1,
            'code'        => $params['code'],
            'name'        => $draw_info['name'],
            'image'       => $draw_info['image'],
            'create_time' => time(),
        ]);


        $this->success("兑换成功!", $draw_info);
    }


    /**
     * 我的抽奖记录
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/my_draw_log",
     *
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/my_draw_log
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/my_draw_log
     *   api:  /wxapp/draw/my_draw_log
     *   remark_name: 我的抽奖记录
     *
     */
    public function my_draw_log()
    {
        $this->checkAuth();
        $DrawLogInit = new \init\DrawLogInit();//抽奖记录    (ps:InitController)


        $params = $this->request->param();

        $map    = [];
        $map[]  = ['user_id', '=', $this->user_id];
        $result = $DrawLogInit->get_list_paginate($map);


        if (empty($result)) $this->error("没有数据");


        $this->success("请求成功!", $result);
    }


    /**
     * 完善收货地址
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/set_address",
     *
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
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="抽奖记录id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="收货地址id",
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/set_address
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/set_address
     *   api:  /wxapp/draw/set_address
     *   remark_name: 完善收货地址
     *
     */
    public function set_address()
    {
        $this->checkAuth();
        $DrawLogModel     = new \initmodel\DrawLogModel(); //抽奖记录  (ps:InitModel)
        $ShopAddressModel = new \initmodel\ShopAddressModel();//地址管理


        $params = $this->request->param();

        //地址信息
        $address_info = $ShopAddressModel->where('id', '=', $params['address_id'])->find();
        if (empty($address_info)) $this->error('地址信息错误');
        $params['username'] = $address_info['username'];
        $params['phone']    = $address_info['phone'];
        $params['address']  = $address_info['address'];
        $params['province'] = $address_info['province'];
        $params['city']     = $address_info['city'];
        $params['county']   = $address_info['county'];
        $params['status']   = 2;


        //编辑订单信息
        $map      = [];
        $map[]    = ['id', '=', $params['id']];
        $log_info = $DrawLogModel->where($map)->find();
        if (empty($log_info)) $this->error("错误信息");
        if ($log_info['status'] != 1) $this->error("状态错误");

        $result = $DrawLogModel->where($map)->strict(false)->update($params);
        if (empty($result)) $this->error("失败请重试");


        $this->success("操作成功!");
    }


    /**
     * 确定收货 (确认完成)
     * @OA\Post(
     *     tags={"奖池管理"},
     *     path="/wxapp/draw/take_order",
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
     *   test_environment: http://pw216.ikun/api/wxapp/draw/take_order
     *   official_environment: https://pw216.aejust.net/api/wxapp/draw/take_order
     *   api: /wxapp/draw/take_order
     *   remark_name: 确定收货 (确认完成)
     *
     */
    public function take_order()
    {
        $this->checkAuth();

        $params       = $this->request->param();
        $DrawLogModel = new \initmodel\DrawLogModel(); //抽奖记录  (ps:InitModel)


        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        if ($params['order_num']) $where[] = ['order_num', '=', $params['order_num']];

        //用户确定收货
        $order_info = $DrawLogModel->where($where)->find();
        if (empty($order_info)) $this->error('暂无数据!');
        if (!in_array($order_info['status'], [4])) $this->error('非法操作!!');

        //处理订单
        $update['status']             = 8;
        $update['accomplish_time']    = time();
        $update['take_delivery_time'] = time();
        $update['update_time']        = time();
        $result                       = $DrawLogModel->where($where)->strict(false)->update($update);
        if (empty($result)) $this->error('失败请重试');


        $this->success("操作成功");
    }


}
