<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/7/15
 * Time: 23:13
 */

namespace App\Modules\Shop\Models;


use Illuminate\Database\Eloquent\Model;

class ShopPowerModel extends Model
{
    protected $table = 'shop_power';

    public $timestamps = false;

    protected $fillable = [
        'id','shop_num', 'Renovation','recommend','url','user_type_id','open_type_id'
    ];
}