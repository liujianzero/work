<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class AgentAdminSelect
{
    /**
     * 校验是否合法进入后台
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = Route::currentRouteName();
        $flag  = Session::get('agentAdmin')->storeType->flag;
        $reg = "/^agent\.{$flag}\.+/";
        if (! preg_match($reg, $route)) {
            return redirect()->route('agent.admin.index');
        }
        return $next($request);
    }
}
