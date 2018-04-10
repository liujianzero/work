<?php
/**
 * 订单商品
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

use Auth;

class ModelsOrderGoodsModel extends Model
{
    protected $table = 'models_order_goods';

    protected $fillable = [
        'order_id', 'goods_id', 'goods_name', 'goods_number', 'goods_price', 'goods_attr', 'goods_attr_id'
    ];

    /**
     * 获取拥有此商品对应的订单。
     */
    public function order()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderModel', 'order_id', 'id');
    }

    /**
     * 获取拥有此商品的详细信息。
     */
    public function models()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsContentModel', 'goods_id', 'id');
    }

    /**
     * 获取拥有此商品的服务信息。
     */
    public function service()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderServiceModel', 'order_id', 'order_id');
    }
}