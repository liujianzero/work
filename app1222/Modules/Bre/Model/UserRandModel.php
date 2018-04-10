<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/7/28
 * Time: 15:16
 */

namespace App\Modules\Bre\Model;


use App\Modules\Shop\Models\ShopOtherModel;
use Illuminate\Database\Eloquent\Model;

class UserRandModel extends Model
{
    protected $table = 'user_randnum';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id','uid','randnum','updated_at','created_at','type'
    ];

    public $timestamps = false;

    static function createRandNum($uid,$num,$type){
        $data = [
            'uid'        => $uid,
            'randnum'    => $num,
            'created_at' => date('Y-m-d H:i:s',time()),
            'type'       => $type,
        ];
        UserRandModel::create($data);
    }

    static function getRandNumForUid($uid){
        $userRand = UserRandModel::where('uid',$uid)->where('type',1)->first();
        $testRand = UserRandModel::where('uid',$uid)->where('type',2)->first();
        $data = [
            'userRand' => $userRand['randnum'],
            'testRand' => $testRand['randnum'],
        ];
        return $data;
    }


    static function getRandNumForUserType($type){
        $red_packet = ShopOtherModel::where('id',$type)->value('red_packet');
        return $red_packet;
    }
}