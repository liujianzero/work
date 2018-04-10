<?php
/**
 * 店铺类型
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Cache;

class StoreType extends Model
{
    protected $fillable = [
        'name', 'flag', 'introduce', 'order_sort', 'status'
    ];

    /**
     * 获取所有店铺。
     */
    public function stores()
    {
        return $this->hasMany('App\Modules\User\Model\UserModel');
    }

    /**
     * 获取所有店铺的URL。
     */
    public function storeUrl()
    {
        return $this->hasMany('App\Modules\User\Model\UserUrlModel');
    }

    /**
     * 获取店铺类型列表
     */
    public static function getList()
    {
        $key = 'store_type_list';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = StoreType::where('status', 'on')
                ->orderBy('order_sort', 'asc')
                ->get();
            Cache::put($key, $data, 24 * 60);// 缓存一天
        }
        return $data;
    }
}
