<?php
/**
 * 订单服务（查看付费）
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

use Auth;

class ModelsOrderViewModel extends Model
{
    protected $table = 'models_order_view';

    protected $fillable = [
        'user_id', 'shop_id', 'models_id',
        'pay_status', 'expiration_date',
        'times', 'permanent'
    ];

    /**
     * 获取所有订单。
     */
    public function orders()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel', 'view_id', 'id');
    }

    /**
     * 获取对应商品。
     */
    public function goods()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsContentModel', 'models_id', 'id');
    }
}