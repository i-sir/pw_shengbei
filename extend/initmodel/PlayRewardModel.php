<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"PlayReward",
    *     "name_underline"   =>"play_reward",
    *     "table_name"       =>"play_reward",
    *     "model_name"       =>"PlayRewardModel",
    *     "remark"           =>"打赏管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-06-27 17:55:03",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\PlayRewardModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayRewardModel extends Model{

	protected $name = 'play_reward';//打赏管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
