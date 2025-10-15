<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"MemberLevel",
    *     "name_underline"   =>"member_level",
    *     "table_name"       =>"member_level",
    *     "model_name"       =>"MemberLevelModel",
    *     "remark"           =>"陪玩等级",
    *     "author"           =>"",
    *     "create_time"      =>"2024-04-24 10:09:41",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\MemberLevelModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberLevelModel extends Model{

	protected $name = 'member_level';//陪玩等级

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
