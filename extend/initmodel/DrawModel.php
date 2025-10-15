<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"Draw",
    *     "name_underline"   =>"draw",
    *     "table_name"       =>"draw",
    *     "model_name"       =>"DrawModel",
    *     "remark"           =>"奖池管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-05-14 09:37:18",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\DrawModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class DrawModel extends Model{

	protected $name = 'draw';//奖池管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
