<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Letter extends Model
{
    protected $fillable = [
        'lower',
        'upper',
    ];

    // 获取数据缓存（默认缓存 10 小时）
    public static function getCache($minutes = 600)
    {
        return Cache::remember('letter@cache', $minutes, function () {
            return self::all(['id', 'lower', 'upper']);
        });
    }
}
