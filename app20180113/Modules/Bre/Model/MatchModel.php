<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/7/1
 * Time: 18:56
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MatchModel extends Model
{
    protected $table = 'match';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','uid','models_id','vote_num'
    ];

    public $timestamps = false;
}