<?php
/**
 * Created by PhpStorm
 * User: phpEr校长
 * Date: 2017/9/7
 * Time: 13:45
 * Email: 7708720@qq.com
 */

namespace App\Modules\User\Model;


use Illuminate\Database\Eloquent\Model;

class UserCapacityModel extends Model
{
    protected $table = 'user_capacity';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'price','capacity'
    ];


    /**
     * Use:获取全部信息
     * @return mixed
     */
    static function getAll(){
        $allData = UserCapacityModel::get();
        return $allData;
    }

    /**
     * Use:通过指定ID获取指定的数据
     * @param int $id
     * @return mixed
     */
    static function getAppointID( $id = 0 ){
        $appointData = UserCapacityModel::where('id',intval($id))->first();
        return $appointData;
    }

}