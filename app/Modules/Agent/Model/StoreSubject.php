<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class StoreSubject extends Model
{
    protected $fillable = [
        'store_id',
        'title',
        'score',
        'type',
    ];

    // 获取所有的答案
    public function storeSubjectAnswers()
    {
        return $this->hasMany('App\Modules\Agent\Model\StoreSubjectAnswer');
    }
}
