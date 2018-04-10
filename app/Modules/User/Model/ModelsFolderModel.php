<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsFolderModel extends Model
{


 //
    protected $table = 'models_folder';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','name','uid','cover_img','auth_type','auth_password','description','create_time','update_time','team_id'
    ];

    public $timestamps = false;


}