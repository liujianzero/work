<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class ShopComponentModel extends Model
{
    protected $table = 'shop_component';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'data_type', 'title', 'add',
    ];
}
