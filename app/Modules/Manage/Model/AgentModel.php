<?php

namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgentModel extends Model
{
    
    protected $table = 'agent';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','user_id','name','note','sort','created_at','updated_at'
    ];

    public $timestamps = false;


}
