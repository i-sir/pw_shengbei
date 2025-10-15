<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayClassTwo",
    *     "name_underline"   =>"play_class_two",
    *     "table_name"       =>"play_class",
    *     "model_name"       =>"PlayClassTwoModel",
    *     "remark"           =>"分类管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-14 10:11:49",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayClassTwoModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayClassTwoModel extends Model{

	protected $name = 'play_class';//分类管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
