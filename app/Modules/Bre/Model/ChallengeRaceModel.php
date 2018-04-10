<?php

namespace App\Modules\Bre\Model;

use Illuminate\Database\Eloquent\Model;

class ChallengeRaceModel extends Model
{
    protected $table = 'challenge_race';

//    public $timestamps = true;

    protected $fillable = [
        'id','name','data'
    ];

}
