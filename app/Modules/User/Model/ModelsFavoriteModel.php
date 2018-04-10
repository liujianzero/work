<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsFavoriteModel extends Model
{


 //
    protected $table = 'models_favorite';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','models_id','uid','created_at','updated_at'
    ];

    public $timestamps = false;


}