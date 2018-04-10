<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreCart extends Model
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

    ];

}