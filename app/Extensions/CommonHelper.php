<?php

use Illuminate\Support\Facades\Cache;
use App\Modules\User\Model\Express;
use Gregwar\Image\Image;

if (!function_exists('matchImg')) {
    // 判断附件类型方便加载不同的图片
    function matchImg($type)
    {
        $file_types = [
            'zip' => [
                'zip',
                'xz',
                'wim',
                'tpz',
                'tbz',
                'swm',
                'rar',
                'lzma86',
                'lha',
                'gz',
                'bzip2',
            ],
            'word' => [
                'doc',
                'docx',
            ],
            'img' => [
                'bmp',
                'tif',
                'tiff',
                'cpx',
                'dwg',
                'eps',
                'gif',
                'ico',
                'jiff',
                'jpeg',
                'jpg',
                'pdf',
                'pm5',
            ],
            'txt' => [
                'txt',
            ],
            'excel' => [
                'xlsm',
                'xltx',
                'xltm',
                'xlsb',
                'xlam',
                'xlsx',
            ],
        ];
        $other_file = 'folder';
        foreach ($file_types as $k => $v) {
            if (in_array($type, $v)) {
                $other_file = $k;
                break;
            }
        }
        return $other_file;
    }
}

if (!function_exists('upload_file')) {
    // 上传文件（默认2M）
    function upload_file($file, $path, $size = 2048, $allowed_extensions = null)
    {
        if (!$file) {
            return ['code' => false, 'msg' => '未上传文件'];
        }
        if ($file->isValid()) {
            if (!$allowed_extensions) {
                $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];
            }
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension && in_array($extension, $allowed_extensions)) {
                $size *= 1024;
                $fileSize = $file->getClientSize();
                if ($fileSize <= $size) {
                    $filename = uniqid() . '.' . $extension;
                    $destinationPath = 'Uploads/' . $path . date('Y-m-d') . '/';
                    if ($file->move($destinationPath, $filename)) {
                        $filePath = $destinationPath . $filename;
                        return [
                            'code' => true,
                            'filePath' => $filePath,
                            'extension' => $extension,
                            'fileSize' => $fileSize,
                            'filename' => $file->getClientOriginalName()
                        ];
                    } else {
                        return ['code' => false, 'msg' => $file->getErrorMessage()];
                    }
                } else {
                    return ['code' => false, 'msg' => '文件大小限制为：' . get_file_size($size)];
                }
            } else {
                return ['code' => false, 'msg' => '支持的文件类型为：' . implode('、', $allowed_extensions)];
            }
        } else {
            return ['code' => false, 'msg' => $file->getErrorMessage()];
        }
    }
}

if (!function_exists('get_file_size')) {
    // 获取最匹配的文件单位
    function get_file_size($size = 0)
    {
        $basic = 1024;
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= $basic && $i < 4; $i++) {
            $size /= $basic;
        }
        return round($size, 2) . $units[$i];
    }
}

if (!function_exists('img_resize')) {
    // 图片裁剪
    function img_resize($data, $path, $resize = null)
    {
        $image = Image::open($data['filePath']);
        $filename = uniqid() . '.' . $data['extension'];
        $destinationPath = 'Uploads/' . $path . date('Y-m-d') . '/';
        $target = get_target_file($destinationPath, $filename);
        if (!$resize) {
            $resize = [
                'width' => 100,
                'height' => 100,
            ];
        }
        $image->cropResize($resize['width'], $resize['height']);
        $result = $image->save($target);
        if ($result) {
            return ['code' => true, 'filePath' => $target];
        } else {
            return ['code' => false, 'msg' => '裁剪失败'];
        }
    }
}

if (!function_exists('get_target_file')) {
    // 创建文件夹并生成完整文件路径
    function get_target_file($directory, $name)
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        } elseif (!is_writable($directory)) {
            return false;
        }
        $target = rtrim($directory, '/\\') . "/$name";
        return $target;
    }
}

if (!function_exists('price_format')) {
    // 格式化价格
    function price_format($price)
    {
        return number_format($price, 2, '.', '');
    }
}

if (!function_exists('cut_str')) {
    // 字符串截取
    function cut_str($string, $sublen, $suffix = true, $start = 0, $code = 'UTF-8')
    {
        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if (count($t_string[0]) - $start > $sublen) {
                return join('', array_slice($t_string[0], $start, $sublen)) . '...';
            }
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';
            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }
                if (ord(substr($string, $i, 1)) > 129) {
                    $i++;
                }
            }
            if (strlen($tmpstr) < $strlen && $suffix) {
                $tmpstr .= '...';
            }
            return $tmpstr;
        }
    }
}

