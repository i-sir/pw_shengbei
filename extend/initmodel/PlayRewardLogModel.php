<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"PlayRewardLog",
 *     "name_underline"   =>"play_reward_log",
 *     "table_name"       =>"play_reward_log",
 *     "model_name"       =>"PlayRewardLogModel",
 *     "remark"           =>"打赏记录",
 *     "author"           =>"",
 *     "create_time"      =>"2024-06-27 18:04:42",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\PlayRewardLogModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayRewardLogModel extends Model
{

    protected $name = 'play_reward_log';//打赏记录

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
