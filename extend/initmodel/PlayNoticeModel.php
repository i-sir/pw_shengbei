<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayNotice",
    *     "name_underline"   =>"play_notice",
    *     "table_name"       =>"play_notice",
    *     "model_name"       =>"PlayNoticeModel",
    *     "remark"           =>"通知管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-14 10:00:36",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayNoticeModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayNoticeModel extends Model{

	protected $name = 'play_notice';//通知管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
