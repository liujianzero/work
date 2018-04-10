<?php
/**
 * 商品-快递公司
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Express extends Model
{
    protected $fillable = [
        'express_name', 'express_code', 'express_logo', 'status',
        'express_letter', 'express_tel', 'express_number'
    ];

    /**
     * 获取所有订单。
     */
    public function orders()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel');
    }

    // 获取所有启用的物流公司
    public static function getList()
    {
        $key = 'express_active_list';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('status', 'on')->get();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }
}