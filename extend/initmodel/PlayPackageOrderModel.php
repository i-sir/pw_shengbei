<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayPackageOrder",
    *     "name_underline"   =>"play_package_order",
    *     "table_name"       =>"play_package_order",
    *     "model_name"       =>"PlayPackageOrderModel",
    *     "remark"           =>"套餐订单",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-15 14:19:33",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayPackageOrderModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayPackageOrderModel extends Model{

	protected $name = 'play_package_order';//套餐订单

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
