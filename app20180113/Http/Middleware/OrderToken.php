<?php

namespace App\Http\Middleware;
use Closure;
use Session;

class OrderToken
{
    /**
     * 防止表单重复提交
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->get('order_token') ? $request->get('order_token') : null;
        if (!$token) {
            return redirect('/');
        } elseif ($token && $token === Session::get('order_token', 'session')) {
            Session::put('order_token', md5(microtime(true)));
            Session::save();
        } elseif ($token && $token !== Session::get('order_token', 'session')) {
            return redirect('/');
        } else {
            return redirect('/');
        }
        return $next($request);
    }
}
