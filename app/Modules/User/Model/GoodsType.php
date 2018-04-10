<?php
/**
 * 商品-属性类型
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GoodsType extends Model
{
    protected $fillable = [
        'user_id', 'name'
    ];

    /**
     * 获取所有商品。
     */
    public function goods()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsContentModel');
    }

    /**
     * 获取所有属性。
     */
    public function attributes()
    {
        return $this->hasMany('App\Modules\User\Model\Attribute');
    }

    /**
     * 获取列表
     */
    public static function getList($store_id)
    {
        $key = 'store_type_list@' . $store_id;
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('user_id', $store_id)
                ->latest()
                ->lists('name', 'id');
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }
}