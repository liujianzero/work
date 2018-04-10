<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreRenew extends Model
{
    protected $fillable = [
        'year',
        'price',
        'flag',
        'is_enable',
    ];

    // 获取数据缓存（默认缓存 10 小时）
    public static function getCache($minutes = 600)
    {
        return Cache::remember('store-renew@cache', $minutes, function () {
            return self::oldest('year')->lists('price', 'year')->toArray();
        });
    }
}
