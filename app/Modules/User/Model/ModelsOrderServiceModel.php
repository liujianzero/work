<?php
/**
 * 订单服务（购买服务）
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

use Auth;

class ModelsOrderServiceModel extends Model
{
    protected $table = 'models_order_service';

    protected $fillable = [
        'order_id', 'user_id', 'shop_id', 'models_id', 'demand_name',
        'design_demand', 'finish_time', 'user_file', 'shop_file',
        'task_status', 'user_reject', 'user_file_extension', 'shop_file_extension',
        'shop_file_cover', 'works_id'
    ];

    /**
     * 获取拥有此服务对应的订单。
     */
    public function order()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderModel', 'order_id', 'id');
    }

    /**
     * 获取对应商品。
     */
    public function goods()
    {
        return $this->hasOne('App\Modules\User\Model\ModelsOrderGoodsModel', 'order_id', 'order_id');
    }
}