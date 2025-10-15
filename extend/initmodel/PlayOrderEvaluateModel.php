<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayOrderEvaluate",
    *     "name_underline"   =>"play_order_evaluate",
    *     "table_name"       =>"play_order_evaluate",
    *     "model_name"       =>"PlayOrderEvaluateModel",
    *     "remark"           =>"资源评价",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-24 17:16:32",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayOrderEvaluateModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayOrderEvaluateModel extends Model{

	protected $name = 'play_order_evaluate';//资源评价

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
