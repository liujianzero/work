<?php

namespace App\Http\Controllers\Agent;


use App\Http\Controllers\BasicController;
use App\Modules\Advertisement\Model\AdTargetModel;
use App\Modules\Agent\Model\AgentUsersModel;
use App\Modules\Manage\Model\AgreementModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Http\Controllers\Auth\AuthController;
use App\Modules\User\Http\Requests\RegisterRequest;
use App\Modules\User\Model\PromoteModel;
use App\Modules\User\Model\UserUrlModel;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class RegisterController extends AgentController
{

    private $scope = 'agent.retail.';

    public function __construct() {
        parent::__construct ();
//        $this->user = Auth::user ();
        $this->initTheme( 'agent.agent' );
    }


    /**
     * Use:分销店铺会员注册（GET展示数据）
     * @param Request $request
     * @return mixed
     */
    public function getRegister(Request $request)
    {
//        $da = ConfigModel::getConfigByAlias('site_url')->rule;
//        var_dump( $da );exit;

        if( 'http://'.$request->getHost() == ConfigModel::getConfigByAlias('site_url')->rule ){
            $uid = 1;
        } else {
//            $store_id = UserUrlModel::getUidForUrl( $request->getHost() );
            $uid = $this->store_id;
        }
//        var_dump($uid);exit;
//        if($request->get('uid')){
//            $uid = Crypt::decrypt($request->get('uid'));
//        }else{
//            $uid = '';
//        }

        $code = \CommonClass::getCodes();

        //$ad = AdTargetModel::getAdInfo('LOGIN_LEFT');

        //协议处要更改
        $agree = AgreementModel::where('code_name','register')->first();

        $view = array(
            'code' => $code,
            //'ad' => $ad,
            'agree' => $agree,
            'from_uid' => $uid
        );
//        var_dump($code);
////        var_dump($ad);
//        var_dump($agree);
//        var_dump($uid);
//        exit;

        //$this->initTheme('agent.auth');
        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');
        return $this->theme->scope('agent.register.index', $view)->render();
    }

    /**
     * Use:分销店铺会员注册（POST接收数据）
     * @param RegisterRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postRegister(Request $request)
    {
        $data = $request->except('from_uid');
        $data['uid'] = $_POST['from_uid'];

//        dd($data);exit;

        $user = AgentUsersModel::createUser($data);//$this->create($request->except('from_uid'));
//        var_dump($user);exit;
//        var_dump($request->get('email'));
//        var_dump(Crypt::encrypt($request->get('email')));exit;

        if ($user){
//            if(!empty($request->get('from_uid'))){
//                PromoteModel::createPromote($request->get('from_uid'),$user);
//            }
            return redirect('waitActive/' . Crypt::encrypt($request->get('email')));
        }
        return redirect()->back()->with(['message' => '注册失败']);
    }

    public function activeEmail($validationInfo, Request $request)
    {
//        var_dump(1);exit;
        $info = Crypt::decrypt($validationInfo);
        $user = AgentUsersModel::where('email', $info['email'])->where('validation_code', $info['validationCode'])->first();

//        $this->initTheme('auth');
        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');

        if ($user && time() > strtotime($user->overdue_date) || !$user) {
            return $this->theme->scope('agent.register.activefail')->render();
        }

        $user->status = 1;
        $user->email_status = 2;
        $status = $user->save();
        if ($status){
            Session::put('users',$user);
//            $this->updateLoginInfo( $user->id, $request->getClientIp() );
//            // 新手任务【验证邮箱】
//            $action = new ActionModel();
//            $action->newbieTaskIE( 2, $request->getClientIp() );
            return $this->theme->scope('agent.register.activesuccess')->render();
        }
    }


    public function waitActive($email)
    {
//        var_dump(1);exit;
        $email = Crypt::decrypt($email);

        $emailType = substr($email, strpos($email, '@') + 1);
        $view = array(
            'email' => $email,
            'emailType' => $emailType
        );
//        $this->initTheme('auth');
//        $this->theme->set('authAction', '欢迎注册');
        $this->theme->setTitle('欢迎注册');
        return $this->theme->scope('agent.register.waitactive', $view)->render();
    }



    /**
     * Use:分销店铺会员注册创建数据
     * @param array $data
     * @return mixed
     */
    protected function create(array $data)
    {

        return AgentUsersModel::createUser($data);
    }

    public function flushCode()
    {
        $code = \CommonClass::getCodes();

        return \CommonClass::formatResponse('刷新成功', 200, $code);
    }

    /**
     * Use:销店铺会员注册（AJAX用户名验证）
     * @param Request $request
     * @return string
     */
    public function checkUserName(Request $request)
    {
//        var_dump(1);exit;
        $username = $request->get('param');
        $status = AgentUsersModel::where('name', $username)->where('pid',$this->store_id)->first();
        if (empty($status)) {
            $status = 'y';
            $info = '恭喜您！用户名可以使用！';
        } else {
            $info = '呜呜！用户名不可用哦！';
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

        $status = AgentUsersModel::where('email', $email)->where('pid',$this->store_id)->first();
        if (empty($status)){
            $status = 'y';
            $info = '恭喜您！邮箱可以使用！';
        } else {
            $info = '呜呜！邮箱已被占用啦！';
            $status = 'n';
        }
        $data = array(
            'info' => $info,
            'status' => $status
        );
        return json_encode($data);
    }

    /**
     * 检测发送邮件倒计时时间(修改支付密码)
     */
    public function checkInterVal()
    {
        $sendTime = Session::get ( 'send_code_time' );
        //var_dump(1);exit;
        $nowTime = time ();
        if (empty ( $sendTime )) {
            return response ()->json ( [
                'errCode' => 3
            ] );
        } else {
            if ($nowTime - $sendTime < 60) { // 时间在0-60
                return response ()->json ( [
                    'errCode' => 1,
                    'interValTime' => 60 - ($nowTime - $sendTime)
                ] );
            } else {
                return response ()->json ( [
                    'errCode' => 2
                ] ); // 大于60
            }
        }
    }
}
