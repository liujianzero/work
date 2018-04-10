<?php
/**
 * 商品-分类表
 */
namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GoodsCategory extends Model
{
    protected $fillable = [
        'store_id', 'cat_name', 'cat_alias_name', 'sort_order', 'parent_id'
    ];

    /**
     * 获取某个店铺的分类
     */
    public static function getList($store_id)
    {
        $key = 'store_cat_list@' . $store_id;
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('store_id', $store_id)
                ->orderBy('sort_order')
                ->latest()
                ->get();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }
}