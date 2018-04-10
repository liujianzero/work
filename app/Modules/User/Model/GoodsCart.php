<?php
/**
 * 商品-购物车
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class GoodsCart extends Model
{
    protected $fillable = [
        'user_id',
        'shop_id',
        'goods_id',
        'goods_name',
        'goods_price',
        'goods_number',
        'goods_attr',
        'goods_attr_id',
        'is_effective'
    ];
}