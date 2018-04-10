<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Http\Controllers\IndexController;
use App\Modules\Advertisement\Http\Controllers\AdTargetController;
use App\Modules\Manage\Model\AgreementModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Http\Requests\LoginRequest;
use App\Modules\User\Http\Requests\RegisterPhoneRequest;
use App\Modules\User\Http\Requests\RegisterRequest;
use App\Modules\User\Model\OauthBindModel;
use App\Modules\User\Model\PromoteModel;
use App\Modules\User\Model\TeamUserModel;
use App\Modules\User\Model\UserLoginModel;
use App\Modules\User\Model\PromoteTypeModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Validator;
use Auth;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Theme;
use Crypt;
use Socialite;
use App\Modules\Advertisement\Model\AdTargetModel;
use App\Modules\Advertisement\Model\AdModel;
use Toplan\PhpSms;
use SmsManager;

use App\Modules\User\Model\ActionModel;

class AuthController extends IndexController
{

    


    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    
    protected $redirectPath = '/user/index';

    
    protected $loginPath = '/login';

    

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('auth');
        $this->theme->setTitle('威客');
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    
    protected $code;

    protected function validator(array $data)
    {

    }

    
    protected function  create(array $data)
    {
        
        return UserModel::createUser($data);
    }
 
    public function getLogin()
    {
        $code = \CommonClass::getCodes();
        $oauthConfig = ConfigModel::getConfigByType('oauth');
        $ad = AdTargetModel::getAdInfo('LOGIN_LEFT');
        $view = array(
            'code'  => $code,
            'oauth' => $oauthConfig,
            'ad'    => $ad
        );
        $this->theme->set('authAction', '欢迎登录');
        $this->theme->setTitle('欢迎登录');
        return $this->theme->scope('user.login', $view)->render();
    }
	public function getLogin1()
    {
		\Session::put('url.intended',\URL::previous());
        $code = \CommonClass::getCodes();
        $oauthConfig = ConfigModel::getConfigByType('oauth');
        $ad = AdTargetModel::getAdInfo('LOGIN_LEFT');
        $view = array(
            'code'  => $code,
            'oauth' => $oauthConfig,
            'ad'    => $ad
        );
        $this->theme->set('authAction', '欢迎登录');
        $this->theme->setTitle('欢迎登录');
        return $this->theme->scope('user.login', $view)->render();
    }

    public function postLogin(LoginRequest $request)
    {
        $teamData = TeamUserModel::where('username', $request->get('username'))->first();
        if(!empty($teamData)){
            $error = array();
            if ($request->get('code') && !\CommonClass::checkCode($request->get('code'))) {
                $error['code'] = '请输入正确的验证码';
            } else {
                if (!TeamUserModel::checkPassword(intval($teamData['id']), $request->get('password'))) {
                    $error['password'] = '请输入正确的帐号或密码';
                } else {
                    if (!empty($teamData) && $teamData->status == 0){
                        $error['username'] = '该账户已禁用';
                    }
                }
            }
            if (!empty($error)) {
                return redirect($this->loginPath())->withInput($request->only('username', 'remember'))->withErrors($error);
            }
            \Session::put('children', $teamData);
            Auth::loginUsingId($teamData->uid);
            $data = ['last_login_time' => date('Y-m-d H:i:s'), 'last_login_ip' => $request->getClientIp(), 'session_id' => session()->getId() ];
            TeamUserModel::where('id', $teamData->id)->update( $data );
            return redirect('/user/index');

        } else {
            $error = array();
            if ($request->get('code') && !\CommonClass::checkCode($request->get('code'))) {
                $error['code'] = '请输入正确的验证码';
            } else {
                if (!UserModel::checkPassword($request->get('username'), $request->get('password'))) {
                    $error['password'] = '请输入正确的帐号或密码';
                } else {
                    $user = UserModel::where('name', $request->get('username'))->first();
                    if (!empty($user) && $user->status == 2){
                        $error['username'] = '该账户已禁用';
                    }
                }
            }
            if (!empty($error)) {
                return redirect($this->loginPath())->withInput($request->only('username', 'remember'))->withErrors($error);
            }
            $throttles = $this->isUsingThrottlesLoginsTrait();
            $user = UserModel::where('email', $request->get('username'))
                ->orWhere('name', $request->get('username'))
                ->orWhere('mobile', $request->get('username'))->first();

            if ($user && !$user->status) {
                $emailSendStatus = \MessagesClass::sendActiveEmail($user['email']);
                if ($emailSendStatus) {
                    return redirect('waitActive/' . Crypt::encrypt($user->email))->withInput(array('email' => $request->get('email')));
                } else {
                    $view =  array();
                    $error['username'] = '激活邮件发送失败!';
                    return redirect($this->loginPath())->withInput($request->only('username', 'remember'))->withErrors($error);
                }
            }
            Auth::loginUsingId($user->id);

            //更新连续登录天数
            UserModel::updateLoginDay($user,time());
            $this->updateLoginInfo( $user->id, $request->getClientIp() );
            PromoteModel::settlementByUid($user->id);

            return $this->handleUserWasAuthenticated($request, $throttles);
        }
    }

