<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Modules\User\Model\TeamUserModel;

class LoginOnce
{

    protected $except = [
        'logout'
    ];


    /**
     * 检测当前登录是否正常
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $children = Session::get('children');
        if ($children && !in_array( $request->path(), $this->except)) {//子账号登录
            $user = TeamUserModel::where('id', $children['id'])->first();
            if ($user->session_id != session()->getId()) {
                Session::forget('children');
                return redirect($request->path());
            }
        } else {//主账号登录
            if (Auth::check() && !in_array( $request->path(), $this->except)) {
                $user = Auth::User();
                if (session()->getId() != $user->session_id) {
                    Auth::logout();
                    return redirect($request->path());
                }
            }
        }
        return $next($request);
    }
}
