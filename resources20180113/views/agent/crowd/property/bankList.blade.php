<div class="container" id="task-pay">
    <div class="alert alert-info alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <strong><i class="fa fa-lightbulb-o" aria-hidden="true"></i></strong> 您已绑定 <span class="count">{{ $count }}</span> 张银行卡，最多绑定 {{ $times }} 张卡号；如果认证失败，请删除后重新认证。
    </div>
    <div class="row">
        @foreach ($list as $v)
            <div class="col-sm-4 text-center">
                <div class="bs-callout bs-callout-warning list-item">
                    <div>
                        <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . "/property/images/bank/{$bank[$v->bank_name]}.jpg") }}" />
                        <hr/>
                        <h4>
                            @if ($v->status == 0)
                                正在审核中...
                            @elseif ($v->status == 1)
                                正在验证中..
                            @elseif ($v->status == 2)
                                {{ star_replace($v->bank_account, 4, 10) }}
                            @elseif ($v->status == 3)
                                认证失败
                            @endif
                        </h4>
                    </div>
                    <a>
                        <button type="button" class="btn btn-primary btn-xs" data-id="{{ $v->id }}" onclick="bindBank(this);"><i class="fa fa-tv" aria-hidden="true"></i> 查看</button>
                        <button type="button" class="btn btn-danger btn-xs" data-id="{{ $v->id }}" onclick="delBank(this);"><i class="fa fa-trash-o" aria-hidden="true"></i> 删除</button>
                    </a>
                </div>
            </div>
        @endforeach
        <div class="col-sm-4 text-center add-bank" style="display: @if ($count < $times){{ 'block' }}@else{{ 'none' }}@endif">
            <div class="bs-callout bs-callout-warning">
                <a href="javascript:void(0);" data-id="0" onclick="bindBank(this);">
                    <h4 style="height: 31px;"><i class="fa fa-plus" aria-hidden="true"></i></h4>
                    <hr/>
                    <p class="intro-p">添加银行卡</p>
                </a>
            </div>
        </div>
    </div>
</div>
