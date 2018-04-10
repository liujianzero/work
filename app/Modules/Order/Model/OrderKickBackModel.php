<?php
/**
 * Created by PhpStorm
 * User: phpEr校长
 * Date: 2017/9/24
 * Time: 21:39
 * Email: 7708720@qq.com
 */

namespace App\Modules\Order\Model;


use Illuminate\Database\Eloquent\Model;

class OrderKickBackModel extends Model
{
    protected $table = 'order_kickback';

    protected $primaryKey = 'id';

    protected $fillable = [
        'type', 'type_id', 'kickback','two_level_type'
    ];

    public $timestamps = false;

    /**
     * Use:按照指定类型查找回扣倍率
     * @param null $type
     * @param null $type_id
     * @param null $two_level_type
     * @return mixed
     */
    static function getOrderKickback($type = null, $type_id= null, $two_level_type = null){
        $list = OrderKickBackModel::whereRaw('1 = 1');
        if($type){
            $list = $list -> where('type',$type);
        }
        if($type_id){
            $list = $list -> where('type_id',$type_id);
        }
        if($two_level_type && $two_level_type != 'normal'){
            $list = $list -> where('two_level_type',$two_level_type);
        }
        $data = $list->first()->kickback / 100;
        return $data;
    }
}