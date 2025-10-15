<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"MemberPointOrder",
    *     "name_underline"   =>"member_point_order",
    *     "table_name"       =>"member_point_order",
    *     "model_name"       =>"MemberPointOrderModel",
    *     "remark"           =>"充值保证金",
    *     "author"           =>"",
    *     "create_time"      =>"2025-06-09 16:21:34",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\MemberPointOrderModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberPointOrderModel extends Model{

	protected $name = 'member_point_order';//充值保证金

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
