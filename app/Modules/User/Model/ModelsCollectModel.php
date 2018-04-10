<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsCollectModel extends Model
{


 //
    protected $table = 'models_collect';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','models_id','uid','created_at','updated_at'
    ];

    public $timestamps = false;


}