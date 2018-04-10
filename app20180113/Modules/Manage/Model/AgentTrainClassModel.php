<?php

namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgentTrainClassModel extends Model
{
    
    protected $table = 'agent_train_class';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','train_id','status','name','note','sort','created_at','updated_at'
    ];

    public $timestamps = false;


}
