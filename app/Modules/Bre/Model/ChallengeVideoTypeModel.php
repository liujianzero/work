<?php

namespace App\Modules\Bre\Model;

use Illuminate\Database\Eloquent\Model;

class ChallengeVideoTypeModel extends Model
{
    protected $table = 'challenge_video_type';

//    public $timestamps = true;

    protected $fillable = [
        'id','type_title'
    ];
}
