<?php

namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgentTrainModel extends Model
{
    
    protected $table = 'agent_train';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','agent_id','user_id','name','note','sort','created_at','updated_at'
    ];

    public $timestamps = false;


}
