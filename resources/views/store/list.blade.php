@foreach ($list as $v)
    <div class="col-md-4 col-sm-6 col-xs-6" id="store-{{ $v->id }}">
        <div class="thumbnail">
            <a href="{{ route('shop.admin', ['id' => $v->id]) }}" class="block">
                <div class="ele-head clearfix">
                    <h4 class="h4-1">{{ cut_str($v->store_name, 8) }}</h4>
                    <h4 class="h4-2"><small>{{ $v->store_type_name }}</small></h4>
                </div>
                <p class="p1">
                    主体信息：
                    @if ($v->auth_status == 1)
                        未认证
                    @elseif ($v->auth_status == 2)
                        认证中
                    @elseif ($v->auth_status == 3)
                        已认证
                    @elseif ($v->auth_status == 4)
                        认证失败
                    @endif
                </p>
                <p class="p2">
                    店铺状态：
                    @if ($v->store_status == 'on' && $v->open_status == 'on' && $v->expire_at >= $time)
                        <span>正常</span>
                    @else
                        <span>关闭</span>
                    @endif
                </p>
                <p class="p3">
                    有效期至：
                    <span>{{ $v->expire_at or '已过期' }}</span>
                </p>
            </a>
            <div class="ele-set clearfix">
                <span class="left"><span class="store-set" onclick="window.location.href='{{ route('shop.admin', ['id' => $v->id]) }}?edit=1'">账号设置</span> <i class="fa fa-question-circle" data-html="true" data-container="body" data-toggle="popover" data-placement="top" data-content="店铺账号：{{ $v->name }}<br/>初始密码：123456"></i></span>
                <span class="right">
                    <a class="info" data-id="{{ $v->id }}" data-name="{{ $v->store_name }}" onclick="storeRenew(this);">续费</a>
                    <a href="{{ route('shop.admin', ['id' => $v->id]) }}?edit=1" class="info">修改</a>
                    @if ($v->auth_status != 3)
                        <a href="javascript:void(0);" class="danger" data-id="{{ $v->id }}" data-name="{{ $v->store_name }}" onclick="delShop(this);">删除</a>
                    @endif
				</span>
            </div>
        </div>
    </div>
@endforeach
