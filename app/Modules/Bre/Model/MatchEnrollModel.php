<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/6/30
 * Time: 15:13
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MatchEnrollModel extends Model
{
    protected $table = 'match_enroll';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','address'
    ];

    public $timestamps = false;

}