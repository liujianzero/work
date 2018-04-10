<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/7/17
 * Time: 10:44
 */

namespace App\Modules\Shop\Models;


use Illuminate\Database\Eloquent\Model;

class ShopOtherModel extends Model
{
    protected $table = 'shop_other';

    public $timestamps = false;

    protected $fillable = [
        'id','account', 'discount','red_packet','service','user_type_id'
    ];
}