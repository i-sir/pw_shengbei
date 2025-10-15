<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayPackageSku",
    *     "name_underline"   =>"play_package_sku",
    *     "table_name"       =>"play_package_sku",
    *     "model_name"       =>"PlayPackageSkuModel",
    *     "remark"           =>"规格管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-15 10:17:44",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayPackageSkuModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayPackageSkuModel extends Model{

	protected $name = 'play_package_sku';//规格管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
