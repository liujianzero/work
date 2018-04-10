<?php

namespace App\Modules\Bre\Model;

use Illuminate\Database\Eloquent\Model;

class ChallengeVideoContentModel extends Model
{
    protected $table = 'challenge_video_content';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id','type_id','type_title','url'
    ];

}
