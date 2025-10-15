<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\lib\Storage;
use Exception;
use initmodel\MemberPlayModel;
use initmodel\PlayPackageOrderModel;
use think\facade\Cache;
use think\facade\Db;
use cmf\lib\Upload;
use think\facade\Log;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WeChat\Oauth;
use WeChat\Script;
use WeMini\Crypt;
use WeMini\Qrcode;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');


error_reporting(0);

class PublicController extends AuthController
{
    public $wx_config;

    public function initialize()
    {
        parent::initialize();// 初始化方法

        $plugin_config        = cmf_get_option('weipay');
        $this->wx_system_type = $plugin_config['wx_system_type'];//默认 读配置可手动修改
        if ($this->wx_system_type == 'wx_mini') {//wx_mini:小程序
            $appid     = $plugin_config['wx_mini_app_id'];
            $appsecret = $plugin_config['wx_mini_app_secret'];
        } else {//wx_mp:公众号
            $appid     = $plugin_config['wx_mp_app_id'];
            $appsecret = $plugin_config['wx_mp_app_secret'];
        }
        $this->wx_config = [
            //微信基本信息
            'token'             => $plugin_config['wx_token'],
            'wx_mini_appid'     => $plugin_config['wx_mini_app_id'],//小程序 appid
            'wx_mini_appsecret' => $plugin_config['wx_mini_app_secret'],//小程序 secret
            'wx_mp_appid'       => $plugin_config['wx_mp_app_id'],//公众号 appid
            'wx_mp_appsecret'   => $plugin_config['wx_mp_app_secret'],//公众号 secret
            'appid'             => $appid,//读取默认 appid
            'appsecret'         => $appsecret,//读取默认 secret
            'encodingaeskey'    => $plugin_config['wx_encodingaeskey'],
            // 配置商户支付参数
            'mch_id'            => $plugin_config['wx_mch_id'],
            'mch_key'           => $plugin_config['wx_v2_mch_secret_key'],
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //	'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'           => './upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => './upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $this->wx_system_type,//wx_mini:小程序 wx_mp:公众号
        ];

    }


    /**
     * 测试接口
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/index
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/index
     *   api: /wxapp/public/index
     *   remark_name: 测试接口
     *
     * @return void
     */
    public function index()
    {
        $SendTempMsgController      = new SendTempMsgController();

        $send_data = [
            'thing20'           => ['value' => '张飒'],
            'character_string1' => ['value' => cmf_order_sn()],
            'thing19'           => ['value' =>'游戏名'],
            'amount4'           => ['value' => 18],
            'time5'             => ['value' => date('Y-m-d H:i:s')],
        ];


        $openid  ='o7QK-7YuP2llg0f4Qz-6t1sbqVh0';
        $openid  ='o7QK-7QGO1XNhrrhlcTcNeOPJBtk';

        //域名
        $url = cmf_get_domain() . "/h5/#/pages/order/index";
        $url = cmf_get_domain() . "/h5/#/pages/order/index";
        $SendTempMsgController->sendTempMsg($openid, 'WUtxLcep8pFkTk_v7029_DS_JYnAcARXtXRUFaP_730', $send_data,null,1,$url);



        $this->success('请求成功', []);
    }


    public function merchant_transfer()
    {
        $url               = $this->request->param('url');
        $WeChat            = new Script($this->wx_config);
        $return            = $WeChat->getJsSign(urldecode($url));
        $return['mchId']   = $this->wx_config['mch_id'];
        $return['package'] = 'affffddafdfafddffda==';

        $this->success("请求成功！", $return);
    }