if (!function_exists('update_batch')) {
    // 拼接批量更新数据的Sql语句
    function update_batch($data = [], $table = '')
    {
        if (!$data || !$table) {
            return false;
        }
        $table = DB::getTablePrefix() . $table;
        $firstRow = current($data);
        $updateColumn = array_keys($firstRow);
        $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
        unset($updateColumn[0]);
        $sets = [];
        $bindings = [];
        $sql = "UPDATE `{$table}` SET ";
        foreach ($updateColumn as $uColumn) {
            $setSql = "`{$uColumn}` = CASE ";
            foreach ($data as $value) {
                $setSql .= "WHEN `{$referenceColumn}` = ? THEN ? ";
                $bindings[] = $value[$referenceColumn];
                $bindings[] = $value[$uColumn];
            }
            $setSql .= "ELSE `{$uColumn}` END";
            $sets[] = $setSql;
        }
        $sql .= implode(', ', $sets);
        $whereIn = collect($data)->pluck($referenceColumn)->values()->all();
        $bindings = array_merge($bindings, $whereIn);
        $whereIn = rtrim(str_repeat('?,', count($whereIn)), ',');
        $sql = rtrim($sql, ", ") . " WHERE `{$referenceColumn}` IN ({$whereIn})";
        return ['sql' => $sql, 'bindings' => $bindings];
    }
}

if (!function_exists('combination')) {
    // 二维数组不重复排列组合
    function combination($data, $currentIndex = -1)
    {
        static $total;
        static $totalIndex;
        static $totalCount;
        static $temp;

        if ($currentIndex < 0) {
            $total = [];
            $totalIndex = 0;
            $temp = [];
            $totalCount = count($data) - 1;
            combination($data, 0);
        } else {
            foreach ($data[$currentIndex] as $v) {
                if ($currentIndex < $totalCount) {
                    $temp[$currentIndex] = $v;
                    combination($data, $currentIndex + 1);
                } else if ($currentIndex == $totalCount) {
                    $temp[$currentIndex] = $v;
                    $total[$totalIndex] = $temp;
                    $totalIndex++;
                }
            }
        }
        return $total;
    }
}

if (!function_exists('how_time')) {
    // 时间对比
    function how_time($time = '')
    {
        $time = strtotime($time);
        if ($time === false) {
            return false;
        }
        $how = time() - $time;
        if ($how < 0) {
            $how = abs($how);
            $suffix = '后';
        } else {
            $suffix = '前';
        }
        if ($how < 60) {
            $how .= " 秒$suffix";
        } elseif ($how < 3600) {
            $how = round($how / 60, 0) . " 分钟$suffix";
        } elseif ($how < 86400) {
            $how = round($how / 3600, 0) . " 小时$suffix";
        } elseif ($how < 31536000) {
            $how = round($how / 86400, 0) . " 天$suffix";
        }

        return $how;
    }
}

if (!function_exists('remove_xss')) {
    // 防止xss攻击
    function remove_xss($val)
    {
        $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            $val = preg_replace('/(&#[x|X]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
        }
        $ra1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound'];
        $ra2 = ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
            'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged',
            'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange',
            'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave',
            'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize',
            'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'];
        $ra = array_merge($ra1, $ra2);
        $found = true;
        while ($found) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
                        $pattern .= '|(&#0{0,8}([9][10][13]);?)?';
                        $pattern .= ')?';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2);
                $val = preg_replace($pattern, $replacement, $val);
                if ($val_before == $val) {
                    $found = false;
                }
            }
        }
        return $val;
    }
}

if (!function_exists('set_token')) {
    // 设置订单令牌
    function set_token()
    {
        Session::put('order_token', md5(microtime(true)));
    }
}

if (!function_exists('valid_token')) {
    // 校验令牌是否一致
    function valid_token($rebuild = true)
    {
        $ret = $_REQUEST['order_token'] === Session::get('order_token', 'session') ? true : false;
        if ($rebuild) {
            set_token();
        }
        return $ret;
    }
}

