<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayHire",
    *     "name_underline"   =>"play_hire",
    *     "table_name"       =>"play_hire",
    *     "model_name"       =>"PlayHireModel",
    *     "remark"           =>"租号管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-12-09 16:14:48",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayHireModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayHireModel extends Model{

	protected $name = 'play_hire';//租号管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
