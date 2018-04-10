<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class EchartsDataModel extends Model {

    protected $table = 'agent_data';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'store_id', 'page_view', 'unique_visitor', 'merchandise_traffic', 'date'
    ];

}