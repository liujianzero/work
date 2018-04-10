<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StorePage extends Model
{
    protected $fillable = [
        'store_id',
        'group',
        'group_name',
        'page',
        'page_name',
        'page_info',
        'top',
        'body',
        'bottom',
    ];
}
