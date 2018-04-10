<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreTemplate extends Model
{
    protected $table = 'store_templates';

    protected $fillable = [
        'id', 'title', 'theme', 'controller', 'cover_img', 'pay_status',
    ];

    public $timestamps = false;

    /**
     * 获取模板信息
     */
    public static function getGroup()
    {
        $data = StoreTemplate::get();
        return $data;
    }
}
