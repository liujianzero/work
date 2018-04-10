<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/1
 * Time: 22:20
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MoocOrderModel extends Model
{
    protected $table = 'mooc_order';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','type_id','user_id','status','created_at','buy_type','pay_type','type_price','code'
    ];

    public $timestamps = false;
}