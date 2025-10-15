<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayClass",
    *     "name_underline"   =>"play_class",
    *     "table_name"       =>"play_class",
    *     "model_name"       =>"PlayClassModel",
    *     "remark"           =>"分类管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-14 10:10:23",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayClassModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayClassModel extends Model{

	protected $name = 'play_class';//分类管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
