<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"PlayUserOrder",
 *     "name_underline"   =>"play_user_order",
 *     "table_name"       =>"play_order",
 *     "model_name"       =>"PlayUserOrderModel",
 *     "remark"           =>"陪玩管理",
 *     "author"           =>"",
 *     "create_time"      =>"2024-04-24 11:32:48",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\PlayUserOrderModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class PlayUserOrderModel extends Model
{

    protected $name = 'play_order';//陪玩管理

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
