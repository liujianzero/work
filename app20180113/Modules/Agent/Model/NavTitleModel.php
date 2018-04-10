<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NavTitleModel extends Model
{
    protected $table = 'agent_nav_title';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id','uid','title','url','is_show','sort'
    ];

    public $timestamps = false;

    /**
     * Use:获取分销类型导航基本信息
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function titleBelongToBasic() {
        return $this->belongsTo('App\Modules\Agent\Model\NavBasicModel', 'uid', 'uid');
    }

}