    /**
     * 查询系统配置信息
     * @OA\Get(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_setting",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/find_setting
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/find_setting
     *   api: /wxapp/public/find_setting
     *   remark_name: 查询系统配置信息
     *
     */
    public function find_setting()
    {
        $config = cmf_config();

        $returnult = [];
        foreach ($config as $k => $v) {
            if (in_array($v['type'], ['img', 'file', 'video'])) {
                $v['value']            = cmf_get_asset_url($v['value']);
                $returnult[$v['name']] = $v['value'];
            } elseif ($v['type'] == 'textarea') {
                if ($v['scatter']) $v['value'] = preg_replace("/\r\n/", "", explode($v['scatter'], $v['value']));
                $returnult[$v['name']] = $v['value'];
            } elseif ($v['data_type'] == 'array' && $v['type'] == 'custom') {
                $returnult[$v['name']] = explode('/', $v['value']);//自定义表格
            } else {
                $returnult[$v['name']] = $v['value'];
            }

            if ($v['type'] == 'content') {
                // 协议不在这里展示
                unset($returnult[$v['name']]);
            }

            if ($v['value'] == 'true') $returnult[$v['name']] = true;
            if ($v['value'] == 'false') $returnult[$v['name']] = false;

            if ($v['is_label']) {
                //插架格式
                $value     = $v['value'];
                $new_value = [];
                foreach ($value as $key => $val) {
                    $new_value[$key]['label']   = $val;
                    $new_value[$key]['value']   = $val;
                    $new_value[$key]['checked'] = false;
                }
                $returnult[$v['name']] = $new_value;
            }

        }
        $this->success("请求成功！", $returnult);
    }


    /**
     * 查询协议列表
     * @OA\Get(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_agreement_list",
     *
     *
     *      @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="协议name ,选填,如传详情,不传列表 ",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/find_agreement_list
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/find_agreement_list
     *   api: /wxapp/public/find_agreement_list
     *   remark_name: 查询协议列表
     *
     */
    public function find_agreement_list()
    {
        $params = $this->request->param();
        if ($params['name']) {
            $returnult = cmf_replace_content_file_url(htmlspecialchars_decode(cmf_config($params['name'])));
        } else {
            $config    = cmf_config();
            $returnult = [];
            foreach ($config as $k => $v) {
                if ($v['type'] == 'content') {
                    if ($v['value']) $v['value'] = cmf_replace_content_file_url(htmlspecialchars_decode($v['value']));
                    $returnult[$v['name']] = $v['value'];
                } else {
                    unset($returnult[$v['name']]);
                }
            }
        }
        $this->success("请求成功！", $returnult);
    }


    /**
     * 上传图片
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/upload_asset",
     *
     *
     *      @OA\Parameter(
     *         name="filetype",
     *         in="query",
     *         description="默认image,其他video，audio，file",
     *         required=true,
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/upload_asset
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/upload_asset
     *   api: /wxapp/public/upload_asset
     *   remark_name: 上传图片
     *
     */
    public function upload_asset()
    {
        if ($this->request->isPost()) {
            session('user.id', 1);
            $uploader = new Upload();
            $fileType = $this->request->param('filetype', 'image');
            $uploader->setFileType($fileType);
            $returnult = $uploader->upload();
            if ($returnult === false) {
                $this->error($uploader->getError());
            } else {
                // TODO  增其它文件的处理
                $returnult['preview_url'] = cmf_get_image_preview_url($returnult["filepath"]);
                $returnult['url']         = cmf_get_image_url($returnult["filepath"]);
                $returnult['filename']    = $returnult["name"];
                $this->success('上传成功!', $returnult);
            }
        }
    }


    /**
     * 查询幻灯片
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *
     *     path="/wxapp/public/find_slide",
     *
     *
     * 	   @OA\Parameter(
     *         name="slide_id",
     *         in="query",
     *         description="幻灯片分类ID，默认传1，可不传",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/find_slide
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/find_slide
     *   api: /wxapp/public/find_slide
     *   remark_name: 查询幻灯片
     *
     */
    public function find_slide()
    {
        $params = $this->request->param();

        if (empty($params['slide_id'])) $params['slide_id'] = 1;

        $map   = [];
        $map[] = ['slide_id', '=', $params['slide_id']];
        $map[] = ['status', '=', 1];

        $returnult = Db::name('slide_item')->field("*")->where($map)->order('list_order asc')->select()->each(function ($item) {
            $item['image'] = cmf_get_asset_url($item['image']);
            return $item;
        });
        $this->success("请求成功!", $returnult);
    }


