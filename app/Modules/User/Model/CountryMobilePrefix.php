<?php
/**
 * 用户-地址表
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Cache;

class CountryMobilePrefix extends Model
{
    protected $fillable = [
        'country', 'prefix', 'area'
    ];

    /**
     * 用户地址-固话前缀。
     */
    public function tels()
    {
        return $this->hasMany('App\Modules\User\Model\UserAddress', 'tel_prefix_id', 'id');
    }

    /**
     * 用户地址-手机前缀。
     */
    public function mobiles()
    {
        return $this->hasMany('App\Modules\User\Model\UserAddress', 'mobile_prefix_id', 'id');
    }

    /**
     * 获取前缀
     */
    public static function getPrefix($key = 'CountryMobilePrefix')
    {
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = CountryMobilePrefix::select('id', 'country', 'prefix')
                ->get()->toArray();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }
}