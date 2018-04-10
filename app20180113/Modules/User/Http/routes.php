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
/*Route::get('/login', 'UserController@login');
Route::post('/userLogin', 'UserController@userLogin');
Route::get('/register', 'UserController@register');
Route::post('/userRegister', 'UserController@userRegister');
Route::get('/loginOut', 'UserController@loginOut');
Route::get('/oauthLogin/{type}', 'ThirdLoginController@oauthLogin');
Route::get('/oauthLogin/callback/{type}', 'ThirdLoginController@handleOAuthCallBack');*/

//用户登录 路由
Route::get('login', 'Auth\AuthController@getLogin')->name('loginCreatePage');
Route::get('login1', 'Auth\AuthController@getLogin1')->name('login1CreatePage');
Route::post('login', 'Auth\AuthController@postLogin')->name('loginCreate');
Route::get('logout', 'Auth\AuthController@getLogout')->name('logout');

//第三方登录
Route::get('oauth/{type}', 'Auth\AuthController@oauthLogin');
Route::get('oauth/{type}/callback', 'Auth\AuthController@handleOAuthCallBack');

//用户注册 路由
Route::get('register', 'Auth\AuthController@getRegister')->name('registerCreatePage');
Route::post('register', 'Auth\AuthController@postRegister')->name('registerCreate');
Route::post('register/phone', 'Auth\AuthController@phoneRegister');
Route::post('auth/mobileCode', 'Auth\AuthController@sendMobileCode');
Route::post('checkMobile', 'IndexController@checkMobile');

//用户账号 验证
Route::get('activeEmail/{validationInfo}', 'Auth\AuthController@activeEmail');
Route::get('waitActive/{email}', 'Auth\AuthController@waitActive');

//找回密码请求路由
Route::get('password/email', 'Auth\PasswordController@getEmail')->name('getPasswordPage');
Route::post('password/email', 'Auth\PasswordController@postEmail')->name('passwordUpdate');
Route::get('password/reSendEmail/{email}', 'Auth\PasswordController@reSendPasswordEmail')->name('reSendPasswordEmail');
Route::post('password/checkEmail', 'Auth\PasswordController@checkEmail')->name('checkEmail');
Route::post('password/checkCode', 'Auth\PasswordController@checkCode')->name('checkCode');
Route::get('password/mobile', 'Auth\PasswordController@getMobile');
Route::post('password/mobile', 'Auth\PasswordController@postMobile');
Route::get('password/mobileReset', 'Auth\PasswordController@getMobileReset');
Route::post('password/mobileReset', 'Auth\PasswordController@postMobileReset');
Route::get('password/mobileResetSuccess', 'Auth\PasswordController@mobileResetSuccess');
Route::post('password/mobilePasswordCode', 'Auth\PasswordController@sendMobilePasswordCode');


//重置密码请求路由
Route::get('resetValidation/{validationInfo}', 'Auth\PasswordController@resetValidation')->name('passwordResetValidation');
Route::get('passwordFail', 'Auth\PasswordController@passwordFail');
Route::get('waitValidation/{email}', 'Auth\PasswordController@waitValidation')->name('waitValidationPage');
Route::get('password/reset', 'Auth\PasswordController@getReset');
Route::post('password/reset', 'Auth\PasswordController@postReset')->name('nameResetCreate');

Route::get('flushCode', 'Auth\AuthController@flushCode')->name('flushCode');
Route::post('checkUserName', 'Auth\AuthController@checkUserName')->name('checkUserName');
Route::post('checkEmail', 'Auth\AuthController@checkEmail')->name('checkEmail');
Route::get('reSendActiveEmail/{email}', 'Auth\AuthController@reSendActiveEmail')->name('reSendActiveEmail');

Route::get('user/getZone', 'AuthController@getZone')->name('zoneDetail');

Route::get('/user/promote/{param}', 'PromoteController@promote')->name('promote'); //被推广出去的链接

/* 我的订单-匿名订单 */
Route::get('/anonymous/orderInfo/{id}', 'OrderController@orderInfo')->name('anonymous.orderInfo');// 获取商品并显示订单信息
Route::get('/anonymous/orderConfirm/{id}', 'OrderController@orderConfirm')->name('anonymous.orderConfirm');// 获取商品并显示订单信息
Route::post('/anonymous/orderAdd', 'OrderController@orderAdd')->name('anonymous.orderAdd');// 获取商品并显示订单信息
//地区三级联动
Route::get('/anonymous/ajaxcity','OrderController@ajaxCity')->name('anonymous.ajaxcity');
Route::get('/anonymous/ajaxarea','OrderController@ajaxArea')->name('anonymous.ajaxarea');



Route::get('/zone/{id}', 'UserCenterController@zone')->name('zone'); //被推广出去的链接
Route::get('/zone/index/{id}/{type}', 'UserCenterController@zoneGoods')->name('zoneGoods'); //商品
Route::get('/zone/service/{id}', 'UserCenterController@zoneService')->name('zoneService'); //商品
Route::get('/zone/models/{id}/{folder}', 'UserCenterController@zone')->name('zone'); //商品



