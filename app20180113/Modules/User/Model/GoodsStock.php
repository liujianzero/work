<?php
/**
 * 商品-属性库存
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class GoodsStock extends Model
{
    protected $fillable = [
        'goods_id', 'goods_attr_id', 'goods_number'
    ];
}