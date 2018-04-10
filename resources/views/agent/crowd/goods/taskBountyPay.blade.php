<div class="container" id="task-pay">
    <form class="form-horizontal" id="payFrom" action="{{ route('agent.common.payment') }}" method="post" target="_blank">
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-8">
                <p class="form-control-static text-center size">请您确认支付信息并选择支付方式</p>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-8">
                <table class="table table-hover">
                    <tr>
                        <td>任务标题</td>
                        <td>{{ cut_str($info->title, 20) }}</td>
                    </tr>
                    @if ($type == 'merge')
                        <tr>
                            <td>任务赏金</td>
                            <td>￥{{ $info->bounty }}</td>
                        </tr>
                        <tr>
                            <td>增值服务</td>
                            <td>￥{{ $info->service }}</td>
                        </tr>
                    @elseif ($type == 'bounty')
                        <tr>
                            <td>任务赏金</td>
                            <td>￥{{ $info->bounty }}</td>
                        </tr>
                    @elseif ($type == 'server')
                        <tr>
                            <td>增值服务</td>
                            <td>￥{{ $info->service }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="text-right">总计：￥{{ $total }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-12 text-center">
                @if ($balance_status)
                    <label class="radio-inline cancel-padding">
                        <input type="radio" class="set-margin" name="pay_type" value="balance">
                        <span class="pay-border" style="padding: 10px 25px;">
                        <img src="{!! Theme::asset()->url(Theme::get('module') . '/payment/images/pay/balance.png') !!}"/>
                    </span>
                    </label>
                @endif
                <label class="radio-inline @if (! $balance_status) cancel-padding @endif">
                    <input type="radio" class="set-margin" name="pay_type" value="ali">
                    <span class="pay-border">
                        <img src="{!! Theme::asset()->url(Theme::get('module') . '/payment/images/pay/ali.png') !!}"/>
                    </span>
                </label>
                <label class="radio-inline">
                    <input type="radio" class="set-margin" name="pay_type" value="wechat">
                    <span class="pay-border">
                        <img src="{!! Theme::asset()->url(Theme::get('module') . '/payment/images/pay/wechat.png') !!}"/>
                    </span>
                </label>
            </div>
        </div>
        @if ($balance_status)
            <div class="form-group" id="password-input" style="display: none;">
                <label for="alternate_password" class="col-sm-offset-2 col-sm-2 control-label">支付密码</label>
                <div class="col-sm-5">
                    <div class="input-group" data-toggle="tooltip" data-placement="top" title="初始支付密码为为您的登录密码">
                        <span class="input-group-addon" id="basic-addon1"><i class="fa fa-key" aria-hidden="true"></i></span>
                        <input type="password" class="form-control" id="alternate_password" name="alternate_password" aria-describedby="basic-addon1"/>
                    </div>
                </div>
            </div>
        @endif
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-8">
                <button type="submit" class="btn btn-default btn-block" id="paySubmit" disabled>请选择支付方式</button>
            </div>
        </div>
        <input type="hidden" name="action_id" value="{{ $info->id }}"/>
        <input type="hidden" name="buy_type" value="task_{{ $type }}"/>
        <input type="hidden" name="cash" value="{{ $total }}"/>
        {{ csrf_field() }}
    </form>
</div>
