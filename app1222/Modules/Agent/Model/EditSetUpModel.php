<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class EditSetUpModel extends Model
{
    protected $table = 'agent_setup';

    protected $fillable = [
        'id', 'store_id', 'name', 'information', 'certification', 'category', 'store_status',
        'mobile_status', 'logo', 'desc', 'qq' , 'pic', 'created_at', 'updated_at'

    ];

}
