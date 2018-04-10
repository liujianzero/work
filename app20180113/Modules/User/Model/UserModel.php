<?php

namespace App\Modules\User\Model;
use App\Modules\Manage\Model\ConfigModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Manage\Model\ClassUserModel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\DB;
use Session;

class UserModel extends Model implements AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;


    protected $table = 'users';

    protected $primaryKey = 'id';


    protected $fillable = [
        'name', 'email', 'email_status', 'mobile', 'password', 'alternate_password', 'salt', 'status', 'overdue_date', 'validation_code', 'expire_date',
        'reset_password_code', 'remember_token','con_login_day','source','experience','user_type','member_expire_date', 'credit_value', 'store_type_id',
        'pid'
    ];


    protected $hidden = ['password', 'remember_token'];

    /**
     * 获取所有订单商品。
     */
    public function goodsOrder()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel', 'shop_id', 'id');
    }

    /**
     * 获取所有订单商品。
     */
    public function goodsUserOrder()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel', 'user_id', 'id');
    }

    /**
     * 获取会员详情。
     */
    public function userDetail()
    {
        return $this->hasOne('App\Modules\User\Model\UserDetailModel', 'uid');
    }

    /**
     * 获取店铺信息。
     */
    public function store()
    {
        return $this->hasOne('App\Modules\User\Model\StoreConfig', 'store_id');
    }

    /**
     * 获取对应店铺信息。
     */
    public function storeType()
    {
        return $this->belongsTo('App\Modules\User\Model\StoreType');
    }

    /**
     * 随机生成用户名
     * 循环创建1万个随机账号，0碰撞，10万大约0-3个碰撞，足够应付未来数十亿级PV
     */
    public static function genUsername()
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        do {
            $username = '';
            for ($i = 0; $i < 6; $i++) {
                $username .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $username = strtoupper(base_convert(time() - 1420070400, 10, 36)) . $username;
        } while (self::checkUsername($username));
        return $username;
    }

    /**
     * 保证用户名唯一
     */
    private static function checkUsername($username = '')
    {
        return UserModel::where('name', $username)->value('name');
    }

    /**
     * 获取商城管理员
     */
    public static function getAgentAdmin()
    {
        return Session::get('agentAdmin');
    }

    /**
     * 获取商城管理员登录校验
     */
    public static function checkAgentAdminPassword($username, $password)
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'];
        if ($url != ConfigModel::getConfigByAlias('site_url')->rule) {
            $info = UserUrlModel::getUidForUrl($_SERVER['HTTP_HOST']);
            if (! $info) {
                return ['code' => false, 'type' => 'username', 'err' => 'Permission denied'];
            } else {
                $user = self::from('users as u')
                    ->select([
                        'u.*',
                        'sc.store_name',
                        'sc.store_thumb_logo as store_logo',
                        'sc.store_auth',
                        'sc.store_status',
                        'sc.assure_status',
                        'sc.auth_status',
                        'sc.open_status',
                        'sc.expire_at',
                        'sc.store_desc',
                        'sc.qq',
                        'sc.mobile_register',
                        'sc.created_at',
                        'st.name as store_type_name',
                        'st.flag',
                        'c.name as store_cat_name',
                    ])
                    ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
                    ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
                    ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
                    ->leftJoin('cate as c', 'c.id', '=', 'sc.major_business')
                    ->where('u.name', $username)
                    ->orWhere('u.mobile', $username)
                    ->orWhere('u.email', $username)
                    ->where('u.id', $info->uid)
                    ->orWhere('u.pid', $info->uid)
                    ->where('u.store_type_id', $info->store_type_id)
                    ->first();
            }
        } else {
            $user = self::from('users as u')
                ->select([
                    'u.*',
                    'sc.store_name',
                    'sc.store_thumb_logo as store_logo',
                    'sc.store_auth',
                    'sc.store_status',
                    'sc.assure_status',
                    'sc.auth_status',
                    'sc.open_status',
                    'sc.expire_at',
                    'sc.store_desc',
                    'sc.qq',
                    'sc.mobile_register',
                    'sc.created_at',
                    'st.name as store_type_name',
                    'st.flag',
                    'c.name as store_cat_name',
                ])
                ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
                ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
                ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
                ->leftJoin('cate as c', 'c.id', '=', 'sc.major_business')
                ->where('u.name', $username)
                ->orWhere('u.mobile', $username)
                ->orWhere('u.email', $username)
                ->first();
        }
        if ($user) {
            $password = self::encryptPassword($password, $user->salt);
            if ($user->password == $password) {
                return ['code' => true, 'user' => $user];
            } else {
                return ['code' => false, 'type' => 'password', 'err' => '密码错误'];
            }
        } else {
            return ['code' => false, 'type' => 'username', 'err' => '用户不存在'];
        }
    }

    /**
     * 获取商城管理员登录
     */
    public static function agentAdminLogin($admin)
    {
        return Session::put('agentAdmin', $admin);
    }

    static function encryptPassword($password, $sign = '')
    {
        return md5(md5($password . $sign));
    }


    static function checkPassword($username, $password)
    {

    	//echo $password;


        $user = UserModel::where('name', $username)
            ->orWhere('email', $username)->orWhere('mobile', $username)->first();

        if ($user) {



            $password = self::encryptPassword($password, $user->salt);

            if ($user->password === $password) {
                return true;
            }
        }
        return false;
    }

    static function checkPayPassword($email, $password)
    {
        $user = UserModel::where('email', $email)->first();
        if ($user) {
            $password = self::encryptPassword($password, $user->salt);
            if ($user->alternate_password == $password) {
                return true;
            }
        }
        return false;
    }

    static function psChange($data, $userInfo)
    {
        $user = new UserModel;
        $password = UserModel::encryptPassword($data['password'], $userInfo['salt']);
        $result = $user->where(['id'=>$userInfo['id']])->update(['password'=>$password]);

        return $result;
    }


    static function payPsUpdate($data, $userInfo)
    {
        $user = new UserModel;
        $password = UserModel::encryptPassword($data['password'], $userInfo['salt']);
        $result = $user->where(['id'=>$userInfo['id']])->update(['alternate_password'=>$password]);

        return $result;
    }


    static function createUser(array $data)
    {

        $salt = \CommonClass::random(4);
        $validationCode = \CommonClass::random(6);
        $date = date('Y-m-d H:i:s');
        $now = time();
        $userArr = array(
            'name' => $data['username'],
            'email' => $data['email'],
            'password' => UserModel::encryptPassword($data['password'], $salt),
            'alternate_password' => UserModel::encryptPassword($data['password'], $salt),
            'salt' => $salt,
            'last_login_time' => $date,
            'overdue_date' => date('Y-m-d H:i:s', $now + 60*60*3),
            'validation_code' => $validationCode,
            'created_at' => $date,
            'updated_at' => $date
        );
        $objUser = new UserModel();

        $status = $objUser->initUser($userArr);

        if ($status){
            $emailSendStatus = \MessagesClass::sendActiveEmail($data['email']);
            if (!$emailSendStatus){
                $status = false;
            }
            return $status;
        }
    }



    public function initUser(array $data)
    {
        $status = DB::transaction(function() use ($data){
            $data['uid'] = UserModel::insertGetId($data);
            $data['nickname'] = $data['name'];
            UserDetailModel::create($data);
            return $data['uid'];
        });
        return $status;

    }


    static function getUserName($id)
    {
        $userInfo = UserModel::where('id',$id)->first();
        return $userInfo->name;
    }

    static function getUserData($uid){
        $data = UserModel::where('id',$uid)->first();
        return $data;
    }

    public function isAuth($uid)
    {
        $auth = AuthRecordModel::where('uid',$uid)->where('status',4)->first();
        $bankAuth = BankAuthModel::where('uid',$uid)->where('status',4)->first();
        $aliAuth = AlipayAuthModel::where('uid',$uid)->where('status',4)->first();
        $data['auth'] = is_null($auth)?true:false;
        $data['bankAuth'] = is_null($bankAuth)?true:false;
        $data['aliAuth'] = is_null($aliAuth)?true:false;

        return $data;
    }


    static function editUser($data)
    {
        $status = DB::transaction(function () use ($data){
            UserModel::where('id', $data['uid'])->update([
                'email' => $data['email'],
                'password' => $data['password'],
                'salt' => $data['salt']
            ]);
            UserDetailModel::where('uid', $data['uid'])->update([
                'realname' => $data['realname'],
                'qq' => $data['qq'],
                'province' => $data['province'],
                'city' => $data['city'],
                'area' => $data['area']
            ]);
        });
        return is_null($status) ? true : false;
    }


    static function editTrainUser($data)
    {
    	$status = DB::transaction(function () use ($data){
    		UserModel::where('id', $data['uid'])->update([
                'email' => $data['email'],
                'password' => $data['password'],
                'salt' => $data['salt']
    	//	'salt' => $data['salt']
    		]);
    		UserDetailModel::where('uid', $data['uid'])->update([
    		'realname' => $data['realname'],
    		'qq' => $data['qq'],
    		'province' => $data['province'],
    		'city' => $data['city'],
    		'area' => $data['area']
    		]);
    		ClassUserModel::where('uid', $data['uid'])->update([
    			'class_id' => $data['class_id'],
    		]);
    	});
    	return is_null($status) ? true : false;
    }


    static function addUser($data)
    {
        $status = DB::transaction(function () use ($data){
            $data['uid'] = UserModel::insertGetId([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'salt' => $data['salt']
            ]);
            UserDetailModel::create([
                'uid' => $data['uid'],
                'realname' => $data['realname'],
                'qq' => $data['qq'],
                'mobile' => $data['mobile'],
                'province' => $data['province'],
                'city' => $data['city'],
                'area' => $data['area']
            ]);
        });
        return is_null($status) ? true : false;
    }

    static function addTrainUser($data)
    {

    	$status = DB::transaction(function () use ($data){
    		$data['uid'] = UserModel::insertGetId([
    				'name' => $data['name'],
    				'email' => $data['email'],
//    				'last_login_time' => $data['last_login_time'],
//            		'overdue_date' => $data['overdue_date'],
//            		'validation_code' => $data['validation_code'],
    				'password' => $data['password'],
    				'salt' => $data['salt'],
//    				'created_at' => $data['last_login_time'],
//    				'updated_at' => $data['last_login_time']
    		]);
    		UserDetailModel::create([
    		'uid' => $data['uid'],
    		'realname' => $data['realname'],
    		'qq' => $data['qq'],
    		'mobile' => $data['mobile'],
    		'province' => $data['province'],
    		'city' => $data['city'],
    		'area' => $data['area']
    		]);
    		ClassUserModel::create([
	    		'uid' => $data['uid'],
	    		'class_id' => $data['class_id']
    		]);
    	});
    	return is_null($status) ? true : false;
    }


    static function mobileInitUser($data)
    {
        $status = DB::transaction(function() use ($data){
            $sign = str_random(4);
            $userInfo = [
                'name' => $data['username'],
                'mobile' => $data['mobile'],
                'password' => self::encryptPassword($data['password'], $sign),
                'alternate_password' => self::encryptPassword($data['password'], $sign),
                'salt' => $sign,
                'status' => 1,
                'source' => 1
            ];
            $user = UserModel::create($userInfo);
            UserDetailModel::create([
                'uid' => $user->id,
                'mobile' => $user->mobile,
            ]);
            return $user->id;
        });
        return $status;
    }




    //更新连续登录天数和经验值
    static function updateLoginDay($user,$nowTime){

        //上次登录日期0点时间,如: 2017-04-17 00:00:00
        $last_login_str = date('Y-m-d'." 00:00:00",strtotime($user->last_login_time)) ;
        //上次登录日期下一天0点时间,如: 2017-04-18 00:00:00
        $last_login_nextday_start = strtotime($last_login_str) + 86400;
        //上次登录日期下一天 23:59:59时间,如: 2017-04-18 23:59:59
        $last_login_nextday_end = $last_login_nextday_start + 86400;

        //当前登录时间在 上次登录的下一天0点到23:59:59范围内，连续登录次数+1
        if($nowTime > $last_login_nextday_start && $nowTime < $last_login_nextday_end){
            $con_login_day = $user->con_login_day + 1;
            $result = UserModel::where("id",$user->id)->increment('con_login_day',1);
            //经验增加连续登录天数*2
            UserModel::where("id",$user->id)->increment('experience',2*$con_login_day);
            //超过一天未登录，连续登录天数清空,经验值+2
        }else if($nowTime > $last_login_nextday_end){
            $result = UserModel::where('id', $user->id)->update(['con_login_day' => 1]);
            //经验+2
            UserModel::where("id",$user->id)->increment('experience',2);
        }


    }


}
