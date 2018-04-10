<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/1
 * Time: 17:30
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MoocPriceModel extends Model
{
    protected $table = 'mooc_price';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','type','price','updated_at','created_at'
    ];

    public $timestamps = false;
}