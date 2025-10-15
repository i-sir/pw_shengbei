<?php

namespace api\wxapp\controller;

use initmodel\MemberModel;

/**
 * @ApiController(
 *     "name"                    =>"Init",
 *     "name_underline"          =>"init",
 *     "controller_name"         =>"Init",
 *     "table_name"              =>"无",
 *     "remark"                  =>"基础接口,封装的接口"
 *     "api_url"                 =>"/api/wxapp/init/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-24 17:16:22",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\InitController();
 *     "test_environment"        =>"http://w.site/api/wxapp/init/index",
 *     "official_environment"    =>"https://lscs001.jscxkf.net/api/wxapp/init/index",
 * )
 */
class InitController extends AuthController
{
    /**
     * 本模块,用于封装常用方法,复用方法
     */


    /**
     * 给上级发放佣金
     * @param $p_user_id
     * https://pw216.aejust.net/api/wxapp/init/send_invitation_commission?p_user_id=2
     */
    public function send_invitation_commission($p_user_id = 0)
    {
        //邀请配置: 佣金[1] 优惠券[2]
        $invitation_configuration = cmf_config('invitation_configuration');

        if ($invitation_configuration == 1) {
            //邀请佣金
            $balance = cmf_config('invitation_commission');
            $remark  = "操作人[邀请奖励];操作说明[邀请好友得佣金];操作类型[佣金奖励];";//管理备注
            MemberModel::inc_balance($p_user_id, $balance, '邀请奖励', $remark, 0, cmf_order_sn(), 10);
        }
        if ($invitation_configuration == 2) {
            //优惠券id
            $coupon_id       = cmf_config('coupon_id');
            $CouponUserInit  = new \init\CouponUserInit();//优惠券领取记录   (ps:InitController)
            $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

            //优惠券信息
            $coupon_info = $ShopCouponModel->where('id', '=', $coupon_id)->find();

            //插入领取记录
            $insert['coupon_id']   = $coupon_id;
            $insert['user_id']     = $p_user_id;
            $insert['start_time']  = $coupon_info['start_time'];
            $insert['end_time']    = $coupon_info['end_time'];
            $insert['amount']      = $coupon_info['amount'];
            $insert['full_amount'] = $coupon_info['full_amount'];
            $insert['name']        = $coupon_info['name'];
            $insert['code']        = $this->get_only_num('shop_coupon_user', 'code', 20, 2);
            $CouponUserInit->api_edit_post($insert);

            //领取优惠券,增加数量
            $ShopCouponModel->where('id', '=', $coupon_id)->inc('select_count', 1)->update();
        }

        return "true";
    }


}