<?php

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