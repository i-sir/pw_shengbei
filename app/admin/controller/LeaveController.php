<?php

namespace app\admin\controller;


use think\facade\Db;

/**
 * @adminMenuRoot(
 *     'name'   =>'Leave',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'cogs',
 *     'remark' =>''
 * )
 */
class LeaveController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 展示
     * @adminMenu(
     *     'name'   => 'Leave',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        $params = $this->request->param();
        
        $where = [];
        if ($params["keyword"]) $where[] = ["nickname|phone", "like", "%{$params["keyword"]}%"];


        $list = Db::name('leave')
            ->where($where)
            ->order('id desc')
            ->paginate([
                'list_rows' => 20,
                'query'     => $this->request->param()
            ])->each(function ($item, $key) use ($MemberModel) {
                if ($item['user_id']) {
                    $user_info = $MemberModel->where('id', '=', $item['user_id'])->find();
                    if ($user_info['avatar']) $user_info['avatar'] = cmf_get_asset_url($user_info['avatar']);
                    $item['user_info'] = $user_info;
                }
                return $item;
            });


        $this->assign("list", $list);
        $this->assign('page', $list->render());//单独提取分页出来

        return $this->fetch();
    }


}
