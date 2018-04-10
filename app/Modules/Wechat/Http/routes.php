<?php

Route::group(['prefix' => 'wechat'], function() {
    Route::any('/getCode', 'WechatController@getCode');
    Route::any('/accept', 'WechatController@accept');
});

Route::any('/wechat', 'WechatController@wechat');


Route::group(['prefix' => 'wePay'], function() {
    Route::any('/getOpenId/{id}', 'WeiPayController@getOpenId');
    Route::any('/wxpay/{id}', 'WeiPayController@wxpay');
    Route::any('/wePaySuccess', 'WeiPayController@wePaySuccess');
});
