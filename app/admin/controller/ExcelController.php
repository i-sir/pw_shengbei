<?php
/**
 * Created by PhpStorm.
 * User: zhang
 * Date: 2021/11/29
 * Time: 16:38
 */

namespace app\admin\controller;

use api\wxapp\controller\AuthController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\Db;

class ExcelController extends AuthController
{


    /**---------------------------------------   导入   ----------------------------**/
    /**
     * 订单导入
     * 上传文件
     */
    public function memberExcelImport()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $heads       = ['phone', 'pass', 'username', 'identity', 'identity_id', 'unit', 'train', 'course'];//导入头部对应数据库字段  A-Z 按顺序

        if ($this->request->isPost()) {
            $my_file = $_FILES['my_file'];
            //获取表格的大小，限制上传表格的大小5M
            if ($my_file['size'] > 5 * 1024 * 1024) {
                $this->error('文件大小不能超过5M');
            }

            //限制上传表格类型
            $fileExtendName = substr(strrchr($my_file["name"], '.'), 1);

            if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') {
                $this->error('必须为excel表格');
            }

            if (is_uploaded_file($my_file['tmp_name'])) {
                // 有Xls和Xlsx格式两种
                if ($fileExtendName == 'xlsx') {
                    $objReader = IOFactory::createReader('Xlsx');
                } else {
                    $objReader = IOFactory::createReader('Xls');
                }

                $filename    = $my_file['tmp_name'];
                $objPHPExcel = $objReader->load($filename);    //$filename可以是上传的表格，或者是指定的表格
                $sheet       = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
                $highestRow  = $sheet->getHighestRow();        // 取得总行数


                //$highestColumn = $sheet->getHighestColumn(); // 取得总列数

                $insert = [];//插入数据
                $letter = [];//字段名字
                foreach ($heads as $k => $v) {
                    $letter[$v] = $this->get_letter($k);
                }

                //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
                for ($j = 2; $j <= $highestRow; $j++) {
                    foreach ($letter as $k => $v) {

                        $value = preg_replace("/\s+/", "", $objPHPExcel->getActiveSheet()->getCell($v . $j)->getValue());

                        //特殊 处理数据
                        if ($k == 'nickname') $nickname = md5($value);


                        //追加字段
                        $z                             = $j - 1;
                        $insert[$j - 2]['openid']      = $this->get_openid() . "!@{$z}_!!!@@@@";
                        $insert[$j - 2]['create_time'] = time();
                        $insert[$j - 2]['avatar']      = 'dz0001/1.png';

                        //定义导入字段
                        $insert[$j - 2][$k] = $value;

                    }
                }


                $MemberModel->strict(false)->insertAll($insert);
            }

