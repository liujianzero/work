<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreUrl extends Model
{
    protected $table = 'store_templates';

    protected $fillable = [
        'id', 'title', 'theme', 'controller', 'cover_img', 'pay_status',
    ];

    public $timestamps = false;

    /**
     * 获取信息
     */
    public static function getAll()
    {
        $data = StoreUrl::leftJoin('StoreUrl','StoreUrl.type_id', '=', 'StoreTemplate.id')->get();
        return $data;
    }
}
