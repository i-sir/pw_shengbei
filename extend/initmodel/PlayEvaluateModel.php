<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayEvaluate",
    *     "name_underline"   =>"play_evaluate",
    *     "table_name"       =>"play_evaluate",
    *     "model_name"       =>"PlayEvaluateModel",
    *     "remark"           =>"陪玩评价",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-24 17:16:17",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayEvaluateModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayEvaluateModel extends Model{

	protected $name = 'play_evaluate';//陪玩评价

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
