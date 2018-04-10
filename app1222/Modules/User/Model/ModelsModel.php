<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsModel extends Model
{

    
 //
    protected $table = 'models';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','title','create_time','update_time','status','allow_post','pid','sort'
    ];

    public $timestamps = false;


}