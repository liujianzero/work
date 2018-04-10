<?php

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for the module.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::group(['prefix' => 'agent'], function() {
	// 后台-Auth
    Route::group(['namespace' => 'Auth'], function () {
        Route::get('/admin/login','AuthController@getLogin')->name('agent.admin.login.page');// 登录页
        Route::post('/admin/login', 'AuthController@postLogin')->name('agent.admin.login');// 登录
        Route::get('/admin/logout', 'AuthController@getLogout')->name('agent.admin.logout');// 退出登录
    });
    // 后台-切换
    Route::group(['prefix' => 'admin', 'middleware' => 'agent.admin'], function () {
        Route::get('/','IndexController@index')->name('agent.admin.index');// 切换后台
    });
    // 各种类型后台
    $list = \App\Modules\User\Model\StoreType::getList();
    foreach ($list as $v) {
        $flag = strtolower($v->flag);
        Route::group([//
            'as' => 'agent.' . $flag . '.',
            'prefix' => $flag,
            'namespace' => ucfirst($flag),
            'middleware' => ['agent.admin', 'agent.admin.select']
        ], function () use($flag) {
            Route::get('/','SurveyController@index')->name('index');// 概况-默认页
            Route::get('/shop','ShopController@index')->name('shop.index');// 店铺-默认页
            Route::get('/shop/selectTemplate','ShopController@selectTemplate')->name('shop.select.template');// 店铺-选择模板
            Route::get('/shop/buyTemplate','ShopController@buyTemplate')->name('shop.buy.template');// 店铺-购买模板
            Route::get('/shop/custom','ShopController@custom')->name('shop.custom');// 店铺-自定义模板
            Route::get('/shop/default/{id}','ShopController@defaults')->name('shop.default');// 店铺-默认模板-首页
            Route::get('/shop/default_personal/{id}','ShopController@personal')->name('shop.default_personal');// 店铺-默认模板-个人中心
            Route::get('/shop/default_summary/{id}','ShopController@summary')->name('shop.default_summary');// 店铺-默认模板-简介
            Route::get('/shop/default_addr/{id}','ShopController@address')->name('shop.default_addr');// 店铺-默认模板-地址
            Route::get('/shop/default_order/{id}','ShopController@order')->name('shop.default_order');// 店铺-默认模板-预订
            Route::get('/shop/default_content/{id}','ShopController@goodsContent')->name('shop.default_content');// 店铺-商品详情
            Route::post('/shop/editPic','ShopController@editPic')->name('shop.editPic');// 店铺-更换全景图背景
            Route::get('/shop/issue/{id}','ShopController@issue')->name('shop.issue');// 店铺-保存发布（自定义）
            Route::get('/shop/detail','ShopController@detail')->name('shop.detail');// 店铺-预览
            Route::post('/shop/accept','ShopController@accept')->name('shop.accept');// 店铺-发布ajax获取信息1127
            Route::get('/goods','GoodsController@index')->name('goods.index');// 商品-默认页
            Route::post('/goods/cat/add','GoodsController@catStore')->name('goods.cat.add');// 商品-分组添加@ajax
            Route::get('/goods/add','GoodsController@addGoods')->name('goods.add');// 商品-添加商品
            Route::get('/goods/distribution','GoodsController@distributionGoods')->name('goods.distribution');// 商品-添加商品
            Route::get('/order','OrderController@index')->name('order.index');// 订单-默认页
            Route::get('/customer','CustomerController@index')->name('customer.index');// 客户-默认页
            Route::post('/customer/add','CustomerController@addCustomer')->name('customer.add');//客户-添加客户界面@ajax
            Route::post('/customer/edit/page/{id}','CustomerController@editPage')->name('customer.edit.page');//客户-编辑客户界面@ajax
            Route::post('/customer/edit/upd/{id}','CustomerController@update')->name('customer.edit.upd');//客户-保存编辑结果@ajax
            Route::get('/customer/del/{id}','CustomerController@delete')->name('customer.del');//客户-删除客户信息
            Route::get('/data','DataController@index')->name('data.index');// 数据-默认页
            Route::post('/data/eCharts','DataController@eCharts')->name('data.eCharts');// 数据-数据图
            Route::get('/property','PropertyController@index')->name('property.index');// 资产-默认页
            Route::get('/marketing','MarketingController@index')->name('marketing.index');// 营销-默认页
            Route::get('/setup','SetupController@index')->name('setup.index');// 设置-默认页
            Route::post('/setup/edit','SetupController@edit')->name('setup.edit');//设置-保存设置信息@ajax
            Route::post('/setup/edit/upd','SetupController@update')->name('setup.edit.upd');//设置-修改店名@ajax
            Route::post('/setup/webUpl','SetupController@webUpload')->name('setup.webUpl');//设置-上传LOGO
            Route::post('/setup/ajaxAvatar','SetupController@ajaxAvatar')->name('setup.ajaxAvatar');//设置-ajax上传
            switch ($flag) {
                case 'business':// VR电子商务
                    // 分销
                    Route::get('/retail','RetailController@index')->name('retail_index');
                    Route::get('/retail/models/{id}','RetailController@models')->name('retail_models');
                    Route::get('/retail/pop/{id}','RetailController@putUpPop')->name('retail_putUpPop');
                    break;
                case 'museum':// VR博物馆

                    break;
                case 'crowd':// VR众包服务

                    break;
                case 'view':// VR查看阅读

                    break;
                case 'material':// VR素材商城

                    break;
                default:

                    break;
            }
        });
    }
});
