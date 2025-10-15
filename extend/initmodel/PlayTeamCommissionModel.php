<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"PlayTeamCommission",
 *     "name_underline"   =>"play_team_commission",
 *     "table_name"       =>"play_team_commission",
 *     "model_name"       =>"PlayTeamCommissionModel",
 *     "remark"           =>"团队佣金比例设置",
 *     "author"           =>"",
 *     "create_time"      =>"2024-04-19 10:46:09",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\PlayTeamCommissionModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayTeamCommissionModel extends Model
{

    protected $name = 'play_team_commission';//团队佣金比例设置

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
