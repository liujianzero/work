<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/1
 * Time: 17:30
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MoocRemarkModel extends Model
{
    protected $table = 'mooc_remark';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'mooc_id', 'uid', 'remark_id', 'content', 'created_at', 'updated_at'
    ];

    public $timestamps = true;

}