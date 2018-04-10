<?php
/**
 * 店铺配置
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class StoreConfig extends Model
{
    protected $fillable = [
        'store_id', 'store_name', 'store_logo', 'store_type_id',
        'major_business', 'country', 'province', 'city', 'area',
        'address', 'store_auth'
    ];

    /**
     * 获取对应店主。
     */
    public function user()
    {
        return $this->belongsTo('App\Modules\User\Model\UserModel', 'store_id');
    }
}