    /**
     * 更新用户登录信息
     * @author orh
     * @time   2017-08-02
     *
     * @param  $uid
     * @param  $ip
     * @return void
     */
    protected function updateLoginInfo( $uid, $ip ){
        //更新最后登录时间及ip
        $data = ['last_login_time' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip, 'session_id' => session()->getId() ];
        UserModel::where('id', $uid)->update( $data );
        //追加本次登录信息
        $data= [
            'uid'        => $uid,
            'login_time' => $data['last_login_time'],
            'login_ip'   => $data['last_login_ip']
        ];
        UserLoginModel::create( $data );
        //检查是否当天首次登录
        $action = new ActionModel();
        $action->dailyIE( $ip );
    }

    public function getRegister(Request $request)
    {
        if ($request->get('uid')) {
            $uid = Crypt::decrypt($request->get('uid'));
        } else {
            $uid = '';
        }
        $code = \CommonClass::getCodes();
        $ad = AdTargetModel::getAdInfo('LOGIN_LEFT');
        $agree = AgreementModel::where('code_name','register')->first();

        $view = array(
            'code' => $code,
            'ad' => $ad,
            'agree' => $agree,
            'from_uid' => $uid
        );
        $this->initTheme('auth');
        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');
        return $this->theme->scope('user.register', $view)->render();
    }

    public function postRegister(RegisterRequest $request)
    {
        $user = $this->create($request->except('from_uid'));
        if ($user){
            if(!empty($request->get('from_uid'))){
                PromoteModel::createPromote($request->get('from_uid'),$user);
            }
            return redirect('waitActive/' . Crypt::encrypt($request->get('email')));
        }
        return back()->with(['message' => '注册失败']);
    }

    public function phoneRegister(RegisterPhoneRequest $request)
    {
        $authMobileInfo = session('auth_mobile_info');
        $data = $request->except('_token');
        if ($data['code'] == $authMobileInfo['code'] && $data['mobile'] == $authMobileInfo['mobile']){
            Session::forget('auth_mobile_info');
            $status = UserModel::mobileInitUser($data);
            if ($status) {
                if (!empty($request->get('from_uid'))) {
                    PromoteModel::createPromote($request->get('from_uid'),$status);
                }
                $user = UserModel::where('mobile', $data['mobile'])->first();
                Auth::loginUsingId($user->id);
                /* @author orh @time 2017-08-02 @edit start */
                $this->updateLoginInfo( $user->id, $request->getClientIp() );
                /* @author orh @time 2017-08-02 @edit end */
                // 新手任务【验证手机】
                $action = new ActionModel();
                $action->newbieTaskIE( 1, $request->getClientIp() );
                return $this->theme->scope('user.activesuccess')->render();
            }
        }
        return back()->withErrors(['code' => '请输入正确的验证码']);
    }

    public function sendMobileCode(Request $request)
    {
        $data = $request->except('_token');
        $code = rand(1000, 9999);
        $templates = [
            'YunTongXun' => '76741',
        ];
        $tempData = [
            'code' => $code,
            'time' => '5'
        ];
        $content = '【十一维度】你注册的手机验证码为' . $code;
        $status = \SmsClass::sendSms($data['mobile'], $templates, $tempData, $content);
        if ($status['success']){
            $data = [
                'code' => $code,
                'mobile' => $data['mobile']
            ];
            Session::put('auth_mobile_info', $data);
        }
        return json_encode($status);
    }

    public function activeEmail($validationInfo, Request $request)
    {
        $info = Crypt::decrypt($validationInfo);
        $user = UserModel::where('email', $info['email'])->where('validation_code', $info['validationCode'])->first();
        $this->initTheme('auth');
        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');
        if ($user && time() > strtotime($user->overdue_date) || !$user) {
            return $this->theme->scope('user.activefail')->render();
        }
        $user->status = 1;
        $user->email_status = 2;
        $status = $user->save();
        if ($status){
            Auth::login($user);
            $this->updateLoginInfo( $user->id, $request->getClientIp() );
            /* @author orh @time 2017-08-02 @edit end */
            // 新手任务【验证邮箱】
            $action = new ActionModel();
            $action->newbieTaskIE( 2, $request->getClientIp() );
            return $this->theme->scope('user.activesuccess')->render();
        }
    }

