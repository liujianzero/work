<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreComment extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'client_ip',
        'content',
    ];
}