Route::group(['prefix' => 'user', 'middleware' => 'auth'], function () {
    Route::get('/index','UserCenterController@index')->name('indexPage');

    Route::get('paylist', 'AuthController@getPayList')->name('paylist');
    //我的战队
    Route::get('/team','UserTeamController@getTeam')->name('getTeam');
    Route::get('/openTeam','UserTeamController@openTeam')->name('openTeam');
    Route::post('/userTeam','UserTeamController@userTeam')->name('userTeam');
    Route::post('/userGetTeam','UserTeamController@userGetTeam')->name('userGetTeam');
    Route::post('/is_disabled','UserTeamController@is_disabled')->name('is_disabled');
    Route::post('/is_del','UserTeamController@is_del')->name('is_del');
    Route::get('/modifyPwd/{id}','UserTeamController@getModifyPwd')->name('getModifyPwd');
    Route::post('/modifyPwd','UserTeamController@postModifyPwd')->name('postModifyPwd');
    Route::get('/getTeamPower','UserTeamController@getTeamPower')->name('getTeamPower');
    Route::post('/ajaxTeamPower','UserTeamController@ajaxTeamPower')->name('ajaxTeamPower');
    Route::post('/postTeamPower','UserTeamController@postTeamPower')->name('postTeamPower');



    //邮箱绑定路由
    Route::get('emailAuth', 'AuthController@getEmailAuth')->name('emailAuthPage');
    Route::get('sendEmailAuth', 'AuthController@sendEmailAuth')->name('sendEmailAuth');
    Route::get('reSendEmailAuth/{email}', 'AuthController@reSendEmailAuth');
    Route::get('verifyEmail/{validationInfo}', 'AuthController@verifyEmail')->name('verifyEmail');


    //手机绑定路由
    Route::get('phoneAuth', 'AuthController@getPhoneAuth')->name('phoneAuthPage');
    Route::post('phoneAuth', 'AuthController@postPhoneAuth');
    Route::post('sendBindSms', 'AuthController@sendBindSms');
    Route::get('unbindMobile', 'AuthController@getUnbindMobile');
    Route::post('sendUnbindSms', 'AuthController@sendUnbindSms');
    Route::post('unbindMobile', 'AuthController@postUnbindMobile');

    //身份认证路由
    Route::get('/realnameAuth', 'AuthController@getRealnameAuth')->name('realnameAuthCreatePage');
    Route::post('/realnameAuth', 'AuthController@postRealnameAuth')->name('realnameAuthCreate');
    Route::get('/reAuthRealname', 'AuthController@reAuthRealname')->name('reAuthRealnamePage');

    //机构认证路由
    Route::get('/organizationAuth', 'AuthController@getOrganizationAuth')->name('organizationAuthCreatePage');
    Route::post('/organizationAuth', 'AuthController@postOrganizationAuth')->name('organizationAuthCreate');
    Route::get('/reAuthOrganization', 'AuthController@reAuthOrganization')->name('reAuthOrganizationPage');

    //用户支付宝认证路由
    Route::get('/alipayAuth', 'AuthController@getAlipayAuth')->name('alipayAuthCreatePage');
    Route::post('/alipayAuth', 'AuthController@postAlipayAuth')->name('alipayAuthCreate');
    Route::get('/alipayAuthList', 'AuthController@listAlipayAuth')->name('alipayAuthList');
    Route::get('/alipayAuthSchedule/{alipayAuthId}', 'AuthController@getAlipayAuthSchedule')->name('alipayAuthSchedule');
    Route::post('/verifyAlipayAuthCash', 'AuthController@verifyAlipayAuthCash')->name('verifyAlipayAuthCash');
    Route::post('changeAlipayAuth', 'AuthController@changeAlipayAuth')->name('alipayAuthStatusUpdate');

    //用户银行认证路由
    Route::get('/bankAuth', 'AuthController@getBankAuth')->name('bankAuthCreatePage');
    Route::post('/bankAuth', 'AuthController@postBankAuth')->name('bankAuthCreate');
    Route::get('/bankAuthList', 'AuthController@listBankAuth')->name('bankAuthList');
    Route::get('/bankAuthSchedule/{bankAuthId}', 'AuthController@getBankAuthSchedule')->name('waitBankAuthPage');
    Route::post('/verifyBankAuthCash', 'AuthController@verifyBankAuthCash')->name('verifyBankAuthCash');
    Route::get('/unBindBankAuth/{id}', 'AuthController@unBindBankAuth')->name('');

    //用户收藏/用户关注
    Route::get('/myshop','UserMoreController@myshop')->name('myshop'); //我收藏的店铺
    Route::get('/myfocus','UserMoreController@myTocusTask')->name('myfocusList');
    Route::get('/ajaxDeleteFocus/{id}','UserMoreController@ajaxDeleteFocus')->name('ajaxDeleteFocus');
    Route::get('/userfocus','UserMoreController@userFocus')->name('userFocusList');
    Route::get('/userFocusDelete/{id}','UserMoreController@userFocusDelete')->name('userFocusDelete');
    Route::get('/userNotFocus/{uid}','UserMoreController@userNotFocus')->name('userNotFocus');
    //我的粉丝
    Route::get('/userfans','UserMoreController@userFans')->name('userfans');

    //用户我发布的任务
    Route::get('/myTasksList','UserMoreController@myTasksList')->name('myTasksList');
    Route::get('/myTaskAxis','UserMoreController@myTaskAxis')->name('myTaskAxis');
    Route::get('/myTaskAxisAjax','UserMoreController@myTaskAxisAjax')->name('myTaskAxisAjax');
    Route::get('/myTask','UserMoreController@myTask')->name('myTask');
    Route::get('/acceptTasksList','UserMoreController@acceptTasksList')->name('acceptTasksList');
    Route::get('/myAjaxTask','UserMoreController@myAjaxTask')->name('myAjaxTask');

    Route::post('/myTask/ajax/del/{id}','UserMoreController@myTaskDel')->name('myTask.ajax.del');
    Route::get('/myTask/pay/{id}/{type}','UserMoreController@myTaskPay')->name('myTask.pay');

    //用户雇主交易评价
    Route::get('/myCommentOwner','UserMoreController@myCommentOwner')->name('myCommentList');
    Route::get('/myWorkHistory','UserMoreController@myWorkHistory')->name('myWorkList');
    Route::get('/myWorkHistoryAxis','UserMoreController@myWorkHistoryAxis')->name('myWorkHistoryAxis');
    Route::get('/workComment','UserMoreController@workComment')->name('workCommentList');//威客交易评价
    //用户未发布的任务
    Route::get('/unreleasedTasks','UserMoreController@unreleasedTasks')->name('unreleasedTasksList');
    Route::get('/unreleasedTasksDelete/{id}','UserMoreController@unreleasedTasksDelete')->name('unreleasedTasksDelete');

    Route::post('changeBankAuth', 'AuthController@changeBankAuth')->name('bankStatusUpdate');


    //用户个人信息设置
    Route::get('/info','UserCenterController@info')->name('infoUpdatePage');
    Route::post('/infoUpdate','UserCenterController@infoUpdate')->name('infoUpdate');

    //安全设置
    Route::get('/safeSet','UserCenterController@safeSet')->name('safeSetPage');
    //微信绑定
    Route::get('/bindWeChat','UserCenterController@bindWeChat')->name('bindWeChatPage');

    //域名认证
    Route::get('/url', 'AuthController@getUrl')->name('url');
    Route::post('/url', 'AuthController@postUrl');

    //用户登录密码修改部分
    Route::get('/loginPassword','UserCenterController@loginPassword')->name('passwordUpdatePage');
    Route::post('/passwordUpdate','UserCenterController@passwordUpdate')->name('passwordUpdate');
    //用户支付密码修改部分
    Route::get('/payPassword','UserCenterController@payPassword')->name('payPasswordUpdatePage');
    Route::get('/checkInterVal','UserCenterController@checkInterVal')->name('checkInterVal');
    Route::post('/payPasswordUpdate','UserCenterController@payPasswordUpdate')->name('payPasswordUpdate');
    Route::post('/sendEmail','UserCenterController@sendEmail')->name('sendEmail');
    Route::post('/checkEmail','UserCenterController@checkEmail')->name('checkEmail');
    Route::post('/validate','UserCenterController@validateCode')->name('validateCodePage');
    //用户技能标签部分
    Route::get('/skill','UserCenterController@skill')->name('skillUpdatePage');
    Route::post('/skillSave','UserCenterController@skillSave')->name('skillCreate');
    //原始的标签修改页面
    Route::get('/skillUpdata/{id}','UserCenterController@skillUpdata')->name('skillUpdate');
    Route::post('/tagUpdate','UserCenterController@tagUpdate')->name('tagUpdate');
    Route::get('/delTag','UserCenterController@delTag')->name('tagDelete');
    Route::get('/hotTag','UserCenterController@hotTag');
    //用户头像部分
    Route::get('/avatar','UserCenterController@userAvatar')->name('userAvatarPage');
    Route::post('/ajaxAvatar','UserCenterController@ajaxAvatar')->name('headUpdate');
    Route::post('/headEdit','UserCenterController@AvatarEdit')->name('headEdit');
    //地区三级联动
    Route::get('/ajaxcity','UserCenterController@ajaxCity')->name('ajaxcity');
    Route::get('/ajaxarea','UserCenterController@ajaxArea')->name('ajaxarea');
    //用户每日签到
    Route::post('/ajaxDailySign','UserCenterController@ajaxDailySign')->name('ajaxDailySign');
    //demo发送邮件修改密码
    //Route::get('/account','UserCenterController@account');
    //Route::post('/password','UserCenterController@password');
    //Route::post('/psUpdate','UserCenterController@psUpdate');
    //Route::get('/sendEmail','UserCenterController@sendEmail');

    //空间页面
    Route::get('/personCase', 'UserController@getPersonCase')->name('personCasePage');
    //个人空间案例添加
    Route::get('/addpersoncase/{id}', 'UserController@getAddPersonCase')->name('caseCreatePage');
    //个人空间案例添加
    Route::post('/addCase', 'UserController@postAddCase')->name('caseCreate');
    //个人空间评价
    Route::get('/personevaluation', 'UserController@getPersonEvaluation')->name('');
    Route::get('/ajaxUpdateCase','UserController@ajaxUpdateCase')->name('ajaxUpdateCase');
    Route::get('/ajaxUpdateBack','UserController@ajaxUpdateBack')->name('ajaxUpdateBack');

    Route::post('/ajaxUpdatePic','UserController@ajaxUpdatePic')->name('ajaxUpdatePic');
    Route::get('/ajaxDelPic','UserController@ajaxDelPic')->name('ajaxDeletePic');

    //个人空间评价详情页
    Route::get('/personevaluationdetail/{id}','UserController@getPersonEvaluationDetail')->name('personevaluationPage');
    //个人空间成功案例
    Route::get('/','UserCenterController@assetdetail')->name('successCaseList');

    //用户中心我的消息
    Route::get('/messageList/{type}', 'MessageReceiveController@messageList')->name('messageList'); //我的消息列表
    Route::post('/allChange', 'MessageReceiveController@allChange')->name('allMessageStatusUpdate'); //批量改变消息状态
    Route::post('/contactMe', 'MessageReceiveController@contactMe')->name('messageCreate'); //站内信发消息
    Route::post('/changeStatus', 'MessageReceiveController@postChangeStatus')->name('messageStatusUpdate'); //改变信息读取状态

    //更改用户头像
    Route::post('/changeAvatar', 'IndexController@ajaxChangeAvatar')->name('changeAvatar'); //更改用户头像

    //删除用户成功案例
    Route::post('/ajaxDeleteSuccess', 'UserController@ajaxDeleteSuccess')->name('UserController');

    //我是威客成功案例编辑视图
    Route::get('/editpersoncase/{id}', 'UserController@getEditPersonCase')->name('caseUpdatePage');
    //我是威客成功案例编辑
    Route::post('/editCase', 'UserController@postEditCase')->name('caseUpdate');

    //修改支付提示状态
    Route::post('/updateTips', 'IndexController@updateTips')->name('updateTips');

    /* 我的店铺 - 店铺管理 */
    Route::get('/shop/list', 'MyShopController@stores')->name('shop.list');// 店铺列表
    Route::post('/ajax/shop/list', 'MyShopController@getStoreList')->name('ajax.shop.list');// ajax@获取店铺列表
    Route::get('/shop/type', 'MyShopController@storeType')->name('shop.type');// 选择要创建的店铺类型
    Route::get('/shop/create/{id}', 'MyShopController@storeCreate')->name('shop.create');// 选择要创建的店铺类型
    Route::post('/shop/creating', 'MyShopController@storeCreating')->name('shop.creating');// 选择要创建的店铺类型
    Route::get('/shop/admin/{id}', 'MyShopController@agentAdminLogin')->name('shop.admin');// 登录相应的店铺管理员
    Route::post('/ajax/shop/delete/{id}', 'MyShopController@storeDelete')->name('ajax.shop.delete');// ajax@删除店铺
    Route::post('/ajax/shop/renew/{id}', 'MyShopController@storeRenew')->name('ajax.shop.renew');// 店铺续费页面 @ajax

    /* 我的店铺 */
    Route::get('/myShopReleaseGoods', 'MyShopController@releaseGoods')->name('myShop.releaseGoods');// 发布商品页面
    Route::get('/myShopGetUserFolders', 'MyShopController@getUserFolders')->name('myShop.getUserFolders');// 获取用户文件夹
    Route::get('/myShopGetFolderInfo/{id}', 'MyShopController@getFolderInfo')->name('myShop.getFolderInfo');// 获取用户文件夹信息
    Route::get('/myShopGetModels/{id}', 'MyShopController@getModels')->name('myShop.getModels');// 获取某个文件夹的所有作品
    Route::get('/myShopGetModelInfo/{id}', 'MyShopController@getModelInfo')->name('myShop.getModelInfo');// 获取某个作品的信息
    Route::get('/myShopGetCategory/{id}', 'MyShopController@getCategory')->name('myShop.getCategory');// 获取分类
    Route::post('/myShopAddGoods', 'MyShopController@addGoods')->name('myShop.addGoods');// 添加商品处理
    Route::get('/myShopGetGoods', 'MyShopController@getGoods')->name('myShop.getGoods');// 获取商品列表
    Route::get('/myShopGetGoodsInfo/{id}', 'MyShopController@getGoodsInfo')->name('myShop.getGoodsInfo');// 获取商品信息
    Route::post('/myShopEditGoods', 'MyShopController@editGoods')->name('myShop.editGoods');// 编辑商品处理
    Route::post('/myShopSetGoodsShelves/{id}', 'MyShopController@setGoodsShelves')->name('myShop.setGoodsShelves');// 商品下架

    /* 我的店铺 - 属性 */
    Route::get('/goodsType', 'MyShopController@goodsType')->name('myShop.goodsType'); //属性类型@页面
    Route::post('/ajax/addType', 'MyShopController@addType')->name('myShop.addType'); //属性类型-添加@处理
    Route::post('/ajax/editType', 'MyShopController@editType')->name('myShop.editType'); //属性类型-编辑@处理
    Route::post('/ajax/delType', 'MyShopController@delType')->name('myShop.delType'); //属性类型-移除@处理
    Route::get('/goodsAttr/{id}', 'MyShopController@goodsAttr')->name('myShop.goodsAttr'); //属性@页面
    Route::post('/addAttr', 'MyShopController@addAttr')->name('myShop.addAttr'); //属性-添加@处理
    Route::get('/editAttr/{id}', 'MyShopController@editAttrPage')->name('myShop.editAttrPage'); //属性-编辑@页面
    Route::post('/editAttr', 'MyShopController@editAttr')->name('myShop.editAttr'); //属性-编辑@处理
    Route::post('/ajax/delAttr', 'MyShopController@delAttr')->name('myShop.delAttr'); //属性-移除@处理
    Route::post('/ajax/getType', 'MyShopController@getType')->name('myShop.getGoodsType'); //获取属性类型列表
    Route::post('/ajax/getAttr/{id}', 'MyShopController@getAttr')->name('myShop.getGoodsAttr'); //获取属性列表
    Route::post('/ajax/delGoodsAttr', 'MyShopController@delGoodsAttr')->name('myShop.delGoodsAttr'); //商品属性-移除@处理

    /* 商品购物车 */
    Route::get('/myCart', 'GoodsCarts@cart')->name('myCart.cart'); //我的购物车@页面
    Route::post('/ajax/cartNumber', 'GoodsCarts@cartNumber')->name('myCart.cartNumber'); //ajax-更新购物车数量@处理
    Route::post('/ajax/delCart/{id}', 'GoodsCarts@delCart')->name('myCart.delCart'); //ajax-删除商品@处理
    Route::post('/cart/changeNumber', 'GoodsCarts@changeNumber')->name('myCart.changeNumber'); //更新购物车数量@处理

    /* 我的订单 - 出售商品 */
    Route::get('/goods/buy/{ids}', 'MyOrderController@goodsBuy')->name('myOrder.myGoodsBuy');// 出售商品-订单创建页面
    Route::post('/goods/add', 'MyOrderController@goodsAdd')->middleware('order.token')->name('myOrder.myGoodsAdd');// 出售商品-订单创建
    Route::get('/goods/payment/{ids}/{address}', 'MyOrderController@payment')->name('myOrder.payment');// 出售商品-订单提交成功
    Route::get('/goods/goodsPay/{id}/{ids}', 'MyOrderController@goodsPay')->name('myOrder.goodsPay');// 出售商品-模拟支付
    Route::get('/myOrderGoodsOut', 'MyOrderController@myGoodsOut')->name('myOrder.myGoodsOut');// 出售商品-已购买的商品
    Route::post('/ajax/goods/cancel/{id}', 'MyOrderController@cancelGoodsOrder')->name('goods.cancelOrder');// 出售商品-已购买的商品-取消订单
    Route::post('/ajax/goods/sure/{id}', 'MyOrderController@sureGoodsOrder')->name('goods.sureOrder');// 出售商品-已购买的商品-确认收货
    Route::get('/goods/order/outInfo/{id}', 'MyOrderController@goodsOutInfo')->name('goods.outInfo');// 出售商品-已购买的商品-订单详情
    Route::get('/goods/user/evaluate/{id}', 'MyOrderController@userEvaluate')->name('goods.userEvaluate');// 出售商品-已购买的商品-订单评价
    Route::post('/goods/user/make/evaluate', 'MyOrderController@makeUserEvaluate')->name('goods.userEvaluate.make');// 出售商品-已购买的商品-订单评价处理
    Route::get('/goods/express/info/{id}', 'MyOrderController@goodsPostInfo')->name('goods.express.info');// 出售商品-已购买的商品-物流信息
    Route::get('/myOrderGoodsIn', 'MyOrderController@myGoodsIn')->name('myOrder.myGoodsIn');// 出售商品-已卖出的商品
    Route::get('/goods/expressIn/info/{id}', 'MyOrderController@inPostInfo')->name('goods.expressIn.info');// 出售商品-已卖出的商品-物流信息
    Route::get('/goods/order/inInfo/{id}', 'MyOrderController@goodsInInfo')->name('goods.inInfo');// 出售商品-已卖出的商品-订单详情
    Route::post('/ajax/goods/delivery/{id}', 'MyOrderController@goodsDelivery')->name('goods.delivery');// 出售商品-已卖出的商品-开始发货
    Route::post('/ajax/goods/handle/delivery', 'MyOrderController@delivery')->name('goods.delivery.handle');// 出售商品-已卖出的商品-开始发货处理
    Route::get('/goods/shop/evaluate/{id}', 'MyOrderController@shopEvaluate')->name('goods.shopEvaluate');// 出售商品-已卖出的商品-订单评价
    Route::post('/goods/shop/make/evaluate', 'MyOrderController@makeShopEvaluate')->name('goods.shopEvaluate.make');// 出售商品-已卖出的商品-订单评价处理
    Route::get('/goods/info/web/{id}', 'MyOrderController@infoWeb')->name('goods.info.web');// 出售商品-商品详情-web
    Route::post('/ajax/addCart', 'MyOrderController@addCart')->name('goods.addCart');// 出售商品-加入购物车
    Route::post('/goods/buyNow', 'MyOrderController@buyNow')->name('goods.buyNow');// 出售商品-立即购买

    /* 我的地址 */
    Route::post('/ajax/addAddress', 'MyOrderController@addAddress')->name('myOrder.addAddress');// 添加地址
    Route::post('/ajax/delAddress/{id}', 'MyOrderController@delAddress')->name('myOrder.delAddress');// 删除地址
    Route::post('/ajax/editAddressPage/{id}', 'MyOrderController@editAddressPage')->name('myOrder.editAddressPage');// 编辑地址页面
    Route::post('/ajax/editAddress', 'MyOrderController@editAddress')->name('myOrder.editAddress');// 编辑地址
    Route::post('/ajax/cancelAddress', 'MyOrderController@cancelAddress')->name('myOrder.cancelAddress');// 取消编辑地址

    /* 我的订单 - 购买服务 */
    Route::get('/myOrderTaskOut', 'MyOrderController@myTaskOut')->name('myOrder.myTaskOut');// 我发布的任务
    Route::get('/task/buy/{id}', 'MyOrderController@myTaskOutBuy')->name('myOrder.myTaskOutBuy');// 我发布的任务-订单创建页面
    Route::post('/task/add', 'MyOrderController@myTaskOutAdd')->middleware('order.token')->name('myOrder.myTaskOutAdd');// 我发布的任务-订单创建
    Route::get('/task/makeSure/{id}', 'MyOrderController@myTaskOutMakeSure')->name('myOrder.myTaskOutMakeSure');// 我发布的任务-订单确认页面
    Route::post('/myOrderTaskOut/info/{id}', 'MyOrderController@myTaskOutInfo')->name('myOrder.myTaskOutInfo');// 我发布的任务-详情
    Route::post('/myOrderTaskOut/cancel/{id}', 'MyOrderController@myTaskOutCancel')->name('myOrder.myTaskOutCancel');// 我发布的任务-取消订单
    Route::post('/myOrderTaskOut/edit/{id}', 'MyOrderController@myTaskOutEdit')->name('myOrder.myTaskOutEdit');// 我发布的任务-修改订单页面
    Route::get('/myOrderTaskOut/pay/{id}', 'MyOrderController@myTaskOutPay')->name('myOrder.myTaskOutPay');// 我发布的任务-模拟支付
    Route::post('/myOrderTaskOut/update', 'MyOrderController@myTaskOutUpdate')->name('myOrder.myTaskOutUpdate');// 我发布的任务-修改订单处理
    Route::post('/myOrderTaskOut/reject', 'MyOrderController@myTaskOutReject')->name('myOrder.myTaskOutReject');// 我发布的任务-驳回任务
    Route::post('/myOrderTaskOut/check/{id}', 'MyOrderController@myTaskOutCheck')->name('myOrder.myTaskOutCheck');// 我发布的任务-任务验收
    Route::get('/myOrderTaskOut/download/{id}', 'MyOrderController@myTaskOutDownload')->name('myOrder.myTaskOutDownload');// 我发布的任务-下载附件
    Route::post('/myOrderTaskOut/evaluation/{id}', 'MyOrderController@myTaskOutEvaluation')->name('myOrder.myTaskOutEvaluation');// 我发布的任务-评价任务
    Route::get('/myOrderTaskIn', 'MyOrderController@myTaskIn')->name('myOrder.myTaskIn');// 我参与的任务
    Route::get('/myOrderTaskIn/download/{id}', 'MyOrderController@myTaskInDownload')->name('myOrder.myTaskInDownload');// 我参与的任务-下载附件
    Route::post('/myOrderTaskIn/accept/{id}', 'MyOrderController@myTaskInAccept')->name('myOrder.myTaskInAccept');// 我参与的任务-承接任务
    Route::post('/myOrderTaskIn/submit/{id}', 'MyOrderController@myTaskInSubmit')->name('myOrder.myTaskInSubmit');// 我参与的任务-提交任务
    Route::post('/myOrderTaskIn/evaluation/{id}', 'MyOrderController@myTaskInEvaluation')->name('myOrder.myTaskInEvaluation');// 我参与的任务-评价任务
    Route::get('/myOrderGetUserFolders', 'MyOrderController@getUserFolders')->name('myOrder.getUserFolders');// 获取用户文件夹
    Route::get('/myOrderGetFolderInfo/{id}', 'MyOrderController@getFolderInfo')->name('myOrder.getFolderInfo');// 获取用户文件夹信息
    Route::get('/myOrderGetModels/{id}', 'MyOrderController@getModels')->name('myOrder.getModels');// 获取某个文件夹的所有作品

    /* 我的订单 - 查看付费 */
    Route::get('/viewPay/denied/{id}', 'MyOrderController@viewPayDenied')->name('myOrder.viewPayDenied');// 未购买时的页面
    Route::get('/viewPay/buy/{id}', 'MyOrderController@viewPayBuy')->name('myOrder.viewPayBuy');// 购买查看付费
    Route::post('/viewPay/add', 'MyOrderController@viewPayAdd')->middleware('order.token')->name('myOrder.viewPayAdd');// 进行下单
    Route::get('/viewPay/makeSure/{id}', 'MyOrderController@viewMakeSure')->name('myOrder.viewMakeSure');// 订单确认页面
    Route::post('/viewPay/cancel/{id}', 'MyOrderController@viewCancel')->name('myOrder.viewCancel');// 取消订单
    Route::get('/viewPay/pay/{id}', 'MyOrderController@viewPay')->name('myOrder.viewPay');// 模拟支付
    Route::get('/myOrderViewOut', 'MyOrderController@myViewOut')->name('myOrder.myViewOut');// 已购买的查看付费
    Route::get('/myOrderViewIn', 'MyOrderController@myViewIn')->name('myOrder.myViewIn');// 已出售的查看付费

    /* 我的订单 - 出售素材 */
    Route::get('/material/buy/{id}', 'MyOrderController@materialBuy')->name('myOrder.materialBuy');// 购买出售素材
    Route::post('/material/add', 'MyOrderController@materialAdd')->middleware('order.token')->name('myOrder.materialAdd');// 进行下单
    Route::get('/material/makeSure/{id}', 'MyOrderController@materialMakeSure')->name('myOrder.materialMakeSure');// 订单确认页面
    Route::get('/material/pay/{id}', 'MyOrderController@materialPay')->name('myOrder.materialPay');// 模拟支付
    Route::get('/material/download/{id}', 'MyOrderController@downloadZip')->name('myOrder.materialDownload');// 下载素材
    Route::get('/myOrderMaterialOut', 'MyOrderController@myMaterialOut')->name('myOrder.myMaterialOut');// 已购买的素材
    Route::get('/myOrderMaterialIn', 'MyOrderController@myMaterialIn')->name('myOrder.myMaterialIn');// 已出售的素材
    Route::post('/material/cancel/{id}', 'MyOrderController@materialCancel')->name('myOrder.materialCancel');// 取消订单


    //我的店铺设置
    Route::get('/shop', 'ShopController@getShop')->name('userShop');
    //保存店铺信息
    Route::post('/shop', 'ShopController@postShopInfo')->name('postShop');
    //ajax获取地区二级、三级信息
    Route::post('/ajaxGetCity', 'ShopController@ajaxGetCity')->name('ajaxGetCity');
    //ajax获取地区三级信息
    Route::post('/ajaxGetArea', 'ShopController@ajaxGetArea')->name('ajaxGetArea');
    //ajax获取二级行业分类信息
    Route::post('/ajaxGetSecondCate', 'ShopController@ajaxGetSecondCate')->name('ajaxGetSecondCate');
    //店铺企业认证
    Route::get('/enterpriseAuth', 'ShopController@getEnterpriseAuth')->name('enterpriseAuth');
    //保存企业认证信息
    Route::post('/enterpriseAuth', 'ShopController@postEnterpriseAuth')->name('postEnterpriseAuth');
    Route::post('/fileUpload','ShopController@fileUpload')->name('enterpriseAuthFileCreate');//企业认证文件上传
    Route::get('/fileDelete','ShopController@fileDelete')->name('enterpriseAuthFileDelete');//企业认证文件上传删除
    Route::get('/enterpriseAuthAgain', 'ShopController@enterpriseAuthAgain')->name('enterpriseAuthAgain');//重新企业认证
    //店铺案例管理
    Route::get('/myShopSuccessCase', 'ShopController@shopSuccessCase')->name('shopSuccessCase');
    Route::get('/addShopSuccess', 'ShopController@addShopSuccess')->name('addShopSuccess');//添加案例视图
    Route::post('/postAddShopSuccess','ShopController@postAddShopSuccess')->name('postAddShopSuccess');//添加案例
    Route::get('/editShopSuccess/{id}', 'ShopController@editShopSuccess')->name('editShopSuccess');//编辑案例视图
    Route::post('/postEditShopSuccess','ShopController@postEditShopSuccess')->name('postEditShopSuccess');//编辑案例
    Route::post('/deleteShopSuccess','ShopController@deleteShopSuccess')->name('deleteShopSuccess');//删除案例

    Route::get('/serviceCreate', 'ServiceController@serviceCreate')->name('serviceCreate');//店铺发布服务
    Route::post('/serviceUpdate','ServiceController@serviceUpdate')->name('serviceUpdate');//发布服务提交
    Route::get('/serviceBounty/{id}','ServiceController@serviceBounty')->name('serviceBounty');//发布服务付款页面
    Route::post('/serviceBountyPay','ServiceController@serviceBountyPay')->name('serviceBountyPay');//发布服务付款页面
    Route::get('/serviceList', 'ServiceController@serviceList')->name('serviceList');//店铺服务列表
    Route::get('/serviceAdded/{id}', 'ServiceController@serviceAdded')->name('serviceAdded');//服务上下架
    Route::get('/serviceDelete/{id}', 'ServiceController@serviceDelete')->name('serviceDelete');//服务软删除
    Route::get('/serviceMine', 'ServiceController@serviceMine')->name('serviceMine');//店铺我购买的服务
    Route::get('/serviceMyJob', 'ServiceController@serviceMyJob')->name('serviceMyJob');//店铺我购买的服务
    Route::get('/serviceEdit/{id}', 'ServiceController@serviceEdit')->name('serviceEdit');//服务编辑功能
    Route::post('/serviceEditUpdate', 'ServiceController@serviceEditUpdate')->name('serviceEditUpdate');//服务编辑提交控制器
    Route::get('/serviceAttchDelete', 'ServiceController@serviceAttchDelete')->name('serviceAttchDelete');//服务编辑删除附件
    Route::post('/serviceEditCreate', 'ServiceController@serviceEditCreate')->name('serviceEditCreate');//未审核通过的服务编辑提交
    Route::get('/serviceEditNew/{id}', 'ServiceController@serviceEditNew')->name('serviceEditNew');//未审核通过的服务编辑页面
    Route::get('/shopcommentowner', 'ServiceController@shopcommentowner')->name('shopcommentowner');//店铺交易评价
    Route::get('/waitServiceHandle/{id}', 'ServiceController@waitServiceHandle')->name('waitServiceHandle');//店铺发布服务等待页面
    Route::post('/servicecashvalid', 'ServiceController@serviceCashValid')->name('serviceCashValid');//店铺发布服务验证金额

    //店铺发布商品页面
    Route::get('/pubGoods', 'GoodsController@getPubGoods')->name('getPubGoods');
    //发布商品处理
    Route::post('/pubGoods', 'GoodsController@postPubGoods')->name('postPubGoods');
    //成功发布商品等待审核
    Route::get('waitGoodsHandle/{id}', 'GoodsController@waitGoodsHandle');
    //店铺商品管理
    Route::get('/goodsShop', 'GoodsController@shopGoods')->name('shopGoods');
    //店铺编辑商品页面
    Route::get('/editGoods/{id}', 'GoodsController@editGoods')->name('editGoods');
    //店铺商品编辑保存信息
    Route::post('/postEditGoods', 'GoodsController@postEditGoods')->name('postEditGoods');
    Route::post('/goodsCashValid', 'GoodsController@goodsCashValid')->name('goodsCashValid');//店铺发布商品验证金额

    //（我是雇主 我购买的商品列表）
    Route::get('/myBuyGoods', 'GoodsController@myBuyGoods')->name('myBuyGoods');

    //（我是威客 我卖出的商品列表）
    Route::get('/mySellGoods', 'GoodsController@mySellGoods')->name('mySellGoods');

    //我收藏的店铺
    Route::get('/myCollectShop', 'ShopController@myCollectShop')->name('myCollectShop');
    Route::post('/cancelCollect', 'ShopController@cancelCollect')->name('cancelCollect'); //取消收藏

    //我的店铺提示
    Route::get('/myShopHint', 'ShopController@myShopHint')->name('myShopHint');


    Route::post('/changeGoodsStatus', 'GoodsController@changeGoodsStatus')->name('changeGoodsStatus'); //修改商品状态

    //我的店铺中转链接
    Route::get('/switchUrl', 'ShopController@switchUrl')->name('switchUrl');

    //实名认证提示
    Route::get('/userShopBefore', 'ShopController@userShopBefore')->name('userShopBefore');

    //我的回答
    Route::get('/myAnswer', 'QuestionController@myAnswer')->name('myAnswer');
    //我的提问
    Route::get('/myquestion', 'QuestionController@myQuestion')->name('myquestion');


    //我的推广链接
    Route::get('/promoteUrl', 'PromoteController@promoteUrl')->name('promoteUrl');
    //我的推广收益
    Route::get('/promoteProfit', 'PromoteController@promoteProfit')->name('promoteUrl');


    //vip购买记录
    Route::get('/vippaylist', 'ShopController@vippaylist')->name('vippaylist');

    Route::get('/vippaylog/{id}', 'ShopController@vippaylog')->name('vippaylog'); //vip购买记录详情


    Route::get('/vipshopbar', 'ShopController@vipshopbar')->name('vipshopbar'); //店铺装修
    Route::post('/vipshopbar', 'ShopController@postVipshopbar'); //店铺装修

    Route::get('delVipshopFile', 'ShopController@delVipshopFile');

    //

    Route::post('/createFolder', 'UserCenterController@createFolder')->name('createFolder'); //新建文件夹
    Route::post('/updateFolder', 'UserCenterController@updateFolder')->name('updateFolder'); //修改文件夹
    Route::post('/deleteFolder', 'UserCenterController@deleteFolder')->name('deleteFolder'); //删除文件夹
	Route::post('/deleteFolderProduct', 'UserCenterController@deleteFolderProduct')->name('deleteFolderProduct'); //删除文件夹及作品
    Route::post('/saveCoverImg', 'UserCenterController@saveCoverImg')->name('saveCoverImg'); //制作封面
    Route::post('/setModelCover', 'UserCenterController@setModelCover')->name('setModelCover'); //上传模型封面

    Route::post('/setFolderCover', 'UserCenterController@setFolderCover')->name('setFolderCover'); //作品效果图设为封面
    Route::post('/setFolderAuth', 'UserCenterController@setFolderAuth')->name('setFolderAuth'); //设置文件夹的访问权限


    Route::get('/folder/{id}', 'UserCenterController@folder')->name('folder'); //制作封面


    Route::post('/editModel', 'UserCenterController@editModel')->name('editModel'); //编辑作品的信息
    Route::post('/deleteModel', 'UserCenterController@deleteModel')->name('deleteModel'); //删除作品
    Route::post('/moveModel', 'UserCenterController@moveModel')->name('moveModel'); //删除作品



    Route::post('/focus', 'UserCenterController@focus')->name('focus'); //关注用户
    Route::post('/cfocus', 'UserCenterController@cancelfocus')->name('cancelfocus'); //取消

    Route::get('/myCollection', 'UserCenterController@myCollection')->name('myCollection'); //我的收藏


    Route::get('/publicGoodsStep', 'GoodsController@publicGoodsStep')->name('publicGoodsStep'); //发布作品流程
    Route::post('/addGoods', 'GoodsController@addGoods')->name('addGoods'); //商店添加作品

    Route::get('/publicServiceStep', 'ServiceController@publicServiceStep')->name('publicServiceStep'); //发布服务流程
    Route::post('/addService', 'ServiceController@addService')->name('addService'); //添加服务

    Route::get('/goodsEdit/{id}', 'GoodsController@getEditGood')->name('getEditGood'); //作品,服务编辑页mian
    Route::post('/goodsEditAjax', 'GoodsController@postAjaxEditGood')->name('postAjaxEditGood'); //商品，服务编辑保存

    Route::get('/paidGoods', 'GoodsController@paidViewGoods')->name('paidViewGoods'); //付费查看作品

    Route::get('/publicPaidGoodsStep', 'GoodsController@publicPaidGoodsStep')->name('publicPaidGoodsStep'); //添加付费查看作品流程
    Route::post('/addPaidViewGoods', 'GoodsController@addPaidViewGoods')->name('addPaidViewGoods'); //添加付费查看作品

    Route::get('/paidViewGoodsEdit/{id}', 'GoodsController@paidViewGoodsEdit')->name('paidViewGoodsEdit'); //作品,服务编辑页mian
    Route::post('/paidViewGoodsEditAjax', 'GoodsController@paidViewGoodsEditAjax')->name('paidViewGoodsEditAjax'); //商品，服务编辑保存


});

	//会员模块
	Route::group(['prefix' => 'member'], function () {

		Route::get('/', 'MemberController@index')->name('index');
		Route::get('/info', 'MemberController@info')->name('info');
		Route::get('/select', 'MemberController@select')->name('select');
//    Route::get('/bounty/{id}/{type}/{models_id}', 'MemberController@bountys')->name('bountys')->middleware('auth'); //测试
//    Route::get('/bountys/{id}/{type}/{models_id}', 'MemberController@bounty')->name('bounty')->middleware('auth'); //测试
    Route::get('/bounty/{id}/{type}/{models_id}', 'MemberController@bounty')->name('bounty')->middleware('auth'); //正确
		Route::post('/bountyUpdate', 'MemberController@bountyUpdate')->name('bountyUpdate')->middleware('auth');

	});





	//模型
	Route::group(['prefix' => 'models'], function () {

		Route::get('/', 'ModelContentController@modelsList')->name('modelsList');

	});

	Route::get('/addv2', 'ModelContentController@addv2')->name('addv2')->middleware('auth');
	Route::post('/addModel', 'ModelContentController@addModel')->name('addModel')->middleware('auth');
	Route::get('/view-{id}', 'ModelContentController@modelsView')->name('modelsView');
	Route::get('/shareView-{id}', 'ModelContentController@modelsShareView')->name('modelsShareView');
	Route::get('/embedView-{id}', 'ModelContentController@modelsEmbedView')->name('modelsEmbedView');

	Route::get('/editModel-{id}', 'ModelContentController@editModel')->name('editModel')->middleware('auth');
	Route::post('/editModel', 'ModelContentController@editModelSave')->name('editModelSave')->middleware('auth');


	Route::get('/addvr', 'ModelContentController@addvr')->name('addvr')->middleware('auth');
	Route::post('/addvr', 'ModelContentController@addvrModel')->name('addvrModel')->middleware('auth');
	Route::post('/saveImg', 'ModelContentController@saveImg')->name('saveImg')->middleware('auth');

	Route::get('/editVrModel-{id}', 'ModelContentController@editVrModel')->name('editVrModel')->middleware('auth');
	Route::post('/editVrModel', 'ModelContentController@editVrModelSave')->name('editVrModelSave')->middleware('auth');

	Route::post('/updateTb', 'ModelContentController@updateTbLink')->name('updateTbLink')->middleware('auth');
	//创建360图片
	Route::get('/add360', 'ModelContentController@add360')->name('add360')->middleware('auth');
	Route::post('/upload360', 'ModelContentController@upload360')->name('upload360')->middleware('auth');
	Route::post('/save360', 'ModelContentController@save360')->name('save360')->middleware('auth');


	//作品点赞
	Route::post('/models/favorite', 'ModelContentController@favorite')->name('favorite')->middleware('auth');
	Route::post('/models/cfavorite', 'ModelContentController@cancelfavorite')->name('cancelfavorite')->middleware('auth');
	//作品收藏
	Route::post('/models/collect', 'ModelContentController@collect')->name('collect')->middleware('auth');
	Route::post('/models/cancelCollect', 'ModelContentController@cancelCollect')->name('cancelCollect')->middleware('auth');

	//评论作品
	Route::post('/models/postRemark', 'ModelContentController@postRemark')->name('postRemark')->middleware('auth');


	Route::get('/models/edit/{id}', 'ModelContentController@modelsEditAjax')->name('modelsEditAjax')->middleware('auth');
	Route::post('/models/edit', 'ModelContentController@postModelsEditAjax')->name('postModelsEditAjax')->middleware('auth');

    //获取时间差
    Route::get('/models/DateTimeDiff', 'ModelContentController@DateTimeDiff')->name('DateTimeDiff')->middleware('auth');

	Route::post('/models/editPrivate', 'ModelContentController@editPrivate')->name('editPrivate')->middleware('auth');


	//获取作品作者的相关信息

	Route::post('/models/getModelsUserInfo', 'ModelContentController@getModelsUserInfo')->name('getModelsUserInfo');
    //社会化分享
    Route::get('/models/getShare/{id}', 'ModelContentController@getShare')->name('getShare');

    //微信手机版登录
    Route::get('/models/weiXin', 'ModelContentController@weiXin')->name('weiXin');

    Route::get('teamWordDownload',function(){
        return response()->download(
            realpath(base_path('public/down')).'/team.docx',
            '协同子账号操作使用文档.docx'
        );
    });
