<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayPackage",
    *     "name_underline"   =>"play_package",
    *     "table_name"       =>"play_package",
    *     "model_name"       =>"PlayPackageModel",
    *     "remark"           =>"套餐管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-14 11:18:32",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayPackageModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayPackageModel extends Model{

	protected $name = 'play_package';//套餐管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
