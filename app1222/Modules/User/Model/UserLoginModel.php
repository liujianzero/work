<?php
/**
 * 用户登陆时间及ip
 *
 * @author orh
 * @time   2017-08-01
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class UserLoginModel extends Model
{

    protected $table = 'user_logins';

    public $timestamps = false;

    protected $fillable = [
        'uid', 'login_time', 'login_ip'
    ];

    /**
     * 获取最近十次登陆记录
     *
     * @param  $user_id
     * @return array
     */
    static function getLoginInfo( $user_id ){
        $data = self::where( 'uid', $user_id )
                    ->orderBy( 'login_time', 'DESC' )
                    ->take( 10 )
                    ->get()
                    ->toArray();
        if( count( $data ) >= 10 ){ // 删除十条以前的记录
            $time = $data[9]['login_time'];
            self::where( 'uid', '=', $user_id )
                ->where( 'login_time', '<', $time )
                ->delete();
        }
        return $data;
    }

}