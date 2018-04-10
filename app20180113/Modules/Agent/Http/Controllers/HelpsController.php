<?php

namespace App\Modules\Agent\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use App\Modules\Manage\Model\ConfigModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class HelpsController extends BaseController
{
    /**
     * 字符串截取
     */
    public static function cutStr($string, $sublen, $start = 0, $code = 'UTF-8')
    {
        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if (count($t_string[0]) - $start > $sublen) {
                return join('', array_slice($t_string[0], $start, $sublen)) . '...';
            }
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start  = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';
            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i,1)) > 129) {
                        $tmpstr .= substr($string, $i,2);
                    } else {
                        $tmpstr .= substr($string, $i,1);
                    }
                }
                if (ord(substr($string, $i,1)) > 129) {
                    $i++;
                }
            }
            if (strlen($tmpstr) < $strlen ) {
                $tmpstr .= '...';
            }
            return $tmpstr;
        }
    }

    /**
     * 格式化价格 *.**
     */
    public static function priceFormat($price)
    {
        return number_format($price, 2, '.', '');
    }

    /**
     * 上传文件（默认2M）
     */
    public static function uploadFile($file, $path, $size = 2048, $allowed_extensions = null)
    {
        if (!$file) {
            return ['code' => false, 'msg' => '未上传文件'];
        }
        if ($file->isValid()) {
            if (! $allowed_extensions) {
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
                    return ['code' => false, 'msg' => '文件大小限制为：' . self::getFileSize($size)];
                }
            } else {
                return ['code' => false, 'msg' => '支持的文件类型为：' . implode('、', $allowed_extensions)];
            }
        } else {
            return ['code' => false, 'msg' => $file->getErrorMessage()];
        }
    }

    /**
     * 获取最匹配的文件单位
     */
    public static function getFileSize($size = 0)
    {
        $basic = 1024;
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= $basic && $i < 4; $i++) {
            $size /= $basic;
        }
        return round($size, 2) . $units[$i];
    }

    /**
     * 创建文件夹并生成完整文件路径
     */
    protected function getTargetFile($directory, $name)
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        } elseif (!is_writable($directory)) {
            return false;
        }
        $target = rtrim($directory, '/\\') . '/' . $name;
        return $target;
    }

    /**
     * 拼接批量更新数据的Sql语句
     */
    public static function updateBatch($data = [], $table = '')
    {
        if (! $data || ! $table) {
            return false;
        }
        $table           = DB::getTablePrefix() . $table;
        $firstRow        = current($data);
        $updateColumn    = array_keys($firstRow);
        $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
        unset($updateColumn[0]);
        $sets      = [];
        $bindings  = [];
        $sql = "UPDATE `{$table}` SET ";
        foreach ($updateColumn as $uColumn) {
            $setSql = "`{$uColumn}` = CASE ";
            foreach ($data as $value) {
                $setSql    .= "WHEN `{$referenceColumn}` = ? THEN ? ";
                $bindings[] = $value[$referenceColumn];
                $bindings[] = $value[$uColumn];
            }
            $setSql .= "ELSE `{$uColumn}` END";
            $sets[]  = $setSql;
        }
        $sql     .= implode(', ', $sets);
        $whereIn  = collect($data)->pluck($referenceColumn)->values()->all();
        $bindings = array_merge($bindings, $whereIn);
        $whereIn  = rtrim(str_repeat('?,', count($whereIn)), ',');
        $sql      = rtrim($sql, ", ") . " WHERE `{$referenceColumn}` IN ({$whereIn})";
        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * 二维数组不重复排列组合
     */
    public static function combination($data, $currentIndex = -1)
    {
        static $total;
        static $totalIndex;
        static $totalCount;
        static $temp;

        if ($currentIndex < 0) {
            $total      = [];
            $totalIndex = 0;
            $temp       = [];
            $totalCount = count($data) - 1;
            self::combination($data, 0);
        } else {
            foreach ($data[$currentIndex] as $v) {
                if ($currentIndex < $totalCount) {
                    $temp[$currentIndex] = $v;
                    self::combination($data, $currentIndex + 1);
                } else if($currentIndex == $totalCount) {
                    $temp[$currentIndex] = $v;
                    $total[$totalIndex]  = $temp;
                    $totalIndex++;
                }
            }
        }
        return $total;
    }

    /**
     * 获取网站配置规则
     */
    public static function getConfigRule($key = '')
    {
        $alias = $key;
        $key .= '@rule';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = ConfigModel::where('alias', $alias)->first();
            $data = $data->rule;
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }

    /**
     * 获取网站配置
     */
    public static function getConfig($key = '')
    {
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = ConfigModel::where('alias', $key)->first();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }

    /**
     * 防止xss攻击
     */
    public static function removeXss($val)
    {
        $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
        $search  = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            $val = preg_replace('/(&#[x|X]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
        }

        $ra1 = ['javascript', 'vbscript', 'expression', 'applet', 'meta','blink', 'link',  'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound'];
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

    /**
     * 数组转换成xml
     */
    public static function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<{$key}>{$val}</{$key}>";
            } else {
                $xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * xml转换成数组
     */
    public static function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 设置令牌
     */
    public function setToken()
    {
        Session::put('order_token', md5(microtime(true)));
    }

    /**
     * 校验令牌是否一致
     */
    public function validToken()
    {
        $ret = $_REQUEST['order_token'] === Session::get('order_token', 'session') ? true : false;
        $this->setToken();
        return $ret;
    }

    /**
     * 获取订单号
     */
    public function getOrderSn()
    {
        mt_srand((double) microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 99999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * 数字货币转中文货币
     */
    public static function convertToZh($price = 0.00)
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

    /**
     * 一个时间与现在时间的对比
     */
    public static function howTime($time = '')
    {
        $time = strtotime($time);
        if ($time === false) {
            return false;
        }
        $how = time() - $time;
        if ($how < 0) {
            return false;
        }
        if ($how < 60) {
            $how .= '秒前';
        } elseif ($how < 3600) {
            $how = round($how / 60, 0) . '分钟前';
        } elseif ($how < 86400) {
            $how = round($how / 3600, 0) . '小时前';
        } elseif ($how < 31536000) {
            $how = round($how / 86400, 0) . '天前';
        }
        return $how;
    }
}
