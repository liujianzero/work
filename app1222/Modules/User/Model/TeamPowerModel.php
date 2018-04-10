<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/18
 * Time: 23:46
 */

namespace App\Modules\User\Model;


use Illuminate\Database\Eloquent\Model;

class TeamPowerModel extends Model
{
    protected $table = 'team_power';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'url','title','team_id','sort_id','type','is_show','url_array'
    ];

    /**
     * Use:根据类型查找全部数据
     * @param null $type
     * @param string $sort
     * @return mixed
     */
    static function getTeamPowerDataForType( $type = null, $sort = 'ASC' ) {
        $list = TeamPowerModel::whereRaw('1 = 1');
        if($type)
            $list = $list -> where('type',intval($type));

        if($sort)
            $list = $list -> orderBy('sort_id',$sort);

        $data = $list->where('is_show', 1)->get();

        return $data;
    }
}