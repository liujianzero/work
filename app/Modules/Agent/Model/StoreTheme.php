<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreTheme extends Model
{
    protected $fillable = [
        'name',
        'store_type_id',
        'flag',
        'is_free',
        'price',
        'cover_image',
    ];
}
