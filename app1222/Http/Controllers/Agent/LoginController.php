<?php

namespace App\Http\Controllers\Agent;

use App\Modules\Agent\Model\AgentUsersModel;
use App\Http\Controllers\ApiBaseController;
use App\Modules\Manage\Model\ConfigModel;
use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends AgentController
{
    private $scope = 'agent.retail.';

    //认证成功后跳转的路径
    protected $redirectPath = '/';

    //认证失败后跳转的路径
    protected $loginPath = '/login';

    public function __construct() {
        parent::__construct ();
//        $this->user = Auth::user ();
        $this->initTheme( 'agent.agent' );
        $this->middleware('guest', ['except' => 'getLogout']);
    }


    /**
     * 后台登录页面
     */
    public function getLogin()
    {
        $code = \CommonClass::getCodes();   //生成验证码图片

        $view = [
            'code'  => $code,
        ];
        $this->theme->set('authAction', '欢迎登录');
        $this->theme->setTitle('欢迎登陆');
        return $this->theme->scope('agent.login.index',$view)->render();
    }

    public function postLogin(Request $request)
    {
        $error = array();
        if ($request->get('code') && !\CommonClass::checkCode($request->get('code'))) {
            $error['code'] = '请输入正确的验证码';
        } else {
            if (!AgentUsersModel::checkPassword($request->get('username'), $request->get('password'))) {
                $error['password'] = '请输入正确的账号或密码';
            } else {
                $user = AgentUsersModel::where('name', $request->get('username'))->first();
                if (!empty($user) && $user->status == 2) {
                    $error['username'] = '该账户已禁用';
                }
            }
        }
        if (!empty($error)) {
            return redirect($this->loginPath)->withInput($request->only('username', 'remember'))->withErrors($error);
        }

        $user = AgentUsersModel::where('name', $request->get('username'))
            ->orWhere('email', $request->get('username'))
            ->orWhere('mobile', $request->get('username'))
            ->first();

        //put方法存储新的数据到session中
        $request->session()->put('uid', $user->id);
        return redirect($this->redirectPath);
    }


    /**
    * 登出
     * 1101
     */
    public function getLogout(Request $request)
    {
        Auth::logout();
        //forget方法从session中移除指定数据
//        $request->session()->forget('key');
        $request->session()->forget('uid');
        /* //从session中移除所有数据可以使用flush方法
        $request->session()->flush();*/
        return redirect($this->redirectPath);
    }

}
