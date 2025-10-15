<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"MemberPlay",
 *     "name_underline"          =>"member_play",
 *     "controller_name"         =>"MemberPlay",
 *     "table_name"              =>"member_play",
 *     "remark"                  =>"陪玩管理"
 *     "api_url"                 =>"/api/wxapp/member_play/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-23 14:33:08",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\MemberPlayController();
 *     "test_environment"        =>"http://pw216.ikun/api/wxapp/member_play/index",
 *     "official_environment"    =>"https://pw216.aejust.net/api/wxapp/member_play/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class MemberPlayController extends AuthController
{


    public function initialize()
    {
        //陪玩管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/member_play/index
     * https://pw216.aejust.net/api/wxapp/member_play/index
     */
    public function index()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        $result = [];

        $this->success('陪玩管理-接口请求成功', $result);
    }


    /**
     * 手机号密码注册 (不用)
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/add_user_pass",
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="验证码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="invite_code",
     *         in="query",
     *         description="上级邀请码",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/add_user_pass
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/add_user_pass
     *   api: /wxapp/member_play/add_user_pass
     *   remark_name: 手机号密码注册
     *
     */
    public function add_user_pass()
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");
        if (empty($params['code'])) $this->error("验证码不能为空！");
        if (empty($params['pass'])) $this->error("密码不能为空！");
        //密码加密
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);

        $result = cmf_check_verification_code($params['phone'], $params['code']);
        if ($result) $this->error($result);


        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $MemberPlayModel->where($map)->find();


        $pid = 0;
        if ($params['invite_code']) $pid = $MemberPlayModel->where('invite_code', '=', $params['invite_code'])->value('id');


        if (empty($findUserInfo)) {
            $insert['nickname']    = $this->get_member_wx_nickname(2);
            $insert['avatar']      = cmf_config('app_logo');
            $insert['phone']       = $params['phone'];
            $insert['openid']      = 'o2x' . $this->insertRandomUnderscore(md5(cmf_random_string(60, 3)));
            $insert['create_time'] = time();
            $insert['login_time']  = time();
            $insert['ip']          = get_client_ip();
            $insert['login_city']  = $this->get_ip_to_city();
            $insert['pid']         = $pid;
            $insert['pass']        = $params['pass'];
            $insert['invite_code'] = $this->get_only_num('member_play', 'invite_code', 20, 2);


            $MemberPlayModel->strict(false)->insert($insert);

            $this->success("注册成功！");
        } else {
            $this->error("你已经注册过了！");
        }
    }


    /**
     * 手机号密码登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/pass_login",
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/pass_login
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/pass_login
     *   api: /wxapp/member_play/pass_login
     *   remark_name: 手机号密码登录
     *
     */
    public function pass_login()
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");
        if (empty($params['pass'])) $this->error("密码不能为空！");


        //登录记录表
        Db::name('login_log')->strict(false)->insert([
            'phone'       => $params['phone'],
            'ip'          => get_client_ip(),
            'create_time' => time(),
        ]);


        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $MemberPlayModel->where($map)->find();
        if (empty($findUserInfo)) $this->error("账户不存在，请先注册！");
        if ($findUserInfo['is_block'] == 1) $this->error("等稍后在尝试登录!!");

        //检测密码是否正确
        if (!cmf_compare_password($params['pass'], $findUserInfo['pass'])) $this->error("密码错误！");

        //更新登录ip,时间,城市,唯一标示
        $openid = 'o2x' . md5(cmf_random_string(60, 3));//更改openid
        $MemberPlayModel->where('id', '=', $findUserInfo['id'])->strict(false)->update([
            'update_time'     => time(),
            'login_time'      => time(),
            'ip'              => get_client_ip(),
            'only_login_code' => cmf_random_string(40),
            'login_city'      => $this->get_ip_to_city(),
            'openid'          => $openid,
        ]);


        //查询会员信息
        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $MemberPlayModel->where($map)->find();
        $findUserInfo = $this->getUserInfoByOpenid($findUserInfo['openid']);


        $this->success("登录成功！", $findUserInfo);
    }


    /**
     * 手机号验证码登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException|\think\Exception
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/sms_login",
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="验证码",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/sms_login
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/sms_login
     *   api: /wxapp/member_play/sms_login
     *   remark_name: 手机号验证码登录
     *
     */
    public function sms_login()
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");
        if (empty($params['code'])) $this->error("验证码不能为空！");

        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $MemberPlayModel->where($map)->find();
        if (empty($findUserInfo)) $this->error("用户不存在，请先注册！");
        //if ($findUserInfo['is_block'] == 1) $this->error("账号异常");


        //登录记录表
        Db::name('login_log')->strict(false)->insert([
            'phone'       => $params['phone'],
            'ip'          => get_client_ip(),
            'create_time' => time(),
        ]);

        $result = cmf_check_verification_code($params['phone'], $params['code']);
        if ($result) $this->error($result);


        //更新登录ip,时间,城市,唯一标示
        $findUserInfo['openid'] = 'o2x' . md5(cmf_random_string(60, 3));//更改openid
        $MemberPlayModel->where('id', '=', $findUserInfo['id'])->strict(false)->update([
            'update_time'     => time(),
            'login_time'      => time(),
            'ip'              => get_client_ip(),
            'only_login_code' => cmf_random_string(40),
            'openid'          => $findUserInfo['openid'],
            'login_city'      => $this->get_ip_to_city(),
        ]);

        //查询陪玩信息
        $findUserInfo = $this->getUserInfoByOpenid($findUserInfo['openid']);

        $this->success("登录成功！", $findUserInfo);
    }


    /**
     * 修改密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/update_pass",
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="根据验证码修改密码 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="old_pass",
     *         in="query",
     *         description="根据旧密码修改 二选一",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/update_pass
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/update_pass
     *   api: /wxapp/member_play/update_pass
     *   remark_name: 修改密码
     *
     */
    public function update_pass()
    {
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");


        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $MemberPlayModel->where($map)->find();
        if (empty($findUserInfo)) $this->error("用户不存在，请先注册！");

        //旧密码修改检测,是否正确
        if ($params['old_pass'] && !cmf_compare_password($params['old_pass'], $findUserInfo['pass'])) $this->error("旧密码错误！");


        //验证码修改
        if ($params['code']) {
            $result = cmf_check_verification_code($params['phone'], $params['code']);
            if ($result) $this->error($result);
        }


        //更新登录ip,时间,城市,修改密码
        $MemberPlayModel->where($map)->strict(false)->update([
            'pass'        => cmf_password($params['pass']),
            'update_time' => time(),
            'login_time'  => time(),
            'ip'          => get_client_ip(),
            'login_city'  => $this->get_ip_to_city(),
        ]);


        //查询会员信息
        $findUserInfo = $this->getUserInfoByOpenid($findUserInfo['openid']);
        $this->success("登录成功！", $findUserInfo);
    }


    /**
     * 陪玩列表
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/find_member_play_list",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/find_member_play_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/find_member_play_list
     *   api:  /wxapp/member_play/find_member_play_list
     *   remark_name: 陪玩列表
     *
     */
    public function find_member_play_list()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理   (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['is_block', '=', 2];
        if ($params["keyword"]) $where[] = ["nickname|phone", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];
        if ($params['play_id']) $where[] = ['', 'EXP', Db::raw("FIND_IN_SET({$params['play_id']},play_ids)")];
        if ($params["is_index"]) $where[] = ["is_index", "=", 1];
        if ($params["area"]) $where[] = ['area', 'like', "%{$params['area']}%"];
        if ($params['class_id']) $where[] = ['', 'EXP', Db::raw("FIND_IN_SET({$params['class_id']},play_ids)")];


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["field"]         = "*";//接口类型
        $result                  = $MemberPlayInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 陪玩管理 详情
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/find_member_play",
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
     *         description="openid 查自己",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/find_member_play
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/find_member_play
     *   api:  /wxapp/member_play/find_member_play
     *   remark_name: 陪玩管理 详情
     *
     */
    public function find_member_play()
    {
        $MemberPlayInit  = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['openid']) $where[] = ["openid", "=", $params["openid"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["field"]         = "*";//接口类型
        $result                  = $MemberPlayInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 修改信息
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/update_play",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/update_play
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/update_play
     *   api:  /wxapp/member_play/update_play
     *   remark_name: 修改信息
     *
     */
    public function update_play()
    {
        $MemberPlayInit = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)

        //参数
        $params       = $this->request->param();
        $params['id'] = $this->user_id;


        $result = $MemberPlayInit->edit_post_two($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功!");
    }


    /**
     * 团队列表查询
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/find_team_list",
     *
     *
     *
     *
     *     @OA\Parameter(
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/find_team_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/find_team_list
     *   api: /wxapp/member_play/find_team_list
     *   remark_name: 团队列表查询
     *
     */
    public function find_team_list()
    {
        $this->checkAuth();
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $params  = $this->request->param();
        $user_id = $this->user_id;
        if ($params['user_id']) {
            $user_id = $params['user_id'];
        }

        if ($params['type'] == 2) {
            $result = $MemberModel
                ->where("spid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel) {
                    $item['avatar']           = cmf_get_asset_url($item['avatar']);
                    $item['child_total_fans'] = $MemberModel->where('pid', $item['id'])->count(); //直接下级数
                    //$item['second_total_fans'] = $MemberModel->where('spid', $item['id'])->count(); //间接下级数

                    return $item;
                });
        } else {
            $result = $MemberModel
                ->where("pid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel) {
                    $item['avatar']           = cmf_get_asset_url($item['avatar']);
                    $item['child_total_fans'] = $MemberModel->where('pid', $item['id'])->count(); //直接下级数

                    return $item;
                });
        }
        $this->success("请求成功！", $result);
    }


    /**
     * 统计管理
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/statistics",
     *
     *
     *
     *    @OA\Parameter(
     *         name="date_type",
     *         in="query",
     *         description="1今日,2本周,3本月",
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
     *         description="openid 查自己",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/statistics
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/statistics
     *   api:  /wxapp/member_play/statistics
     *   remark_name: 统计管理
     *
     */
    public function statistics()
    {
        $this->checkAuth();
        $PlayUserOrderModel = new \initmodel\PlayUserOrderModel(); //陪玩管理   (ps:InitModel)

        $params = $this->request->param();
        $date   = date('Y-m-d');

        /**  获取当日,开始,结束时间戳 */
        if (empty($params['date_type']) || $params['date_type'] == 1) {
            $startTimeStamp = strtotime(date("{$date} 00:00:00"));
            $endTimeStamp   = strtotime(date("{$date} 23:59:59"));
        }


        /**  获取本周,开始,结束时间戳 */
        if ($params['date_type'] == 2) {
            $currentDayOfWeek = date('w', strtotime($date));
            $currentTimeStamp = strtotime($date);
            $startTimeStamp   = strtotime('-' . $currentDayOfWeek . ' days', $currentTimeStamp);
            $endTimeStamp     = strtotime('+' . (6 - $currentDayOfWeek) . ' days', $currentTimeStamp) + 86399; // 86399秒是一天的最后一秒
        }


        /** 获取本月,开始,结束时间戳 */
        if ($params['date_type'] == 3) {
            $currentYearMonth = date('Y-m', strtotime($date));
            $startTimeStamp   = strtotime($currentYearMonth . '-01 00:00:00');
            $endTimeStamp     = strtotime(date('Y-m-d', strtotime($currentYearMonth . '+1 month')) . ' 00:00:00') - 1;
        }

        $map10   = [];
        $map10[] = ['create_time', 'between', [$startTimeStamp, $endTimeStamp]];


        //佣金合计
        $map20   = [];
        $map20[] = ['send_time', '>', 0];
        $map20[] = ['user_id', '=', $this->user_id];
        $map20[] = ['create_time', 'between', [$startTimeStamp, $endTimeStamp]];


        $result['total_commission'] = $PlayUserOrderModel->where($map20)->sum('commission_amount');

        //完成订单
        $result['total_accomplish'] = $PlayUserOrderModel->where($map20)->count();


        //取消订单
        $map30                  = [];
        $map30[]                = ['user_id', '=', $this->user_id];
        $map30[]                = ['status', '=', 10];
        $map30                  = array_merge($map10, $map30);
        $result['total_cancel'] = $PlayUserOrderModel->where($map30)->count();

        $this->success('请求成功!', $result);
    }


    /**
     * 等级列表
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/find_level_list",
     *
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/find_level_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/find_level_list
     *   api:  /wxapp/member_play/find_level_list
     *   remark_name: 等级列表
     *
     */
    public function find_level_list()
    {
        $MemberLevelInit = new \init\MemberLevelInit();//陪玩等级    (ps:InitController)

        $result['total_cancel'] = $MemberLevelInit->get_list();

        $this->success('请求成功!', $result);
    }


    /**
     * 所有人统计管理
     * @OA\Post(
     *     tags={"陪玩管理"},
     *     path="/wxapp/member_play/user_statistics",
     *
     *
     *
     *    @OA\Parameter(
     *         name="date_type",
     *         in="query",
     *         description="1今日,2本周,3本月",
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
     *         description="openid 查自己",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member_play/user_statistics
     *   official_environment: https://pw216.aejust.net/api/wxapp/member_play/user_statistics
     *   api:  /wxapp/member_play/user_statistics
     *   remark_name: 所有人 统计管理
     *
     */
    public function user_statistics()
    {
        //$this->checkAuth(); // 权限验证
        $PlayUserOrderModel = new \initmodel\PlayUserOrderModel(); // 陪玩订单模型
        $MemberPlayInit     = new \init\MemberPlayInit();//陪玩管理    (ps:InitController)
        $MemberPlayModel    = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)

        $params = $this->request->param(); // 获取请求参数
        $date   = date('Y-m-d'); // 当前日期

        // 1. 确定统计时间范围（今日/本周/本月）
        switch ($params['date_type'] ?? 1) {
            case 2: // 本周
                $startTimeStamp = strtotime('Monday this week');
                $endTimeStamp   = strtotime('Sunday this week 23:59:59');
                break;
            case 3: // 本月
                $startTimeStamp = strtotime('first day of this month 00:00:00');
                $endTimeStamp   = strtotime('last day of this month 23:59:59');
                break;
            default: // 今日（默认）
                $startTimeStamp = strtotime('today 00:00:00');
                $endTimeStamp   = strtotime('today 23:59:59');
        }

        // 2. 构建基础查询（已完成订单 + 时间范围）
        $query = $PlayUserOrderModel
            ->where('send_time', '>', 0)
            ->where('create_time', 'between', [$startTimeStamp, $endTimeStamp])
            ->field('user_id, SUM(commission_amount) as total_commission, COUNT(*) as total_accomplish')
            ->group('user_id');

        // 3. 排序控制（order=1 按收益降序，order=2 按接单数降序）
        if (isset($params['order'])) {
            if ($params['order'] == 1) {
                $query->order('total_commission DESC');
            } elseif ($params['order'] == 2) {
                $query->order('total_accomplish DESC');
            }
        }

        $userStats = $query->select()->each(function ($item, $key) use ($MemberPlayModel) {
            $user_info        = $MemberPlayModel->where('id', '=', $item['user_id'])->find();
            $item['nickname'] = $user_info['nickname'];
            $item['avatar']   = cmf_get_asset_url($user_info['avatar']);

            return $item;
        }); // 执行查询

        // 4. 返回结果
        $result = $userStats;

        // 5. 如果只需要当前用户数据
        if (!empty($params['current_user_only'])) {
            $currentUserStats = array_filter($userStats, function ($stat) {
                return $stat['user_id'] == $this->user_id;
            });
            $result           = reset($currentUserStats) ?: [
                'user_id'          => $this->user_id,
                'total_commission' => 0,
                'total_accomplish' => 0
            ];
        }

        $this->success('请求成功!', $result);
    }


}
