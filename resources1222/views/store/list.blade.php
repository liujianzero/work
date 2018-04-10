@foreach ($list as $v)
    <div class="col-md-4 col-sm-6 col-xs-6">
        <div class="thumbnail">
            <a href="{{ route('shop.admin', ['id' => $v->id]) }}" class="block">
                <div class="ele-head clearfix">
                    <h4 class="h4-1">{{ App\Modules\User\Http\Controllers\MyShopController::cutStr($v->userDetail->nickname, 8) }}</h4>
                    <h4 class="h4-2"><small>{{ $v->storeType->name }}</small></h4>
                </div>
                <p class="p1">主体信息：已认证，请完善信息</p>
                <p class="p2">店铺状态：<span>已过期</span></p>
                <p class="p3">有效期至：<span>2017年10月5日</span></p>
            </a>
            <div class="ele-set clearfix">
                <span class="left"><span class="store-set">账号设置</span> <i class="fa fa-question-circle" data-html="true" data-toggle="tooltip" data-placement="top" title="店铺账号：{{ $v->name }}<br/>初始密码：123456"></i></span>
                <span class="right">
                    <a href="javascript:void(0);" class="info">修改</a>
                    <a href="javascript:void(0);" class="danger">删除</a>
				</span>
            </div>
        </div>
    </div>
@endforeach