    /**
     * 查询导航列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_navs",
     *
     *
     *    @OA\Parameter(
     *         name="nav_id",
     *         in="query",
     *         description="导航ID 默认为1",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/find_navs
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/find_navs
     *   api: /wxapp/public/find_navs
     *   remark_name: 查询导航列表
     *
     */
    public function find_navs()
    {
        $params = $this->request->param();

        if (empty($params['nav_id'])) $params['nav_id'] = 1;

        $map   = [];
        $map[] = ['nav_id', '=', $params['nav_id']];
        $map[] = ['status', '=', 1];
        $map[] = ['parent_id', '=', 0];


        $returnult = Db::name("nav_menu")
            ->where($map)
            ->order('list_order asc')
            ->select()
            ->each(function ($item) {
                if ($item['icon']) $item['icon'] = cmf_get_asset_url($item['icon']);
                return $item;
            });

        $this->success("请求成功！", $returnult);
    }


    /**
     * H5授权登录
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/h5_login",
     *
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="state",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/h5_login
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/h5_login
     *   api: /wxapp/public/h5_login
     *   remark_name: H5授权登录
     *
     */
    public function h5_login()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $Init        = new InitController();//基础管理

        $params = $this->request->param();

        if (empty($params['code'])) $this->error('code不能为空');
        if (empty($params['state'])) $this->error('state不能为空');
        $state_arr = explode('/', $params['state']);
        $http      = $state_arr[0] . '//';
        $host      = $state_arr[2];

        $WeChat = new Oauth($this->wx_config);
        $return = $WeChat->getOauthAccessToken($params['code']);
        if (empty($return)) $this->error('登陆失败!');


        $h5_access_token = $return['access_token'];
        $openid          = $return['openid'];
        $UserData        = $WeChat->getUserInfo($h5_access_token, $openid);
        //        Log::write('h5_login:UserData');
        //        Log::write($UserData);

        //$state_arr[8] , 邀请码
        $pid = 0;
        if ($state_arr[8]) $pid = $MemberModel->where('invite_code', '=', $state_arr[8])->value('id');


        $findUserInfo = $this->getUserInfoByOpenid($openid);
        if (empty($findUserInfo)) {
            //注册
            $insert['nickname']    = urldecode($UserData['nickname']);
            $insert['avatar']      = $UserData['headimgurl'];
            $insert['openid']      = $UserData['openid'];
            $insert['createtime']  = time();
            $insert['create_time'] = time();
            $insert['login_time']  = time();
            $insert['ip']          = get_client_ip();
            $insert['login_city']  = $this->get_ip_to_city();
            $insert['pid']         = $pid;
            $insert['invite_code'] = $this->get_only_num('member', 'invite_code', 20, 2);

            //关闭邀请奖励
            //if ($pid) $Init->send_invitation_commission($pid);//给上级佣金


            $MemberModel->strict(false)->insert($insert);
        } else {
            //更新登录信息
            $update['nickname']   = urldecode($UserData['nickname']);
            $update['avatar']     = $UserData['headimgurl'];
            $update['login_time'] = time();
            $update['ip']         = get_client_ip();
            $update['login_city'] = $this->get_ip_to_city();


            if (empty($findUserInfo['pid']) && $pid) {
                $update['pid'] = $pid;
                //关闭邀请奖励
                //$Init->send_invitation_commission($pid);//给上级佣金
            }

            $MemberModel->strict(false)->where('openid', $openid)->update($update);
        }

