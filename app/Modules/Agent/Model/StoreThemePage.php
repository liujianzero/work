<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreThemePage extends Model
{
    protected $fillable = [
        'store_theme_id',
        'name',
        'page',
    ];
}
