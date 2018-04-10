<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelsVrContentModel extends Model
{

    
 //
    protected $table = 'models_vr_content';
    protected $primaryKey = 'id';
    
    
    protected $fillable = [
        'id','title', 'content', 'view_count', 'cover_id', 'models_id', 'uid', 'reply_count', 'create_time',
    	'update_time', 'status', 'jsfile', 'sourcefile', 'is_download', 'is_private', 'cover_img',
    	'price', 'license','scene', 'sceneGlobal',
    	'collect', 'share', 'is_share', 'is_print', 'sort', 'tblink'
    ];

    public $timestamps = false;


}