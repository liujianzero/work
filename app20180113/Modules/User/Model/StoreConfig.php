<?php
/**
 * 店铺配置
 */

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class StoreConfig extends Model
{
    protected $fillable = [
        'store_id',
        'store_name',
        'template_id',
        'store_logo',
        'store_thumb_logo',
        'store_type_id',
        'major_business',
        'country',
        'province',
        'city',
        'area',
        'address',
        'store_auth',
        'store_status',
        'mobile_register',
        'qq',
        'store_desc',
        'auth_status',
        'expire_at',
        'assure_status',
        'auth_status',
        'open_status',
        'pic',
    ];

    // 获取对应店主
    public function user()
    {
        return $this->belongsTo('App\Modules\User\Model\UserModel', 'store_id');
    }

    // 获取店铺开启状态
    public static function storeOpenStatus($store_id = 0)
    {
        $data = self::where('store_id', $store_id)->first();

        if (!$data) {
            return false;
        }

        $time = date('Y-m-d H:i:s');
        if ($data->store_status == 'on' && $data->open_status == 'on' && $data->expire_at >= $time) {
            $status = true;
        } else {
            $status = false;
        }
        if ($data->open_status == 'on' && $data->expire_at < $time) {
            self::where('store_id', $store_id)->update(['open_status' => 'off', 'expire_at' => null]);
        }
        return $status;
    }

}
