<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;


use think\facade\Log;

class ChatController extends AuthController
{

    public $uni_appid         = '__UNI__F0B321B';        
    public $requestAuthSecret = 'pnNj82VA0RPoHB8XJ7gcJQ==';
    public $baseIDUrl         = 'https://fc-mp-cfd5f11b-b775-4397-b068-6aab68fb61bb.next.bspapp.com/yuejiclub';

    /**
     * 升级了一下,聊天
     * @OA\Post(
     *     tags={"聊天"},
     *     summary="获取uid",
     *     path="/wxapp/chat/get_uid",
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
     *    @OA\Response(response="default", description="An example resource")
     * )
     *
     * https://pw216.aejust.net/api/wxapp/chat/get_uid?openid=olS7D6q9Yz-5xzFJxUpQfuVdgTBQ
     *
     */
    public function get_uid()
    {
        $this->checkAuth();
        $MemberModel     = new \initmodel\MemberModel();//用户管理
        $MemberPlayModel = new \initmodel\MemberPlayModel(); //陪玩管理  (ps:InitModel)

        //系统头像
        $app_logo = cmf_config('app_logo');


        //系统名称
        $app_name = cmf_config('app_name');


        if (empty($this->user_info['uni_token'])) {
            //注册
            $url    = $this->baseIDUrl . '/externalRegister';
            $params = [
                'externalUid' => (string)"{$this->user_id}_{$this->user_info['identity_type']}",
                'nickname'    => $this->filterEmoji($this->user_info['nickname'] ?? $app_name),
                'avatar'      => cmf_get_asset_url($this->user_info['avatar'] ?? $app_logo)
            ];
        } else {
            //登录
            $url    = $this->baseIDUrl . '/externalLogin';
            $params = [
                'externalUid' => (string)"{$this->user_id}_{$this->user_info['identity_type']}",
            ];
        }


        $res = $this->getCurlData($url, $params);
//        Log::write('get_uid:res');
//        Log::write($res);


        if (isset($res['errCode']) && $res['errCode'] === 0) {
            $update['uni_token']         = $res['newToken']['token'];
            $update['uni_uid']           = $res['uid'];
            $update['uni_token_expired'] = $res['newToken']['tokenExpired'];
            $update['externalUid']       = $params['externalUid'];

            if ($this->user_info['identity_type'] == 'user') $MemberModel::where(['id' => $this->user_id])->save($update);
            if ($this->user_info['identity_type'] == 'play') $MemberPlayModel::where(['id' => $this->user_id])->save($update);


            unset($update['externalUid']);
            $this->success('获取成功', $update);
        } else {
            $this->error($res['errCode'] ?? $res['error']['message']);
        }

    }


    //去除昵称的表情问题
    function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        //设置最多字符长度
        $str = mb_substr($str, 0, 9, 'utf-8');

        return $str;
    }


    /**
     * @OA\Post(
     *     tags={"聊天"},
     *     summary="更新用户信息",
     *     path="/wxapp/chat/update_user",
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function update_user()
    {
        $this->checkAuth();

        //系统头像
        $app_logo = cmf_config('app_logo');

        //系统名称
        $app_name = cmf_config('app_name');

        $url    = $this->baseIDUrl . '/updateUserInfoByExternal';
        $params = [
            "externalUid" => (string)"{$this->user_id}_{$this->user_info['identity_type']}",
            'nickname'    => $this->filterEmoji($this->user_info['nickname'] ?? $app_name),
            'avatar'      => cmf_get_asset_url($this->user_info['avatar'] ?? $app_logo)
        ];

        $res    = $this->getCurlData($url, $params);
        if (isset($res['errCode']) && $res['errCode'] === 0) {
            $this->success('更新成功');
        } else {
            $this->error($res['errMsg'] ?? '错误');
        }
    }

    public function getCurlData($url, $params)
    {
        $nonce     = sprintf("%d", rand());
        $timestamp = $this->getMilliseconds();
        $signature = $this->getSignature($params, $nonce, $timestamp);

        $data['params']     = $params;
        $data['clientInfo'] = ['uniPlatform' => 'web', 'appId' => $this->uni_appid];

        $header = [
            'uni-id-nonce:' . $nonce,
            'uni-id-timestamp:' . $timestamp,
            'uni-id-signature:' . $signature,
            'Content-Type:application/json',
        ];

        return json_decode($this->curl_post($url, json_encode($data), $header), true);
    }

    // 获取当前时间的毫秒级时间戳
    protected function getMilliseconds()
    {
        // 获取当前时间的秒级时间戳
        list($msec, $sec) = explode(' ', microtime());
        // 将秒级时间戳转换为毫秒级时间戳
        return (int)(($sec * 1000) + ($msec * 1000));
    }


    public function getSignature($params, $nonce, $timestamp)
    {
        $requestAuthSecret = $this->requestAuthSecret;
        $paramsStr         = $this->getParamsString($params);
        $signature         = hash_hmac('sha256', ((string)$timestamp . $paramsStr), ($requestAuthSecret . $nonce));

        return strtoupper($signature);
    }

    private function getParamsString($params)
    {
        ksort($params);
        $paramsStr = [];
        foreach ($params as $key => $value) {
            if (gettype($value) == "array" || gettype($value) == "object") {
                continue;
            }
            array_push($paramsStr, $key . '=' . $value);
        }
        return join('&', $paramsStr);
    }


    public function curl_post($url, $post_data, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, 0);//返回response头部信息
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


}
