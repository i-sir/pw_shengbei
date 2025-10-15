<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"Statistics",
 *     "name_underline"      =>"statistics",
 *     "controller_name"     =>"Statistics",
 *     "table_name"          =>"statistics",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"统计管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-08-10 10:09:14",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\StatisticsController();
 * )
 */


use think\facade\Db;


class StatisticsController extends BaseController
{


    public function initialize()
    {
        //统计管理

        parent::initialize();

        //所有参数 赋值
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //处理 get参数
        $get              = $this->request->get();
        $this->params_url = "?" . http_build_query($get);
    }


    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'Statistics',
     *     'name_underline'   => 'statistics',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '统计管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $PlayRewardLogModel    = new \initmodel\PlayRewardLogModel(); //打赏记录  (ps:InitModel)


        $params = $this->request->param();


        //时间
        if (empty($params['beginTime'])) $params['beginTime'] = date("Y-m-d");
        if (empty($params['endTime'])) $params['endTime'] = date("Y-m-d");


        $map   = [];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);


        $withdrawal_map   = [];
        $withdrawal_map[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'pass_time');


        //用户数量
        $params['member_total'] = $MemberModel->where($map)->count();

        //订单数
        $map2                  = [];
        $map2[]                = ['status', 'in', [20, 25, 30, 40, 50, 60, 62]];
        $params['order_total'] = $PlayPackageOrderModel->where(array_merge($map, $map2))->count();

        //订单金额
        $params['order_amount'] = $PlayPackageOrderModel->where(array_merge($map, $map2))->sum('amount');


        //完成订单金额
        $map20                         = [];
        $map20[]                       = ['status', 'in', [50, 65]];
        $params['order_amount_finish'] = $PlayPackageOrderModel->where(array_merge($map, $map20))->sum('amount');

        //总订单数
        $map5                = [];
        $map5[]              = ['status', 'in', [20, 25, 30, 40, 50, 60, 62]];
        $params['order_all'] = $PlayPackageOrderModel->where($map5)->count();


        //陪玩总提现金额
        $map3                             = [];
        $map3[]                           = ['identity_type', '=', 'play'];
        $map3[]                           = ['status', '=', 2];
        $map3[]                           = ['price', '<=', 500];
        $params['play_withdrawal_amount'] = $MemberWithdrawalModel->where(array_merge($map3, $withdrawal_map))->sum('price');


        //用户总提现金额
        $map4                             = [];
        $map4[]                           = ['identity_type', '=', 'user'];
        $map4[]                           = ['status', '=', 2];
        $map4[]                           = ['price', '<=', 500];
        $params['user_withdrawal_amount'] = $MemberWithdrawalModel->where(array_merge($map4, $withdrawal_map))->sum('price');


        //打赏总金额
        $map6                    = [];
        $map6[]                  = ['status', '=', 2];
        $params['reward_amount'] = $PlayRewardLogModel->where(array_merge($map6, $map))->sum('amount');

        //结果
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }


    //陪玩统计
    public function play()
    {
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $PlayOrderModel        = new \initmodel\PlayOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayRewardLogModel    = new \initmodel\PlayRewardLogModel(); //打赏记录  (ps:InitModel)


        $params = $this->request->param();


        //时间
        if (empty($params['beginTime'])) $params['beginTime'] = date("Y-m-d");
        if (empty($params['endTime'])) $params['endTime'] = date("Y-m-d");


        //结果
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["nickname|phone", "like", "%{$params["keyword"]}%"];

        //时间筛选&陪玩订单
        $map   = [];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'send_time');

        //时间筛选&陪玩提现
        $map1   = [];
        $map1[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);


        if ($params["is_export"]) $this->export_excel($where, $params);


        //查询数据
        $result = $MemberPlayModel
            ->where($where)
            ->order('list_order,is_index,id desc')
            ->paginate(["list_rows" => $params["page_size"] ?? 10, "query" => $params])
            ->each(function ($item, $key) use ($params, $map, $map1, $PlayPackageOrderModel, $PlayRewardLogModel, $MemberWithdrawalModel, $PlayOrderModel) {


                //已完成订单数量
                $map2                = [];
                $map2[]              = ['send_time', '>', 1];
                $map2[]              = ['user_id', '=', $item['id']];
                $item['order_total'] = $PlayOrderModel->where(array_merge($map, $map2))->count();

                //已完成订单金额
                $item['order_amount'] = $PlayOrderModel->where(array_merge($map, $map2))->sum('commission_amount');


                //统计提现总金额
                $map3                      = [];
                $map3[]                    = ['user_id', '=', $item['id']];
                $map3[]                    = ['identity_type', '=', 'play'];
                $map3[]                    = ['status', '=', 2];//已提现
                $item['withdrawal_amount'] = $MemberWithdrawalModel->where(array_merge($map1, $map3))->sum('price');


                //打赏金额
                $map4                  = [];
                $map4[]                = ['play_user_id', '=', $item['id']];
                $map4[]                = ['status', '=', 2];
                $item['reward_amount'] = $PlayRewardLogModel->where(array_merge($map1, $map4))->sum('amount');

                return $item;
            });


        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来


        return $this->fetch();
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $MemberPlayModel       = new \initmodel\MemberPlayModel(); //陪玩管理   (ps:InitModel)
        $PlayPackageOrderModel = new \initmodel\PlayPackageOrderModel(); //套餐订单   (ps:InitModel)
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $PlayOrderModel        = new \initmodel\PlayOrderModel(); //陪玩管理   (ps:InitModel)
        $PlayRewardLogModel    = new \initmodel\PlayRewardLogModel(); //打赏记录  (ps:InitModel)


        //时间筛选&陪玩订单
        $map   = [];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'send_time');

        //时间筛选&陪玩提现
        $map1   = [];
        $map1[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);


        //查询数据
        $result = $MemberPlayModel
            ->where($where)
            ->order('list_order,is_index,id desc')
            ->select()
            ->each(function ($item, $key) use ($params, $map, $map1, $PlayRewardLogModel, $MemberWithdrawalModel, $PlayOrderModel) {


                //已完成订单数量
                $map2                = [];
                $map2[]              = ['send_time', '>', 1];
                $map2[]              = ['user_id', '=', $item['id']];
                $item['order_total'] = $PlayOrderModel->where(array_merge($map, $map2))->count();

                //已完成订单金额
                $item['order_amount'] = $PlayOrderModel->where(array_merge($map, $map2))->sum('commission_amount');


                //统计提现总金额
                $map3                      = [];
                $map3[]                    = ['user_id', '=', $item['id']];
                $map3[]                    = ['identity_type', '=', 'play'];
                $map3[]                    = ['status', '=', 2];//已提现
                $item['withdrawal_amount'] = $MemberWithdrawalModel->where(array_merge($map1, $map3))->sum('price');


                //打赏金额
                $map4                  = [];
                $map4[]                = ['play_user_id', '=', $item['id']];
                $map4[]                = ['status', '=', 2];
                $item['reward_amount'] = $PlayRewardLogModel->where(array_merge($map1, $map4))->sum('amount');


                //用户信息
                $item['userInfo'] = "(ID:{$item['id']}) {$item['nickname']}";


                return $item;
            });


        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "陪玩信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "手机号", "rowVal" => "phone", "width" => 20],
            ["rowName" => "余额", "rowVal" => "balance", "width" => 20],
            ["rowName" => "已完成订单数量", "rowVal" => "order_total", "width" => 20],
            ["rowName" => "已完成订单金额", "rowVal" => "order_amount", "width" => 20],
            ["rowName" => "打赏金额", "rowVal" => "reward_amount", "width" => 20],
        ];


        //副标题 纵单元格
        $beginTime = $params['beginTime'];
        $endTime   = $params['endTime'];
        $subtitle  = [
            ["rowName" => "开始时间:{$beginTime}", "acrossCells" => count($headArrValue) / 2],
            ["rowName" => "结束时间:{$endTime}", "acrossCells" => count($headArrValue) / 2],
        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "陪玩统计"], $subtitle);
    }


    //租号统计
    public function hire()
    {
        $PlayHireInit  = new \init\PlayHireInit();//租号管理  (ps:InitController)
        $PlayHireModel = new \initmodel\PlayHireModel(); //租号管理   (ps:InitModel)

        $params = $this->request->param();


        //时间
        if (empty($params['beginTime'])) $params['beginTime'] = date("Y-m-d");
        if (empty($params['endTime'])) $params['endTime'] = date("Y-m-d");


        $map   = [];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);


        //账号数量
        $params['account_total'] = $PlayHireModel->where($map)->count();


        //总成本价格
        $params['cost_price_amount'] = $PlayHireModel->where($map)->sum('cost_price');


        //总展示价格
        $params['show_price_amount'] = $PlayHireModel->where($map)->sum('show_price');

        //已结算账号
        $map2                 = [];
        $map2[]               = ['status', '=', 4];
        $params['hire_total'] = $PlayHireModel->where(array_merge($map, $map2))->count();

        //结果
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }

}
