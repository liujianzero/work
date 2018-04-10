<div class="container" id="task-pay">
    <form class="form-horizontal" id="payFrom" action="{{ route('agent.common.payment') }}" method="post" target="_blank">
        <div class="form-group">
            <label for="balance" class="col-sm-offset-2 col-sm-2 control-label">我的资产</label>
            <div class="col-sm-8">
                <p class="form-control-static" id="balance">￥{{ $balance or '0.00' }}</p>
            </div>
        </div>
        <div class="form-group">
            <label for="cash" class="col-sm-offset-2 col-sm-2 control-label">充值金额</label>
            <div class="col-sm-5">
                <div class="input-group" data-toggle="tooltip" data-placement="top" title="我们会在您提交后处理您的充值">
                    <span class="input-group-addon" id="basic-addon1">￥</span>
                    <input type="text" class="form-control" id="cash" name="cash" aria-describedby="basic-addon1">
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <label class="radio-inline">
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
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-8">
                <button type="submit" class="btn btn-default btn-block" id="paySubmit" disabled>请选择支付方式</button>
            </div>
        </div>
        <input type="hidden" name="buy_type" value="recharge"/>
        {{ csrf_field() }}
    </form>
</div>
