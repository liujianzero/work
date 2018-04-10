<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/16
 * Time: 15:11
 */

namespace App\Modules\User\Model;


use Illuminate\Database\Eloquent\Model;

class LoseModel extends Model
{
    protected $table = 'lose';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id','lose_id', 'lose_type', 'lose_cause', 'created_at'
    ];

    public $timestamps = false;
}