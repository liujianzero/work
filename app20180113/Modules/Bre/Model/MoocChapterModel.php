<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2017/8/4
 * Time: 9:40
 */

namespace App\Modules\Bre\Model;


use Illuminate\Database\Eloquent\Model;

class MoocChapterModel extends Model
{
    protected $table = 'mooc_chapter';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'type_id', 'type_title','url', 'created_at', 'updated_at',
    ];

    public $timestamp = false;
}