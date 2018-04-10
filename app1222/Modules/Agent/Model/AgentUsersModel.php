<?php

namespace App\Modules\Agent\Model;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use AgentMessagesClass;
use Session;

class AgentUsersModel extends Model implements AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
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
//        dd($data);exit;
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
//        dd($userArr);exit;
//        $objUser = AgentUsersModel::insertGetId($userArr);
//        dd($objUser);exit;
//        return $objUser;
        $status = DB::transaction(function() use ($userArr){
            $data['uid'] = AgentUsersModel::insertGetId($userArr);
//            $data['nickname'] = $data['name'];
//            UserDetailModel::create($data);
            return true;
        });
//        dd($status);exit;
//        return $status;

//        $status = self::initUser($userArr);
//        dd($status);exit;
        if ($status){
            $emailSendStatus = AgentMessagesClass::sendActiveEmail( $data['email'] );
//            var_dump($emailSendStatus);exit;
//            $emailSendStatus = \MessagesClass::sendActiveEmail( $data['email'] );
            if (!$emailSendStatus){
                $status = false;
            }
            return $status;
        }
    }


    /*public static function getUser()
    {
        return Session::get('user');
    }//1103*/

    static function encryptPassword($password, $sign = '')
    {
        return md5(md5($password . $sign));
    }


    static function checkPassword($username, $password)
    {
        $user = AgentUsersModel::where('name', $username)
            ->orWhere('email', $username)->orWhere('mobile', $username)->first();
        if ($user) {
            $password = self::encryptPassword($password, $user->salt);
            if ($user->password === $password) {
                return true;
            }
        }
        return false;
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


    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        // TODO: Implement getAuthIdentifier() method.
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        // TODO: Implement getAuthPassword() method.
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        // TODO: Implement getRememberToken() method.
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // TODO: Implement setRememberToken() method.
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        // TODO: Implement getRememberTokenName() method.
    }

    /**
     * Determine if the entity has a given ability.
     *
     * @param  string $ability
     * @param  array|mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        // TODO: Implement can() method.
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        // TODO: Implement getEmailForPasswordReset() method.
    }
}
