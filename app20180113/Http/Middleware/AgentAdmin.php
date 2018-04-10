<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Session;

class AgentAdmin
{
    /**
     * 检测是否有登录
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(! Session::has('agentAdmin')){
            if ($request->ajax()) {
                return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
            } else {
                return redirect()->route('agent.admin.login.page');
            }
        }
        return $next($request);
    }
}