        header('Location:' . $http . $host . '/h5/#/pages/public/login?openid=' . $openid);
    }


    /**
     * 静默获取openid
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @OA\Get(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/get_opneid",
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/get_opneid
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/get_opneid
     *   api: /wxapp/public/get_opneid
     *   remark_name: 静默获取openid
     *
     */
    public function get_opneid()
    {
        $params = $this->request->param();
        if (empty($params['code'])) $this->error('code不能为空');

        $WeChat = new Oauth($this->wx_config);
        $return = $WeChat->getOauthAccessToken($params['code']);
        if (empty($return)) $this->error('登陆失败!');
        $openid = $return['openid'];

        //处理数据
        $state_arr = explode('/', $params['state']);
        $http      = $state_arr[0] . '//';
        $host      = $state_arr[2];

        header('Location:' . $http . $host . '/h5/#/pages/user/index?wx_openid=' . $openid);
    }


    /**
     * 获取公众号分享签名
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/get_js_sign",
     *
     *
     *
     * 	   @OA\Parameter(
     *         name="url",
     *         in="query",
     *         description="url",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/get_js_sign
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/get_js_sign
     *   api: /wxapp/public/get_js_sign
     *   remark_name: 获取公众号分享签名
     *
     */
    public function get_js_sign()
    {
        $url             = $this->request->param('url');
        $WeChat          = new Script($this->wx_config);
        $return          = $WeChat->getJsSign(urldecode($url));
        $return['mchId'] = $this->wx_config['mch_id'];

        $this->success("请求成功！", $return);
    }


    /**
     * 获取手机验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/send_sms",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/send_sms
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/send_sms
     *   api: /wxapp/public/send_sms
     *   remark_name: 获取手机验证码
     *
     */
    public function send_sms()
    {
        $phone = $this->request->param('phone');
        $phone = trim($phone);
        if (empty($phone)) $this->error("手机号不能为空！");

        //检测该手机号是否是陪玩
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $is_member       = $MemberPlayModel->where('phone', '=', $phone)->count();
        if (empty($is_member)) $this->error('该手机号不存在,请联系客服!');


        $ali_sms = cmf_get_plugin_class("AliSms");
        $sms     = new $ali_sms();
        $code    = cmf_get_verification_code($phone);

        $params = ["mobile" => $phone, "code" => $code];

        cmf_verification_code_log($phone, $code);
        $returnult = $sms->sendMobileVerificationCode($params);

        if ($returnult['error'] == 0) {
            $this->success($returnult['message']);
        } else {
            $this->error($returnult['message']);
        }
    }


    /**
     * 获取 省市区
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_area",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/find_area
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/find_area
     *   api: /wxapp/public/find_area
     *   remark_name: 获取 省市区
     *
     */
    public function find_area()
    {
        if (cache('region_list')) {
            $area = cache('region_list');
        } else {
            $area = Db::name('region')->where('parent_id', '=', 10000000)->select()->each(function ($item, $key) {
                $item['value']    = $item['id'];
                $item['label']    = $item['name'];
                $item['children'] = Db::name("region")->where(['parent_id' => $item['id']])->select()->each(function ($item1, $key) {

                    $item1['children'] = Db::name("region")->where(['parent_id' => $item1['id']])->select()->each(function ($item2, $key) {
                        $item2['value'] = $item2['id'];
                        $item2['label'] = $item2['name'];

                        return $item2;
                    });

                    $item1['value'] = $item1['id'];
                    $item1['label'] = $item1['name'];
                    return $item1;
                });

                return $item;
            });
            cache("region_list", $area);
        }

        $this->success('list', $area);
    }


    /**
     * 翻译 误删
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/translate",
     *
     *
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="value",
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
     *   test_environment: http://pw216.ikun/api/wxapp/public/translate
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/translate
     *   api: /wxapp/public/translate
     *   remark_name: 翻译 误删
     *
     */
    public function translate()
    {
        $value = $this->request->param('value');

        $translate = new \init\TranslateInit();
        $returnult = $translate->translate($value);

        if (isset($returnult) && $returnult) {
            $this->success('翻译结果', $returnult['trans_result'][0]['dst']);
        }
    }


    /**
     * 获取超稳定 access_token
     * 该接口调用频率限制为 1万次 每分钟，每天限制调用 50万 次
     * @return mixed
     *
     *
     *   test_environment: http://pw216.ikun/api/wxapp/public/get_stable_access_token
     *   official_environment: https://pw216.aejust.net/api/wxapp/public/get_stable_access_token
     *   api: /wxapp/public/get_stable_access_token
     *   remark_name: 获取超稳定 access_token
     *
     */
    public function get_stable_access_token()
    {
        $appid  = 'wxcecfab687710cd6a';
        $secret = 'c9fee3915c658ed5472b7e5bc328b195';
        $url2   = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        //小程序信息获取token
        $param['grant_type'] = 'client_credential';
        $param['appid']      = $appid;
        $param['secret']     = $secret;
        $return              = $this->curl_post($url2, json_encode($param));
        $data                = json_decode($return, true);
        $token               = $data['access_token'];
        return $token;
    }
}