if (!function_exists('get_order_sn')) {
    // 获取订单号
    function get_order_sn()
    {
        mt_srand((double)microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 99999), 8, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('convert_to_zh')) {
    // 数字货币转中文货币
    function convert_to_zh($price = 0.00)
    {
        $unit = '';
        $price = round($price, 2);
        if ($price >= 10000) {
            $unit = '万';
            $price /= 10000;
        } elseif ($price >= 1000) {
            $unit = '千';
            $price /= 1000;
        }
        $price .= $unit;
        return $price;
    }
}

if (!function_exists('express_query')) {
    // 获取物流信息（基于阿里云Api-全国快递物流查询接口-杭州网尚科技有限公司）
    function express_query($number = '1202516745301', $type = 'YUNDA')
    {
        $key = "$type@$number";
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $host = 'http://jisukdcx.market.alicloudapi.com';
            $path = '/express/query';
            $method = 'GET';
            $appcode = '7c2f10b20b154781a57c66663ed5185f';// 密钥
            $headers = [];
            array_push($headers, "Authorization:APPCODE $appcode");
            $queries = "number={$number}&type={$type}";
            $url = "$host$path?$queries";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$$host", 'https://')) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $data = json_decode(curl_exec($curl), true);
            if ($data['status'] == '0') {
                if ($data['result']['deliverystatus'] == '3') {
                    Cache::put($key, $data, 3 * 24 * 60);//已签收，缓存3天
                } else {
                    Cache::put($key, $data, 2 * 60);//其它，缓存2小时
                }
            } else {
                $data = [
                    'status' => 1001,
                    'msg' => '暂无物流信息',
                    'time' => date('Y-m-d H:i:s'),
                    'result' => [
                        'number' => '',
                        'type' => '',
                        'list' => '',
                        'deliverystatus' => -1,
                        'issign' => -1
                    ],
                ];
                Cache::put($key, $data, 2 * 60);//其它，缓存2小时
            }
        }
        return $data;
    }
}

if (!function_exists('express_type')) {
    // 获取快递公司信息（基于阿里云Api-全国快递物流查询接口-杭州网尚科技有限公司）
    function express_type()
    {
        //缓存一周
        $key = 'express@type';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $host = 'http://jisukdcx.market.alicloudapi.com';
            $path = '/express/type';
            $method = 'GET';
            $appcode = '7c2f10b20b154781a57c66663ed5185f';
            $headers = [];
            array_push($headers, "Authorization:APPCODE $appcode");
            $url = $host . $path;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$$host", 'https://')) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $data = json_decode(curl_exec($curl), true);
            Cache::put($key, $data, 7 * 24 * 60);
        }
        // 录入数据库
        /*foreach ($data['result'] as $v) {
            $create = [
                'express_name' => $v['name'],
                'express_code' => $v['type'],
                'express_letter' => $v['letter'],
                'express_tel' => $v['tel'],
                'express_number' => $v['number']
            ];
            Express::create($create);
        }*/
        // 更新数据
        foreach ($data['result'] as $v) {
            $has = Express::where('express_code', $v['type'])->first();
            if ($has) {
                $update = [
                    'express_name' => $v['name'],
                    'express_letter' => $v['letter'],
                    'express_tel' => $v['tel'],
                    'express_number' => $v['number']
                ];
                Express::where('express_code', $v['type'])->update($update);
            } else {
                $create = [
                    'express_name' => $v['name'],
                    'express_code' => $v['type'],
                    'express_letter' => $v['letter'],
                    'express_tel' => $v['tel'],
                    'express_number' => $v['number']
                ];
                Express::create($create);
            }
        }
        return $data;
    }
}

if (!function_exists('ajax_page')) {
    // ajax分页
    function ajax_page($paginate, $event, $params, $length = 7)
    {
        if ($paginate->lastPage() < $length) {
            $start = 1;
            $end = $paginate->lastPage();
        } else {
            $start = $paginate->currentPage() - 3;
            $end = $paginate->currentPage() + 3;
            if ($start < 1) {
                $start = 1;
                $end = $start + $length - 1;
            }
            if ($end > $paginate->lastPage()) {
                $end = $paginate->lastPage();
                $start = $end - $length + 1;
            }
        }

        $data = '';
        foreach ($params as $k => $v) {
            $data .= "data-{$k}={$v} ";
        }

        if ($paginate->previousPageUrl()) {
            $page = '<li><a href="javascript:void(0);" rel="prev" onclick="' . $event . '" ' . $data . ' data-page="' . ($paginate->currentPage() - 1) . '">&laquo;</a></li>';
        } else {
            $page = '<li class="disabled"><span>&laquo;</span></li>';
        }

        if ($paginate->lastPage() > 1) {
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $paginate->currentPage()) {
                    $page .= '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    $page .= '<li><a href="javascript:void(0);" onclick="' . $event . '" ' . $data . ' data-page="' . $i . '">' . $i . '</a></li>';
                }
            }
        }

        if ($paginate->nextPageUrl()) {
            $page .= '<li><a href="javascript:void(0);" rel="next" onclick="' . $event . '" ' . $data . ' data-page="' . ($paginate->currentPage() + 1) . '">&raquo;</a></li>';
        } else {
            $page .= '<li class="disabled"><span>&raquo;</span></li>';
        }

        return $page;
    }
}

