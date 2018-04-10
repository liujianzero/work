<?php

namespace App\Modules\Task\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class ServiceModel extends Model
{

    protected $table = 'service';

    
    static public function serviceMoney($product_ids)
    {
        $money = 0;
        foreach($product_ids as $k=>$v)
        {
            $data = Self::where('id','=',$v)->first()->toArray();
            $money += $data['price'];
        }
        return $money;
    }

    /**
     * 查询增值服务数据
     */
    public static function getList($type = 1, $status = 1)
    {
        $key = "service_type@{$type}_status@{$status}";
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = ServiceModel::where('status', $status)
                ->where('type', $type)
                ->get();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }

}
