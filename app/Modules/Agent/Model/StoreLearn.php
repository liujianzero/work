<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreLearn extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'client_ip',
        'total_number',
        'right_number',
        'total_score',
        'right_score',
    ];
}