            $this->success("导入成功,请手动刷新");
        }
    }


    /**
     * 导入,根据key获取对应字母
     * @param $key
     * @return string
     */
    public function get_letter($key)
    {
        $letter = '';
        $key    = intval($key);
        while ($key >= 0) {
            $remainder = $key % 26;
            $letter    = chr(65 + $remainder) . $letter;
            $key       = floor($key / 26) - 1;
        }
        return $letter;
    }


    /**
     * 根据key获取对应字母以及之前的字母数组
     * @param $key
     * @return array
     */
    public function get_letters_sequence_before($key)
    {
        $num   = 0;
        $chars = str_split($key);
        foreach ($chars as $char) {
            $num = $num * 26 + ord($char) - 65 + 1;
        }

        $letters = [];
        for ($i = 1; $i <= $num; $i++) {
            $letter = '';
            $n      = $i;
            while ($n > 0) {
                $remainder = ($n - 1) % 26;
                $letter    = chr(65 + $remainder) . $letter;
                $n         = intdiv($n - 1, 26);
            }
            $letters[] = $letter;
        }
        return $letters;
    }


    /**---------------------------------------   导出   ----------------------------**/


    /**
     * 导出
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function test_export()
    {
        $par    = $this->request->param();
        $params = $par['excel'];

        $list = Db::name('form_test')
            ->select()
            ->each(function ($item, $key) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                //图片链接 可用默认浏览器打开   后面为展示链接名字
                if ($item['image']) $item['image'] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';

                return $item;
            })->toArray();


        $headArrValue = [
            ['rowName' => 'ID', 'rowVal' => 'id', 'width' => 10],
            ['rowName' => '名字', 'rowVal' => 'name', 'width' => 10],
            ['rowName' => '年龄', 'rowVal' => 'age', 'width' => 10],
            ['rowName' => '测试', 'rowVal' => 'test', 'width' => 10],
        ];

        //副标题 纵单元格
        $subtitle = [
            ['rowName' => '列1', 'acrossCells' => 2],
            ['rowName' => '列2', 'acrossCells' => 2],
        ];

        $this->excelExports($list, $headArrValue, ['fileName' => '订单导出'], $subtitle);
    }


    /**
     * 生成指定长度的随机字符串
     * @param int    $length 字符串长度
     * @param string $chars  生成字符范围
     * @return string
     */
    function get_openid($length = 50, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $result   = '';
        $char_len = strlen($chars);
        $chars    = str_shuffle($chars);//随机打乱字符
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $char_len - 1)];
        }
        return $result;
    }




    /**
     * excel 导出
     * @param array $data      数据data
     * @param array $headerRow 首行数据data
     * @param array $conf      [fileName] string 文件名 | [fileNameValue] string 内标题 | [format] string 文件格式后缀 xls
     * @param array $subtitle  [rowName] string 列名 | [acrossCells] string 跨越列数
     * @return void
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelExports($data = [], $headerRow = [], $conf = [], $subtitle = [])
    {
        $fileName      = $conf['fileName'];
        $fileNameValue = $conf['fileNameValue'] ?? '';
        $fileName      .= "_" . date('Ymd') . '_' . cmf_random_string(3);
        if (empty($fileNameValue)) {
            $fileNameValue = $fileName;
        }


        $format   = $conf['format'] ?? 'Xlsx'; //'Xls'
        $newExcel = new Spreadsheet(); //创建一个新的excel文档



        // 定义颜色与对应的背景颜色映射
        $colorBackgroundMap = [
            'red'    => '#FF0000',
            'green'  => '#00FF00',
            'blue'   => '#0000FF',
            'yellow' => '#FFFF00',
            'orange' => '#FFA500',
            'purple' => '#800080',
            // 可以根据需求添加更多颜色
        ];




        $objSheet = $newExcel->getActiveSheet(); //获取当前操作sheet的对象
        $objSheet->setTitle($fileName); //设置当前sheet的标题

        $sort = 0;
        //首行标题
        if (isset($conf['fileName']) && $conf['fileName']) {
            //首行标题
            $styleArray = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $objSheet->setCellValue('A1', $fileNameValue);
            $objSheet->getStyle('A1')->applyFromArray($styleArray);

            $length = count($headerRow) - 1;
            $objSheet->mergeCells('A1:' . $this->intToChr($length) . '1');
            $sort = 1;
        }

        /** 副标题列 */
        if ($subtitle) {
            $subSort = 0;
            foreach ($subtitle as $key) {
                $objSheet->setCellValue($this->intToChr($subSort) . "2", $key['rowName'] ?? ' ');
                $endSort = $subSort + $key['acrossCells'] - 1;
                $objSheet->mergeCells($this->intToChr($subSort) . "2:" . $this->intToChr($endSort) . "2");
                $subSort = $endSort + 1;
            }
            $sort = 2;
        }


        /** 字段渲染列 */
        $sort        += 1;
        $sheetConfig = false; //根据 $headerRow 配置数据读取方式
        //标题栏设置 & 行宽设置
        $headerRowCount = count($headerRow);
        for ($i = 0; $i < $headerRowCount; $i++) {
            $rowLetter = $this->intToChr($i);

            //设置第一栏的标题
            if (is_array($headerRow[$i])) {
                $sheetConfig = true;
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]['rowName']);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth($headerRow[$i]['width']);
            } else {
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth(30);
            }
        }

        //样式设置 - 水平、垂直居中
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];

        //竖向,结束行,默认+2行(一行表头一行注释,如有副标题+3行)
        $count_date = 2;
        if ($subtitle) $count_date = 3;
        $newExcel->getActiveSheet()->getStyle('A1:' . $this->intToChr($headerRowCount) . (count($data) + $count_date))->applyFromArray($styleArray);




        //长数组,转文本格式
        $LongNumberField = ['order_num', 'phone'];


        //第二行起，每一行的值
        $row_key = $sort + 1;
        if (!$sheetConfig) {
            foreach ($data as $key) {
                foreach ($key as $val) {
                    $rowLetter = $this->intToChr($key);
                    $objSheet->setCellValue($rowLetter . $row_key, $val);
                }
                $row_key++;
            }
        } else {
            foreach ($data as $rowVal) {
                foreach ($headerRow as $key => $val) {
                    $rowLetter = $this->intToChr($key);
                    //如果为手机号,订单号,转文本格式
                    if (in_array($val['rowVal'], $LongNumberField)) {
                        $objSheet->setCellValueExplicit($rowLetter . $row_key, $rowVal[$val['rowVal']], DataType::TYPE_STRING);
                    } else {
                        //否则正常导出
                        $objSheet->setCellValue($rowLetter . $row_key, $rowVal[$val['rowVal']]);
                    }


                    // 检查颜色信息是否存在，并且是否存在对应的背景颜色映射
                    if (isset($rowVal['BackgroundColor']) && isset($colorBackgroundMap[$rowVal['BackgroundColor']])) {
                        $objSheet->getStyle($rowLetter . $row_key)
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB($colorBackgroundMap[$rowVal['BackgroundColor']]);
                    }

                }
                $row_key++;
            }
        }

        // $format只能为 Xlsx 或 Xls
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel');
        }

        //直接输出文件
        header("Content-Disposition: attachment;filename=" . $fileName . '.' . strtolower($format));
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($newExcel, $format);
        $objWriter->save('php://output');
        exit;
    }


    /**
     * 导出处理
     * @param $num
     * @return mixed
     */
    public function intToChr($num)
    {
        $data = [];
        for ($i = 0; $i <= 701; $i++) {
            $y = ($i / 26);
            if ($y >= 1) {
                $y        = intval($y);
                $data[$i] = chr($y + 64) . chr($i - $y * 26 + 65);
            } else {
                $data[$i] = chr($i + 65);
            }
        }
        return $data[$num];
    }


    /**
     * 下载远程图片 到指定目录
     * @param $file_url
     * @param $path
     * @return array|string|string[]
     */
    private function download($file_url, $path)
    {
        $basepath = $path;
        $dir_path = $basepath;
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($file_url, PATHINFO_BASENAME);
        $resource = fopen($basepath . '/' . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        return $filename;
    }


}