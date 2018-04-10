<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use App\Extensions\ExtendBlade;

/* @author orh @time 2017-08-02 @add start */
use Illuminate\Http\Request;// 设置代理服务器
/* @author orh @time 2017-08-02 @add end */

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setLocale('zh');
        ExtendBlade::register();

        /* @author orh @time 2017-08-02 @add start */
        Request::setTrustedProxies(['10.200.0.1/8']); // 设置代理服务器
        /* @author orh @time 2017-08-02 @add end */

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
