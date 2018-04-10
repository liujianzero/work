<?php
/**
 * 商品-任务价格表
 */
namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaskPriceRange extends Model
{
    protected $fillable = [
        'min_price', 'max_price', 'desc', 'status'
    ];

    /**
     * 获取某个店铺的分类
     */
    public static function getList()
    {
        $key = 'task_price_ranges';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('status', 1)
                ->latest()
                ->get();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }
}