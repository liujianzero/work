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
                Route::get('agent','Agent\AgentController@agent');
                Route::get('/view-{id}', 'DomainController@modelsView');
                Route::get('models/getModelsUserInfo', 'DomainController@getModelsUserInfo');
                Route::get('{all}', 'DomainController@zone')->where('all', '^(?!(agent)).*$');
            } else {    //正常用户绑定域名
                Route::get('/view-{id}', 'DomainController@modelsView');
                Route::get('models/getModelsUserInfo', 'DomainController@getModelsUserInfo');
                Route::get('{all}', 'DomainController@zone')->where('all', '.*');
            }
        });
    }
}

Route::group(['domain' => '1.vr.com'], function () {
    /*Route::get('/view-{id}', 'DomainController@modelsView');
    Route::get('models/getModelsUserInfo','DomainController@getModelsUserInfo');*/
    Route::get('/','Agent\IndexController@index')->name('agent_index');
    //分销
//    Route::get('/retail','RetailController@index')->name('retail_index');
//    Route::get('/retail1','RetailController@index1')->name('retail_index1');
//    Route::get('/retail/models/{id}','RetailController@models')->name('retail_models');
//    Route::get('/retail/pop/{id}','RetailController@putUpPop')->name('retail_putUpPop');
//    Route::post('/retail/postModels','RetailController@postModels')->name('retail_postModels');
    //注册
    Route::get('/register','Agent\RegisterController@getRegister')->name('retail_getRegister');
    Route::post('/register','Agent\RegisterController@postRegister')->name('retail_postRegister');
//	  Route::post('/register','RegisterController@postRegister')->name('retail_postRegister');
    //登录
//    Route::get('/login','Agent\LoginController@index')->name('retail_login');//1030note
    Route::get('/login','Agent\LoginController@getLogin')->name('retail_getLogin');//1028
    Route::post('/login','Agent\LoginController@postLogin')->name('retail_postLogin');//1028
    //登出
    Route::get('/logout','agent\LoginController@getLogout')->name('retail_Logout');//1030
    //验证
    Route::post('/checkUserName','Agent\RegisterController@checkUserName')->name('retail_checkUserName');
    Route::post('/checkEmail','Agent\RegisterController@checkEmail')->name('retail_checkEmail');
//    Route::post('/checkUserName','Agent\LoginController@checkUserName')->name('retail_checkUserName');
    Route::get('/checkInterVal','Agent\RegisterController@checkInterVal')->name('checkInterVal');
    //邮箱类
    Route::get('waitActive/{email}', 'Agent\RegisterController@waitActive');
    Route::get('activeEmail/{validationInfo}', 'Agent\RegisterController@activeEmail');

    Route::get('agent','Agent\IndexController@agent');
    Route::get('{all}','DomainController@zone')->where('all','.*');
});

Route::get('/', 'HomeController@index');
