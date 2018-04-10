<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2017/8/3
 * Time: 9:59
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MoocContentModel extends Model
{
    protected $table = 'mooc_content';
    protected $primaryKey = 'id';

    protected $fillabled = [
        'id','type_id','type_content','type_title','created_at','updated_at','type_chapter',
    ];

    public $timestamps = false;
}