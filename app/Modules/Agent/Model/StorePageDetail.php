<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StorePageDetail extends Model
{
    protected $table = 'store_pages_details';

    protected $primaryKey = 'id';

    protected $fillable = [
        'store_id',
        'head_nav_one',
        'head_nav_tow',
        'head_nav_three',
        'head_nav_four',
        'summary_img',
        'summary_desc',
        'address_img',
        'address_details',
        'travel_tips',
        'distributor_status',
        'collect_status',
        'cart_status',
        'orders_status',
        'theme',
    ];

}
