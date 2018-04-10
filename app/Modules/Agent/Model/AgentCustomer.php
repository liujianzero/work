<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class AgentCustomer extends Model
{
    protected $fillable = [
        'id',
        'store_id',
        'name',
        'mobile',
        'wechat',
        'from_at',
        'vip',
        'remark',
    ];
}
