<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayList",
    *     "name_underline"   =>"play_list",
    *     "table_name"       =>"play_list",
    *     "model_name"       =>"PlayListModel",
    *     "remark"           =>"游戏列表",
    *     "author"           =>"",
    *     "create_time"      =>"2024-12-09 16:15:24",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayListModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayListModel extends Model{

	protected $name = 'play_list';//游戏列表

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
