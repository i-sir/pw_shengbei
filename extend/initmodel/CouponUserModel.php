<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"CouponUser",
    *     "name_underline"   =>"coupon_user",
    *     "table_name"       =>"shop_coupon_user",
    *     "model_name"       =>"CouponUserModel",
    *     "remark"           =>"优惠券领取记录",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-25 14:15:02",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\CouponUserModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class CouponUserModel extends Model{

	protected $name = 'shop_coupon_user';//优惠券领取记录

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
