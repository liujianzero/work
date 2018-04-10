<?php

namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClassUserModel extends Model
{
    
    protected $table = 'class_user';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','class_id','uid'
    ];

    public $timestamps = false;


}