    public function waitActive($email)
    {
        $email = Crypt::decrypt($email);
        $emailType = substr($email, strpos($email, '@') + 1);
        $view = array(
            'email' => $email,
            'emailType' => $emailType
        );
        $this->initTheme('auth');
        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');
        return $this->theme->scope('user.waitactive', $view)->render();
    }
    
    public function flushCode()
    {
        $code = \CommonClass::getCodes();
        return \CommonClass::formatResponse('刷新成功', 200, $code);
    }

    public function checkUserName(Request $request)
    {
        $username = $request->get('param');
        $status = UserModel::where('name', $username)->first();
        if (empty($status)){
            $status = 'y';
            $info = '';
        } else {
            $info = '用户名不可用';
            $status = 'n';
        }
        $data = array(
            'info' => $info,
            'status' => $status
        );
        return json_encode($data);
    }

    public function checkEmail(Request $request)
    {
        $email = $request->get('param');
        $status = UserModel::where('email', $email)->first();
        if (empty($status)){
            $status = 'y';
            $info = '';
        } else {
            $info = '邮箱已占用';
            $status = 'n';
        }
        $data = array(
            'info' => $info,
            'status' => $status
        );
        return json_encode($data);
    }

    public function reSendActiveEmail($email)
    {
        $email = Crypt::decrypt($email);
        $status = UserModel::where('email', $email)->update(array('overdue_date' => date('Y-m-d H:i:s', time() + 60*60*3)));
        if ($status){
            $status = \MessagesClass::sendActiveEmail($email);
            if ($status){
                $msg = 'success';
            } else {
                $msg = 'fail';
            }
            return \CommonClass::formatResponse($msg);
        }
    }

    public function oauthLogin($type)
    {
        switch ($type){
            case 'qq':
                $alias = 'qq_api';
                break;
            case 'weibo':
                $alias = 'sina_api';
                break;
            case 'weixinweb':
                $alias = 'wechat_api';
                break;
        }
        $oauthConfig = ConfigModel::getOauthConfig($alias);
        $clientId = $oauthConfig['appId'];
        $clientSecret = $oauthConfig['appSecret'];
        $redirectUrl = url('oauth/' . $type . '/callback');
        $config = new \SocialiteProviders\Manager\Config($clientId, $clientSecret, $redirectUrl);
        return Socialite::with($type)->setConfig($config)->redirect();
    }
    
    public function handleOAuthCallBack($type, Request $request)
    {
        switch ($type){
            case 'qq':
                $service = 'qq_api';
                break;
            case 'weibo':
                $service = 'sina_api';
                break;
            case 'weixinweb':
                $service = 'wechat_api';
                break;
        }
        $oauthConfig = ConfigModel::getOauthConfig($service);
        Config::set('services.' . $type . '.client_id', $oauthConfig['appId']);
        Config::set('services.' . $type . '.client_secret', $oauthConfig['appSecret']);
        Config::set('services.' . $type . '.redirect', url('oauth/' . $type . '/callback'));

        $user = Socialite::driver($type)->user();

        $userInfo = [];
        switch ($type){
            case 'qq':
                $userInfo['oauth_id'] = $user->id;
                $userInfo['oauth_nickname'] = $user->nickname;
                $userInfo['oauth_type'] = 0;
                break;
            case 'weibo':
                $userInfo['oauth_id'] = $user->id;
                $userInfo['oauth_nickname'] = $user->nickname;
                $userInfo['oauth_type'] = 1;
                break;
            case 'weixinweb':
                $userInfo['oauth_nickname'] = $user->nickname;
                $userInfo['oauth_id'] = $user->user['unionid']; 
                $userInfo['oauth_type'] = 2;
                break;
        }
        
        $oauthStatus = OauthBindModel::where(['oauth_id' => $userInfo['oauth_id'], 'oauth_type' => $userInfo['oauth_type']])
                    ->first();

        if (!empty($oauthStatus)){
            $uid = $oauthStatus->uid;
        } else {
            $uid = OauthBindModel::oauthLoginTransaction($userInfo);
        }
        Auth::loginUsingId($uid);
        $this->updateLoginInfo( $uid, $request->getClientIp() );

        return redirect()->intended($this->redirectPath());
    }

}
