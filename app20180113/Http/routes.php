<?php


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
// 域名
$list = \App\Modules\User\Model\UserUrlModel::getActiveUrl();
if (count($list) > 0) {
    foreach ($list as $v) {
        Route::group(['domain' => $v->url], function () use($v) {
            if ($v->store_type_id > 0) {// 店铺域名
                // Route::get('agent','Agent\AgentController@agent');
                // Route::get('/view-{id}', 'DomainController@modelsView');
                // Route::get('models/getModelsUserInfo', 'DomainController@getModelsUserInfo');
                Route::get('{all}', 'DomainController@zone')->where('all', '^(?!(agent|_debugbar)).*$');
            } else {// 正常用户绑定域名
                Route::get('/view-{id}', 'DomainController@modelsView');
                Route::get('models/getModelsUserInfo', 'DomainController@getModelsUserInfo');
                Route::get('{all}', 'DomainController@zone')->where('all', '.*');
            }
        });
    }
}

Route::get('/', 'HomeController@index');
