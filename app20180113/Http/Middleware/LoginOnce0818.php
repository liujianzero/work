<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

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

        if( Auth::check() && !in_array( $request->path(), $this->except ) ){
            $user = Auth::User();
            if( session()->getId() != $user->session_id ){
                Auth::logout();
                return redirect($request->path());
            }
        }
        return $next($request);
    }
}
