<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"MemberPlayLog",
 *     "name_underline"   =>"member_play_log",
 *     "table_name"       =>"member_play_log",
 *     "model_name"       =>"MemberPlayLogModel",
 *     "remark"           =>"陪玩注册",
 *     "author"           =>"",
 *     "create_time"      =>"2025-05-15 16:55:42",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\MemberPlayLogModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberPlayLogModel extends Model
{

    protected $name = 'member_play_log';//陪玩注册

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
