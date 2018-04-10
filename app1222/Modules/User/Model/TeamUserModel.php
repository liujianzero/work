<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/18
 * Time: 23:46
 */

namespace App\Modules\User\Model;


use Illuminate\Database\Eloquent\Model;

class TeamUserModel extends Model
{
    protected $table = 'team_user';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'uid','status','username','created_at','password','salt'
    ];

    static function findUser($name){
        $userData = UserModel::where('name', $name)->first();
        $teamData = TeamUserModel::where('username', $name)->first();
        if(empty($userData) && empty($teamData)){
            return true;
        }
        return false;
    }

    static function TeamUpdate($id,$status){
        $data = TeamUserModel::find($id);
        $status = $status == 0 ? 1 : 0 ;
        $data->status = $status;
        $bool = $data->save();
        if($bool){
            $data = [
                'msg'  => $status == 1 ? '账号启用成功' : '账号禁用成功',
                'sta'  => $status == 1 ? 'yes' : 'no'
            ];
        }else{
            $data = [
                'msg'  => '修改失败',
                'sta'  => 'no'
            ];
        }
        return $data;
    }

    static function encryptPassword($password, $sign = '')
    {
        return md5(md5($password . $sign));
    }


    /**
     * @param $id
     * @param $password
     * @return bool
     */
    static function checkPassword($id, $password)
    {
        $user = TeamUserModel::where('id', $id)->first();
        if ($user) {
            $password = self::encryptPassword($password,$user->salt);
            if ($user->password === $password) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $data
     * @param $userInfo
     * @return mixed
     *
     */
    static function psChange($data, $userInfo)
    {
        $user = new TeamUserModel;
        $password = TeamUserModel::encryptPassword($data['password'], $userInfo['salt']);
        $result = $user->where(['id'=>$userInfo['id']])->update(['password'=>$password]);
        return $result;
    }
}