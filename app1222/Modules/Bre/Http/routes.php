<?php
Route::group(['prefix' => 'bre'], function() {
    Route::get('/', 'IndexController@index')->name('indexList');
    Route::get('/gif', 'IndexController@gif')->name('indexGif');
    
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
    Route::get('/study/case/{id}', 'IndexController@studyCase')->name('case');
    Route::get('/challenge', 'IndexController@challenge')->name('challenge');
	Route::get('/study/video/{id}/{type}','IndexController@getVideo')->name('video');
    Route::get('/changeUrl', 'IndexController@changeUrl')->name('changeUrl');
    Route::post('/study/video/postRemark', 'IndexController@postRemark')->name('postRemark');

    //参赛作品
    Route::post('/match', 'IndexController@match')->name('match');
    Route::post('/voteNum', 'IndexController@voteNum')->name('voteNum'); //投票数
    Route::get('/getCode', 'IndexController@getCode')->name('getCode');
    //赛事编号(海峡)
    Route::get('challenge/race_hx', 'IndexController@race_hx')->name('challenge');

    //挑战页教学视频
    Route::get('challenge/case/{id}', 'IndexController@ChallengeCase')->name('ChallengeCase');
    Route::get('challenge/video/{id}/{type}', 'IndexController@ChallengeVideo')->name('ChallengeVideo');

    //报名报信息
    Route::get('/registrationForm/{id}','RegistrationFormController@registrationForm')->name('registrationForm');
    Route::post('/registrationForm/postForm','RegistrationFormController@postForm')->name('postForm');//获取保存报名表信息

});

    Route::get('/bre/down',function(){
        return response()->download(
            realpath(base_path('public/down')).'/sucai.rar',
            '第二届海峡工业产品造型设计技能竞赛方案.rar'
        );
    });


Route::group(['prefix' => 'bre', 'middleware' => ['ruleengine']], function () {

    
});
