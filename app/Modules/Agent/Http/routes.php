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

    // 前台-自定义页面
    Route::get('/page/{store?}/{page?}','PagesController@show')->name('agent.pages.show');// 展示自定义页面
    Route::post('/shop/editPic','PagesController@editPic')->name('agent.shop.editPic');// 博物馆-更换全景图背景
    Route::post('/shop/select','PagesController@select')->name('agent.shop.select');// 博物馆-选择模板
    Route::post('/page/comment','PagesController@comment')->name('agent.pages.comment');// 评论
    Route::post('/page/answer','PagesController@answer')->name('agent.pages.answer');// 答题
//    Route::post('/page/get_goods_number','PagesController@get_goods_number')->name('agent.pages.get_goods_number');// 搭配方案
    //电子商务模块
    Route::post('/page/goods_buy', 'PagesController@goods_buy')->name('agent.pages.goods_buy');// 商品-立即购买
    Route::post('/page/addCart', 'PagesController@addCart')->name('agent.pages.addCart');// 商品-加入购物车
    Route::post('/page/delCart/{id}', 'PagesController@delCart')->name('agent.pages.delCart');// 购物车-删除商品
    Route::post('/page/change_number', 'PagesController@change_number')->name('agent.pages.change_number');// 购物车-更新商品的数量
    Route::post('/page/get_address', 'PagesController@get_address')->name('agent.pages.get_address');// 购物车-更新商品的数量

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

    // stateless-公用类接口
    Route::group([
        'as' => 'agent.stateless.',
        'prefix' => 'stateless',
    ], function () {
        Route::get('/ali/pay/return','StatelessController@aliPayReturn')->name('ali.pay.return');// 支付宝同步回调
    });

    // ajax-公用类接口
    Route::group([
        'as' => 'agent.ajax.',
        'prefix' => 'ajax',
        'middleware' => 'agent.admin'
    ], function () {
        Route::post('/region/{id}','AjaxController@getRegion')->name('region');// 获取地区数据
        Route::post('/task/bounty/limit','AjaxController@getTaskBountyLimit')->name('task.bounty.limit');// 任务最大/最小金额
        Route::post('/weChat/pay/query','AjaxController@weChatPayStatus')->name('weChat.pay.query');// 查询微信扫码支付状态
    });

    // common-公用类接口
    Route::group([
        'as' => 'agent.common.',
        'prefix' => 'common',
        'middleware' => 'agent.admin'
    ], function () {
        Route::post('/attachment/download/{id}','CommonController@attachmentDownload')->name('attachment.download');// 下载附件
        Route::post('/payment','CommonController@payment')->name('payment');// 统一支付
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
            Route::get('/shop/buyTemplate','ShopController@buyTemplate')->name('shop.buy.template');// 店铺-选择模板
            Route::get('/shop/customTemplate','ShopController@customTemplate')->name('shop.customTemplate');// 店铺-自定义模板
            Route::any('/shop/receiveCustomData','ShopController@receiveCustomData')->name('shop.receiveCustomData');// 店铺-自定义模板
            Route::post('/shop/save/template','ShopController@save')->name('shop.save.template');// 店铺-保存发布模板
            Route::post('/shop/page/data','ShopController@page')->name('shop.page.data');// 店铺-获取自定义页面数据
            Route::post('/shop/page/upload', 'ShopController@storeUpload')->name('shop.page.upload');//简介页上传@ajax
            Route::post('/shop/page/del/{id}','ShopController@storeDelFile')->name('shop.page.del');// 简介页删除文件@ajax
            Route::post('/shop/validate/from','ShopController@validateFrom')->name('shop.validate.from');// 页面设置表单验证@ajax
            Route::post('/shop/get/model','ShopController@getModel')->name('shop.get.model');// 店铺-根据关键字获取作品
            Route::post('/shop/upload/image','ShopController@uploadImage')->name('shop.upload.image');// 店铺-上传图片
            Route::post('/shop/del/image','ShopController@delImage')->name('shop.del.image');// 店铺-删除图片
            Route::post('/shop/get/subject','ShopController@getSubject')->name('shop.get.subject');// 店铺-获取店铺题目列表
            Route::post('/shop/handle/subject','ShopController@handleSubject')->name('shop.handle.subject');// 店铺-新增/编辑题目页面
            Route::post('/shop/subject/store','ShopController@subjectStore')->name('shop.subject.store');// 店铺-新增题目
            Route::post('/shop/subject/update','ShopController@subjectUpdate')->name('shop.subject.update');// 店铺-编辑题目
            Route::post('/shop/subject/destroy','ShopController@subjectDestroy')->name('shop.subject.destroy');// 店铺-删除题目
            Route::post('/shop/subject/select','ShopController@subjectSelect')->name('shop.subject.select');// 店铺-获取可以选择的题目列表
            Route::post('/shop/subject/show','ShopController@subjectShow')->name('shop.subject.show');// 店铺-获取题目详情

            Route::get('/goods','GoodsController@index')->name('goods.index');// 商品-默认页
            Route::post('/goods/batch/cat','GoodsController@batchCat')->name('goods.batch.cat');// 商品-默认页-批量操作-改分组@ajax
            Route::post('/goods/batch/sale/off','GoodsController@batchOffSale')->name('goods.batch.sale.off');// 商品-默认页-批量操作-下架@ajax
            Route::post('/goods/batch/back','GoodsController@batchBack')->name('goods.batch.back');// 商品-默认页-批量操作-交还设计师@ajax
            Route::get('/goods/task/list','GoodsController@taskList')->name('goods.task.list');// 商品-任务列表
            Route::post('/goods/task/bounty/{id}','GoodsController@taskBounty')->name('goods.task.bounty');// 商品-托管赏金@ajax
            Route::post('/goods/task/issue','GoodsController@issueTask')->name('goods.task.issue');// 商品-任务列表-发布任务@ajax
            Route::post('/goods/task/create','GoodsController@issueTaskCreate')->name('goods.task.create');// 商品-任务列表-发布任务处理@ajax
            Route::post('/goods/task/del/{id}','GoodsController@taskDel')->name('goods.task.del');// 商品-任务列表-删除任务@ajax
            Route::post('/goods/task/edit/{id}','GoodsController@taskEdit')->name('goods.task.edit');// 商品-任务列表-编辑任务@ajax
            Route::post('/goods/task/update','GoodsController@taskUpdate')->name('goods.task.update');// 商品-任务列表-编辑任务处理@ajax
            Route::post('/goods/task/category/{id}','GoodsController@taskCategory')->name('goods.task.category');// 商品-任务列表-发布任务-获取分类@ajax
            Route::post('/goods/task/upload','GoodsController@taskUpload')->name('goods.task.upload');// 商品-任务列表-发布任务-上传文件@ajax
            Route::post('/goods/task/del/file/{id}','GoodsController@taskDelFile')->name('goods.task.del.file');// 商品-任务列表-发布任务-删除文件@ajax
            Route::post('/goods/task/check/bounty','GoodsController@checkBounty')->name('goods.task.check.bounty');// 商品-任务列表-发布任务-赏金验证@ajax
            Route::post('/goods/task/info/{id}','GoodsController@taskInfo')->name('goods.task.info');// 商品-任务列表-任务操作@ajax
            Route::post('/goods/task/page','GoodsController@ajaxPage')->name('goods.task.page');// 商品-任务列表-获取分页数据@ajax
            Route::post('/goods/task/win/bid','GoodsController@winBid')->name('goods.task.win.bid');// 商品-任务列表-任务中标@ajax
            Route::post('/goods/task/delivery/check','GoodsController@workCheck')->name('goods.task.delivery.check');// 商品-任务列表-稿件通过验收@ajax
            Route::post('/goods/task/comment/page','GoodsController@commentPage')->name('goods.task.comment.page');// 商品-任务列表-评价页面@ajax
            Route::post('/goods/task/comment','GoodsController@comment')->name('goods.task.comment');// 商品-任务列表-评价@ajax
            Route::post('/goods/cat/add','GoodsController@catStore')->name('goods.cat.add');// 商品-分组添加@ajax
            Route::post('/goods/folder/get','GoodsController@folderGet')->name('goods.folder.get');// 商品-获取设计师文件夹@ajax
            Route::post('/goods/models/get','GoodsController@modelsGet')->name('goods.models.get');// 商品-获取设计师某个文件夹下的模型@ajax
            Route::post('/goods/models/add/{id}','GoodsController@modelsAdd')->name('goods.models.add');// 商品-设计师移交模型给店家@ajax
            Route::get('/goods/edit/{id}','GoodsController@editGoods')->name('goods.edit');// 商品-编辑商品页面
            Route::post('/goods/update','GoodsController@updateGoods')->name('goods.update');// 商品-编辑商品处理
            Route::get('/goods/add','GoodsController@addGoods')->name('goods.add');// 商品-add
            Route::post('/goods/content/upload','GoodsController@uploadContentImages')->name('goods.content.upload');// 商品-编辑商品处理-上传商品描述的图片@ajax
            Route::post('/goods/type/get','GoodsController@getTypeList')->name('goods.type.get');// 商品-编辑商品处理-获取属性类型列表@ajax
            Route::post('/goods/type/add','GoodsController@addType')->name('goods.type.add');// 商品-编辑商品处理-添加属性类型@ajax
            Route::post('/goods/type/del','GoodsController@delType')->name('goods.type.del');// 商品-编辑商品处理-删除属性类型@ajax
            Route::post('/goods/type/edit','GoodsController@editType')->name('goods.type.edit');// 商品-编辑商品处理-编辑属性类型@ajax
            Route::post('/goods/attr/get','GoodsController@getAttrList')->name('goods.attr.get');// 商品-编辑商品处理-获取属性列表@ajax
            Route::post('/goods/attr/add/{id}','GoodsController@addAttr')->name('goods.attr.add');// 商品-编辑商品处理-添加属性页面@ajax
            Route::post('/goods/attr/create','GoodsController@createAttr')->name('goods.attr.create');// 商品-编辑商品处理-添加属性处理@ajax
            Route::post('/goods/attr/edit/{id}','GoodsController@editAttr')->name('goods.attr.edit');// 商品-编辑商品处理-编辑属性页面@ajax
            Route::post('/goods/attr/update','GoodsController@updateAttr')->name('goods.attr.update');// 商品-编辑商品处理-编辑属性处理@ajax
            Route::post('/goods/attr/del/{id}','GoodsController@delAttr')->name('goods.attr.del');// 商品-编辑商品处理-删除属性处理@ajax
            Route::post('/goods/attr/list','GoodsController@listAttr')->name('goods.attr.list');// 商品-编辑商品处理-获取商品属性@ajax
            Route::post('/goods/attr/table','GoodsController@tableAttr')->name('goods.attr.table');// 商品-编辑商品处理-获取商品属性库存表@ajax
            Route::get('/goods/distribution','GoodsController@distributionGoods')->name('goods.distribution');// 商品-分销商城

            Route::get('/order','OrderController@index')->name('order.index');// 订单-默认页
            Route::post('/order/{id}/delivery','OrderController@deliveryPage')->name('order.delivery.page');// 订单-发货页面@ajax
            Route::post('/order/delivery','OrderController@delivery')->name('order.delivery');// 订单-发货处理@ajax
            Route::post('/order/{id}/evaluate','OrderController@evaluatePage')->name('order.evaluate.page');// 订单-评价页面@ajax
            Route::get('/order/detail/{id}','OrderController@detail')->name('order.detail');// 订单详情页
            Route::post('/order/evaluate','OrderController@evaluate')->name('order.evaluate');// 订单-评价处理@ajax

            Route::get('/customer','CustomerController@index')->name('customer.index');// 客户-默认页
            Route::post('/customer/add','CustomerController@addCustomer')->name('customer.add');// 客户-添加客户处理@ajax
            Route::post('/customer/edit/page/{id}','CustomerController@editPage')->name('customer.edit.page');//客户-编辑客户界面@ajax
            Route::post('/customer/edit/update','CustomerController@update')->name('customer.edit.update');//客户-编辑客户处理@ajax
            Route::post('/customer/del/{id}','CustomerController@delete')->name('customer.del');//客户-删除客户@ajax

            Route::get('/data','DataController@index')->name('data.index');// 数据-默认页
            Route::post('/data/eCharts','DataController@eCharts')->name('data.eCharts');// 数据-数据图

            Route::get('/property','PropertyController@index')->name('property.index');// 资产-默认页
            Route::post('/property/recharge','PropertyController@recharge')->name('property.recharge');// 资产-充值@ajax
            Route::post('/property/authentication','PropertyController@authentication')->name('property.authentication');// 资产-支付认证@ajax
            Route::post('/property/bind/ali/{id}','PropertyController@bindAli')->name('property.bind.ali');// 资产-绑定支付宝@ajax
            Route::post('/property/bind/action/ali','PropertyController@aliAction')->name('property.ali.action');// 资产-绑定支付宝相关操作@ajax
            Route::post('/property/ali/list','PropertyController@aliList')->name('property.ali.list');// 资产-绑定支付宝列表@ajax
            Route::post('/property/ali/del/{id}','PropertyController@aliDel')->name('property.ali.del');// 资产-删除支付宝账号@ajax
            Route::post('/property/bind/bank/{id}','PropertyController@bindBank')->name('property.bind.bank');// 资产-绑定银行卡@ajax
            Route::post('/property/bind/action/bank','PropertyController@bankAction')->name('property.bank.action');// 资产-绑定银行卡相关操作@ajax
            Route::post('/property/bank/list','PropertyController@bankList')->name('property.bank.list');// 资产-绑定银行卡列表@ajax
            Route::post('/property/bank/del/{id}','PropertyController@bankDel')->name('property.bank.del');// 资产-删除银行卡@ajax
            Route::post('/property/withdrawals','PropertyController@withdrawals')->name('property.withdrawals');// 资产-提现@ajax
            Route::post('/property/cash','PropertyController@cash')->name('property.cash');// 资产-提现操作@ajax

            Route::get('/marketing','MarketingController@index')->name('marketing.index');// 营销-默认页

            Route::get('/setup','SetupController@index')->name('setup.index');// 设置-默认页
            Route::post('/setup/edit','SetupController@edit')->name('setup.edit');// 设置-保存设置信息
            Route::post('/setup/edit/upd','SetupController@update')->name('setup.edit.upd');// 设置-保存店名@ajax
            Route::post('/setup/webUpl','SetupController@webUpload')->name('setup.webUpl');// 设置-上传LOGO@ajax
            Route::post('/setup/update/password','SetupController@password')->name('setup.password');// 设置-修改登录/支付密码@ajax
            Route::get('/setup/authorize/{action}','SetupController@auth')->name('setup.authorize');// 设置-店铺认证
            Route::post('/setup/handle/authorize','SetupController@authHandle')->name('setup.authorize.handle');// 设置-店铺认证处理

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
