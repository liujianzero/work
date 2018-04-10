<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class NavBasicModel extends Model
{
    protected $table = 'agent_nav_basic';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id','hid','uid','is_sell','sm_logo','lg_logo','style'
    ];

    public $timestamps = false;

    /**
     * Use:获取分销类型导航标题
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function basicHasTitle() {
        return $this->hasMany('App\Modules\Agent\Model\NavTitleModel', 'uid', 'uid');
    }

    /**
     * Use:获取分销类型导航全部数据
     * @param $uid
     * @param string $sort
     * @param int $is_show
     * @return mixed
     */
    static function getNavBasicData( $uid, $sort = 'DESC', $is_show = 1 ){
        $data['basic'] = NavBasicModel::where('uid',$uid)->first();
        $data['title'] = $data['basic']->basicHasTitle()->where('is_show',$is_show)->orderBy('sort',$sort)->get()->toArray();
        return $data;
    }

}
