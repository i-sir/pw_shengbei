<?php

namespace app\admin\validate;

use think\Validate;


/**
    * @AdminModel(
    *     "name"             =>"Draw",
    *     "name_underline"   =>"draw",
    *     "table_name"       =>"draw",
    *     "validate_name"    =>"DrawValidate",
    *     "remark"           =>"奖池管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-05-14 09:37:18",
    *     "version"          =>"1.0",
    *     "use"              =>   $this->validate($params, Draw);
    * )
    */

class DrawValidate extends Validate
{

protected $rule = [];




protected $message = [];




//软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',

//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];


}