if (!function_exists('applause_rate')) {
    // 计算好评率
    function applause_rate($total, $good)
    {
        if ($total == 0) {
            $applause_rate = 100;
        } else {
            $applause_rate = ($good / $total) * 100;
        }

        return floor($applause_rate);
    }
}

if (!function_exists('calculate_days')) {
    // 计算过期时间
    function calculate_days($expire)
    {
        $expire = strtotime($expire);
        $time = $expire - time();
        if ($time > 0) {
            $day = floor($time / 86400);
        } else {
            $day = 0;
        }

        return $day;
    }
}

if (!function_exists('star_replace')) {
    // 字符串星号替换
    function star_replace($str, $start, $length = 0)
    {
        $i = 0;
        $star = '';
        if ($start >= 0) {
            if ($length > 0) {
                $str_len = strlen($str);
                $count = $length;
                if ($start >= $str_len) {
                    $count = 0;
                }
            } elseif ($length < 0) {
                $str_len = strlen($str);
                $count = abs($length);
                if ($start >= $str_len) {
                    $start = $str_len - 1;
                }
                $offset = $start - $count + 1;
                $count = $offset >= 0 ? abs($length) : ($start + 1);
                $start = $offset >= 0 ? $offset : 0;
            } else {
                $str_len = strlen($str);
                $count = $str_len - $start;
            }
        } else {
            if ($length > 0) {
                $offset = abs($start);
                $count = $offset >= $length ? $length : $offset;
            } elseif ($length < 0) {
                $str_len = strlen($str);
                $end = $str_len + $start;
                $offset = abs($start + $length) - 1;
                $start = $str_len - $offset;
                $start = $start >= 0 ? $start : 0;
                $count = $end - $start + 1;
            } else {
                $str_len = strlen($str);
                $count = $str_len + $start + 1;
                $start = 0;
            }
        }

        while ($i < $count) {
            $star .= '*';
            $i++;
        }

        return substr_replace($str, $star, $start, $count);
    }
}

if (!function_exists('check_wap')) {
    // 判断当前是wap还是web
    function check_wap()
    {
        if (isset($_SERVER['HTTP_VIA'])
            || isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])
            || isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) {
            return true;// wap
        }
        if (strpos(strtoupper($_SERVER['HTTP_ACCEPT']), 'VND.WAP.WML') > 0) {
            $br = 'WML';
        } else {
            $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
            if (empty($browser)) {
                return true;// wap
            }
            $mobile_os_list = [
                'Google Wireless Transcoder',
                'Windows CE',
                'WindowsCE',
                'Symbian',
                'Android',
                'armv6l',
                'armv5',
                'Mobile',
                'CentOS',
                'mowser',
                'AvantGo',
                'Opera Mobi',
                'J2ME/MIDP',
                'Smartphone',
                'Go.Web',
                'Palm',
                'iPAQ'
            ];
            $mobile_token_list = [
                'Profile/MIDP',
                'Configuration/CLDC-',
                '160×160',
                '176×220',
                '240×240',
                '240×320',
                '320×240',
                'UP.Browser',
                'UP.Link',
                'SymbianOS',
                'PalmOS',
                'PocketPC',
                'SonyEricsson',
                'Nokia',
                'BlackBerry',
                'Vodafone',
                'BenQ',
                'Novarra-Vision',
                'Iris',
                'NetFront',
                'HTC_',
                'Xda_',
                'SAMSUNG-SGH',
                'Wapaka',
                'DoCoMo',
                'iPhone',
                'iPod'
            ];
            $found_mobile = check_sub_str($mobile_os_list, $browser) || check_sub_str($mobile_token_list, $browser);
            if ($found_mobile) {
                $br = 'WML';
            } else {
                $br = 'WWW';
            }
        }
        if ($br == 'WML') {
            return true;// wap
        } else {
            return false;// web
        }
    }
}

if (!function_exists('check_sub_str')) {
    // 查找匹配的字符
    function check_sub_str($list, $str)
    {
        $flag = false;
        for ($i = 0; $i < count($list); $i++) {
            if (strpos($str, $list[$i]) > 0) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }
}