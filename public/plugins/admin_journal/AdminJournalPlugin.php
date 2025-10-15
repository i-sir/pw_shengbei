<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 https://www.wzxaini9.cn/ All rights reserved.
// +----------------------------------------------------------------------
// | Author: Powerless <wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace plugins\admin_journal;

use cmf\lib\Plugin;
use think\facade\Db;

class AdminJournalPlugin extends Plugin
{
    public $info = [
        'name'        => 'AdminJournal',
        'title'       => '操作日志',
        'description' => '后台操作日志',
        'status'      => 1,
        'author'      => 'Powerless',
        'version'     => '1.2.0',
        'demo_url'    => 'https://www.wzxaini9.cn/',
        'author_url'  => 'https://www.wzxaini9.cn/'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    public function adminInit()
    {
        /**
         * 屏蔽一些方法,模块(api或admin)控制器+方法
         */
        $NoController = [
            'admin/shop_order/order_notification',
            'admin/member/test',
            'admin/member/test1',
        ];


        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        $adminId = cmf_get_current_admin_id();
        $time    = time();
        $this->assign("js_debug", APP_DEBUG ? "?v=$time" : "");

        //获取url
        $menu_name = $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $menu_name = str_replace("api.php?s=", "api/", $menu_name);//修改字符 api.php?s=改为api
        // 解析 URL 获取参数部分
        $url_detail = explode('&', $menu_name);


        //获取所有参数
        $params = request()->param();

        //获取控制器,方法
        $pathinfo = str_replace(".html", "", request()->pathinfo());//后台带的.html去掉

        // 将参数编码为 JSON 字符串
        $param = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // 构建访问的完整 URL
        $visit_url = $url_detail[0] . '?' . http_build_query($params);

        //如果存在设定方法,不记录日志
        if (in_array($pathinfo, $NoController)) return false;

        $admin_name = $this->filterEmoji(session('name'));

        //所要写入的数据
        $array_log  = [
            'admin_id'    => $adminId,
            'admin_name'  => $admin_name,
            'date'        => date('Y-m-d H:i:s'),
            'create_time' => time(),
            'ip'          => get_client_ip(),
            'param'       => json_encode($params),
            'menu_name'   => $menu_name,
            'visit_url'   => $visit_url,
        ];
        $array_log1 = [
            'log'         => json_encode($array_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'admin_id'    => $adminId,
            'ip'          => get_client_ip(),
            'admin_name'  => $admin_name,
            'date'        => date('Y-m-d H:i:s'),
            'create_time' => time(),
            'menu_name'   => $menu_name,
            'visit_url'   => $visit_url,
            'param'       => json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];


        //日志文件保留天数
        $log_file_days = cmf_config('log_file_days') ?? 10;
        // 指定日志文件目录
        $logDirectory = CMF_ROOT . 'data/journal/';
        // 获取当前日期和时间戳
        $currentDate = strtotime(date('Y-m-d'));
        // 计算n天前的日期时间戳
        $deleteDate = strtotime("-{$log_file_days} days", $currentDate);
        // 获取日志文件列表
        $files = scandir($logDirectory);
        // 遍历日志文件列表
        foreach ($files as $file) {
            // 排除当前目录和上级目录
            if ($file != '.' && $file != '..') {
                // 获取文件的最后修改时间
                $fileLastModified = filemtime($logDirectory . '/' . $file);
                // 如果文件的最后修改时间早于要删除的日期时间戳，则删除文件
                if ($fileLastModified < $deleteDate) {
                    unlink($logDirectory . '/' . $file);
                    //echo "Deleted: " . $logDirectory . '/' . $file . "\n";
                }
            }
        }
        //写入文件中 & 保存前10天的日志
        $filename = CMF_ROOT . 'data/journal/';
        !is_dir($filename) && mkdir($filename, 0755, true);
        $file_hwnd = fopen($filename . date('Y-m-d') . ".log", "a+");
        fwrite($file_hwnd, json_encode($array_log) . "\r\n");
        fclose($file_hwnd);

        //保存前n*3倍的日志
        $log_file_days = ($log_file_days ?? 10) * 3;
        $deleteDateDb  = strtotime("-{$log_file_days} days", $currentDate);
        Db::name('base_admin_log')->where('create_time', '<', $deleteDateDb)->delete();

        //数据库同步下
        Db::name('base_admin_log')->insert($array_log1);


        //删除资源表
        Db::name('asset')->where('id','<>',0)->delete();
    }


    //去除昵称的表情问题
    function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }
}
