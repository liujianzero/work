<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class AddCustomerModel extends Model
{
    protected $table = 'agent_customer_add';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'store_id', 'name', 'mobile', 'wechat', 'source', 'vip', 'remark'
    ];
}
