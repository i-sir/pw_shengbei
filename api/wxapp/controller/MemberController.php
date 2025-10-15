<?php
// +----------------------------------------------------------------------
// | 会员中心
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use init\QrInit;
use think\facade\Db;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');

error_reporting(0);

class MemberController extends AuthController
{
    public function initialize()
    {
        parent::initialize();//初始化方法

    }


    /**
     * 查询会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_member",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/member/find_member
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/find_member
     *   api: /wxapp/member/find_member
     *   remark_name: 查询会员信息
     *
     */
    public function find_member()
    {
        $this->checkAuth();
        //查询会员信息
        $result = $this->getUserInfoByOpenid($this->openid);

        $this->success("请求成功!", $result);
    }


    /**
     * 更新会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/update_member",
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
     *     @OA\Parameter(
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
     *     @OA\Parameter(
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
     *     @OA\Parameter(
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
     *      @OA\Parameter(
     *         name="used_pass",
     *         in="query",
     *         description="旧密码,如需要传,不需要请勿传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="更改密码,如需要传,不需要请勿传",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member/update_member
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/update_member
     *   api: /wxapp/member/update_member
     *   remark_name: 更新会员信息
     *
     */
    public function update_member()
    {
        $this->checkAuth();

        $MemberModel = new \initmodel\MemberModel();//用户管理


        $params                = $this->request->param();
        $params['update_time'] = time();
        $member                = $this->getUserInfoByOpenid($this->openid);


        //        $result = $this->validate($params, 'Member');
        //        if ($result !== true) $this->error($result);


        if (empty($member)) $this->error("该会员不存在!");
        if ($member['pid']) unset($params['pid']);


        //修改密码
        if ($params['pass']) {
            if (!cmf_compare_password($params['used_pass'], $member['pass'])) $this->error('旧密码错误');
            $params['pass'] = cmf_password($params['pass']);
        }

        $result = $MemberModel->where('id', $member['id'])->strict(false)->update($params);
        if ($result) {
            $result = $this->getUserInfoByOpenid($this->openid);
            $this->success("保存成功!", $result);
        } else {
            $this->error("保存失败!");
        }
    }


    /**
     * 账户(余额)变动明细
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"余额明细"},
     *     path="/wxapp/member/find_balance_list",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="2023-04-05",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="2023-04-05",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member/find_balance_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/find_balance_list
     *   api: /wxapp/member/find_balance_list
     *   remark_name: 账户(余额)变动明细
     *
     */
    public function find_balance_list()
    {
        $this->checkAuth();

        $params  = $this->request->param();
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = $this->getBetweenTime($params['begin_time'], $params['end_time']);

        if ($this->user_info['identity_type'] == 'user') {
            $result = Db::name("member_balance")
                ->where($where)
                ->order("id desc")
                ->paginate($params['page_size'])
                ->each(function ($item, $key) {
                    if ($item['type'] == 2) {
                        $item['price'] = -$item['price'];
                    } else {
                        $item['price'] = '+' . $item['price'];
                    }
                    return $item;
                });
        } else {
            $result = Db::name("member_balance_play")
                ->where($where)
                ->order("id desc")
                ->paginate($params['page_size'])
                ->each(function ($item, $key) {
                    if ($item['type'] == 2) {
                        $item['price'] = -$item['price'];
                    } else {
                        $item['price'] = '+' . $item['price'];
                    }
                    return $item;
                });
        }


        $this->success("请求成功！", $result);
    }


    /**
     * 账户(积分)变动明细
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"余额明细"},
     *     path="/wxapp/member/find_point_list",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="2023-04-05",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="2023-04-05",
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
     *   test_environment: http://pw216.ikun/api/wxapp/member/find_point_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/find_point_list
     *   api: /wxapp/member/find_point_list
     *   remark_name: 账户(积分)变动明细
     *
     */
    public function find_point_list()
    {
        $this->checkAuth();

        $params  = $this->request->param();
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = $this->getBetweenTime($params['begin_time'], $params['end_time']);


        $result = Db::name("member_point_play")
            ->where($where)
            ->order("id desc")
            ->paginate($params['page_size'])
            ->each(function ($item, $key) {
                if ($item['type'] == 2) {
                    $item['price'] = -$item['price'];
                } else {
                    $item['price'] = '+' . $item['price'];
                }
                return $item;
            });


        $this->success("请求成功！", $result);
    }


    /**
     * 团队列表查询
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_team_list",
     *
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="1 直接团队成员列表 2间接团队成员列表",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
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
     *   test_environment: http://pw216.ikun/api/wxapp/member/find_team_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/find_team_list
     *   api: /wxapp/member/find_team_list
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
                    //$item['second_total_fans'] = $MemberModel->where('spid', $item['id'])->count(); //间接下级数

                    return $item;
                });
        }
        $this->success("请求成功！", $result);
    }


    /**
     * 获客海报-公用,传入openid即可
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"邀请码"},
     *     path="/wxapp/member/poster",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/member/poster
     *   official_environment: https://pw216.aejust.net/api/wxapp/member/poster
     *   api: /wxapp/member/poster
     *   remark_name: 获客海报
     *
     */
    public function poster()
    {
        $this->checkAuth();

        $MemberModel     = new \initmodel\MemberModel();//用户管理
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)
        $Qr              = new QrInit();//二维码
        $url             = "https://pw216.aejust.net/h5/#/pages/index/index?invite_code={$this->user_info['invite_code']}";

        if (empty($this->user_info['invite_image'])) {
            //生成二维码
            $qr_url = $Qr->get_qr($url);
            $image  = $Qr->applet_share(cmf_get_asset_url($qr_url));

            //更新二维码信息
            $update['update_time']  = time();
            $update['invite_image'] = $image;
            if ($this->user_info['identity_type'] == 'user') {
                $MemberModel->where('id', '=', $this->user_id)->update($update);
            } else {
                $MemberPlayModel->where('id', '=', $this->user_id)->update($update);
            }
        } else {
            $image = $this->user_info['invite_image'];
        }


        $this->success('请求成功', cmf_get_asset_url($image));
    }


}