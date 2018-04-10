<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Extensions\ExtendBlade;

use Illuminate\Http\Request;

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
        Request::setTrustedProxies(['10.200.0.1/8']);
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
