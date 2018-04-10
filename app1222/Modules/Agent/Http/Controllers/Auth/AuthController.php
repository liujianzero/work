<?php

namespace App\Modules\Agent\Http\Controllers\Auth;

use App\Http\Controllers\AgentAdminController;
use Illuminate\Http\Request;
use App\Modules\Manage\Http\Requests\LoginRequest;
use App\Modules\User\Model\UserModel;
use Illuminate\Support\Facades\Session;
use Validator;

class AuthController extends AgentAdminController
{
    //认证成功后跳转路由
    protected $redirectPath = '/agent/admin';

    //认证失败后跳转路由
    protected $loginPath = '/agent/admin/login';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('module', 'agent');
        $this->theme->set('route', 'agent');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {

    }

    /**
     * 后台登录页面
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getLogin()
    {
        if (UserModel::getAgentAdmin()){
            return redirect($this->redirectPath);
        }
        $this->initTheme('agent.login');
        $this->theme->setTitle('后台登录');
        return $this->theme->scope('agent.auth.login')->render();
    }

    /**
     * @param LoginRequest $request
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postLogin(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ], [
            'username.required' => '请输入用户名',
            'password.required' => '请输入密码'
        ]);
        //数据校验
        $user = UserModel::checkAgentAdminPassword($request->get('username'), $request->get('password'));
        if (! $user['code']) {
            return redirect($this->loginPath)->withErrors([$user['type'] => $user['err']])->withInput();
        }
        UserModel::agentAdminLogin($user['user']);
        return redirect($this->redirectPath);
    }

    /**
     * 后台登出
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getLogout()
    {
        Session::forget('agentAdmin');
        return redirect($this->loginPath);
    }

}
