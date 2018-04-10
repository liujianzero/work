<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsRemarkModel extends Model
{


 //
    protected $table = 'models_remark';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','models_id', 'uid', 'remark_id', 'content','created_at', 'updated_at'
    ];

    public $timestamps = true;


}