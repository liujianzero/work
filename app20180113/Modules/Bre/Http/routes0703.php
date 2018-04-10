<?php




Route::group(['prefix' => 'bre'], function() {
    Route::get('/', 'IndexController@index')->name('indexList');

    
    Route::get('/service', 'IndexController@getService')->name('serviceList');
    Route::post('/feedbackInfo', 'IndexController@creatInfo')->name('feedbackCreate');
    Route::get('/serviceCaseList/{uid}', 'ServiceController@serviceCaseList')->name('serviceCaseList');
    Route::get('/serviceEvaluateDetail/{uid}', 'ServiceController@serviceEvaluateDetail')->name('serviceEvaluateDetail');
    Route::get('/serviceCaseDetail/{id}/{uid}', 'ServiceController@serviceCaseDetail')->name('serviceCaseDetail');
    Route::get('/ajaxAdd', 'ServiceController@ajaxAdd')->name('ajaxCreateAttention');
    Route::get('/ajaxDel', 'ServiceController@ajaxDel')->name('ajaxDeleteAttention');
    Route::post('/contactMe', 'ServiceController@contactMe')->name('messageCreate');

    
    Route::get('/agree/{code_name}', 'AgreementController@index')->name('agreementDetail');

    
    Route::get('/shop', 'IndexController@shop')->name('shopList');
    Route::get('/study', 'IndexController@study')->name('study');
    Route::get('/study/case1', 'IndexController@studyCase1')->name('study');
    Route::get('/challenge', 'IndexController@challenge')->name('challenge');

    Route::get('/changeUrl', 'IndexController@changeUrl')->name('changeUrl');
});




Route::group(['prefix' => 'bre', 'middleware' => ['ruleengine']], function () {
	

	

    
    
});
