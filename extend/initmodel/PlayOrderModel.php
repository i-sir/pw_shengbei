<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayOrder",
    *     "name_underline"   =>"play_order",
    *     "table_name"       =>"play_order",
    *     "model_name"       =>"PlayOrderModel",
    *     "remark"           =>"陪玩管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-24 11:31:14",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayOrderModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayOrderModel extends Model{

	protected $name = 'play_order';//陪玩管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
