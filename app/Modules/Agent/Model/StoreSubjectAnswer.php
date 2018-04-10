<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreSubjectAnswer extends Model
{
    protected $fillable = [
        'store_subject_id',
        'option',
        'is_right',
        'title',
    ];
}
