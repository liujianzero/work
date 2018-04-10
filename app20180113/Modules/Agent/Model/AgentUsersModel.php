<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use AgentMessagesClass;

class AgentUsersModel extends Model
{
    protected $table = 'agent_users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id','name','email','mobile','email_status','password','alternate_password',
        'salt','status','overdue_date','validation_code','expire_date','reset_password_code',
        'remember_token','last_login_time','source','user_type','member_expire_date',
        'last_login_ip','session_id','created_at','updated_at','pid'
    ];

    public $timestamps = false;

    static function createUser(array $data)
    {

        //var_dump($data);exit;
        $salt = \CommonClass::random(4);
        $validationCode = \CommonClass::random(6);
        $date = date('Y-m-d H:i:s');
        $now = time();
        $userArr = array(
            'name' => $data['username'],
            'email' => $data['email'],
            'password' => AgentUsersModel::encryptPassword($data['password'], $salt),
            'alternate_password' => AgentUsersModel::encryptPassword($data['password'], $salt),
            'salt' => $salt,
            'last_login_time' => $date,
            'overdue_date' => date('Y-m-d H:i:s', $now + 60*60*3),
            'validation_code' => $validationCode,
            'created_at' => $date,
            'updated_at' => $date,
            'pid' => $data['uid']
        );
//        var_dump($userArr);exit;
//        $objUser = AgentUsersModel::insertGetId($userArr);
//        var_dump($objUser);exit;
//        return $objUser;
        $status = DB::transaction(function() use ($userArr){
            $data['uid'] = AgentUsersModel::insertGetId($userArr);
//            $data['nickname'] = $data['name'];
//            UserDetailModel::create($data);
            return true;
        });
//        var_dump($status);exit;
//        return $status;

//        $status = self::initUser($userArr);
//var_dump($status);exit;
        if ($status){
            $emailSendStatus = AgentMessagesClass::sendActiveEmail( $data['email'] );
//            $emailSendStatus = \MessagesClass::sendActiveEmail( $data['email'] );
            if (!$emailSendStatus){
                $status = false;
            }
            return $status;
        }
    }



    static function encryptPassword($password, $sign = '')
    {
        return md5(md5($password . $sign));
    }

    public function initUser(array $data)
    {
        $status = DB::transaction(function() use ($data){
            $data['uid'] = AgentUsersModel::insertGetId($data);
//            $data['nickname'] = $data['name'];
//            UserDetailModel::create($data);
            return $data['uid'];
        });
        return $status;

    }
}
