<?php

namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserLevelModel extends Model
{
    
    protected $table = 'user_level';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','name','min','max','remark','status','created_at','updated_at'
    ];

    public $timestamps = false;


}
