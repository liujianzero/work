<div class="container" id="task-pay">
    <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="withdrawals-standard">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            提现手续费标准
            <i class="fa fa-question-circle" aria-hidden="true"></i>
        </div>
        <div style="display: none;">
            <p>A. 200 元以下（含 200 元）单笔收费 {{ $cash_rule['per_low'] }} 元</p>
            <p>B. 200 元以上单笔收费 {{ $cash_rule['per_charge'] }}%，最高收费 {{ $cash_rule['per_high'] }} 元</p>
            <p>C. 单次最低提现金额 {{ $cash_rule['withdraw_min'] }} 元</p>
            <p>D. 当日提现最大金额 {{ $cash_rule['withdraw_max'] }} 元</p>
        </div>
    </div>
    <form class="form-horizontal" id="confirmFrom">
        @if ($cash_out_type == 'bank')
            <div class="form-group">
                <label for="account" class="col-sm-3 control-label">账户类型</label>
                <div class="col-sm-7">
                    <p class="form-control-static">银行卡</p>
                </div>
            </div>
            <div class="form-group">
                <label for="realname" class="col-sm-3 control-label">开户人</label>
                <div class="col-sm-7">
                    <p class="form-control-static">{{ $account->realname }}</p>
                </div>
            </div>
            <div class="form-group">
                <label for="info" class="col-sm-3 control-label">账号信息</label>
                <div class="col-sm-4 text-center">
                    <div class="bs-callout bs-callout-warning" style="margin: 8px 0;">
                        <div>
                            <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . "/property/images/bank/{$bank[$account->bank_name]}.jpg") }}" />
                            <hr/>
                            <h4>{{ star_replace($account->bank_account, 4, 10) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="form-group">
                <label for="account" class="col-sm-3 control-label">账户类型</label>
                <div class="col-sm-7">
                    <p class="form-control-static">支付宝</p>
                </div>
            </div>
            <div class="form-group">
                <label for="alipay_name" class="col-sm-3 control-label">支付宝姓名</label>
                <div class="col-sm-7">
                    <p class="form-control-static">{{ $account->alipay_name }}</p>
                </div>
            </div>
            <div class="form-group">
                <label for="info" class="col-sm-3 control-label">账号信息</label>
                <div class="col-sm-4 text-center">
                    <div class="bs-callout bs-callout-primary" style="margin: 8px 0;">
                        <div>
                            <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/property/images/ali-list.jpg') }}" class="img-circle">
                            <hr/>
                            <h4>{{ star_replace($account->alipay_account, 3, 4) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="form-group">
            <label for="cash" class="col-sm-3 control-label">提现金额</label>
            <div class="col-sm-7">
                <p class="form-control-static">￥{{ $cash }}</p>
            </div>
        </div>
        <div class="form-group">
            <label for="fees" class="col-sm-3 control-label">服务费</label>
            <div class="col-sm-7">
                <p class="form-control-static">￥{{ $fees }}</p>
            </div>
        </div>
        <div class="form-group">
            <label for="realcash" class="col-sm-3 control-label">到账金额</label>
            <div class="col-sm-7">
                <p class="form-control-static">￥{{ price_format($cash - $fees) }}</p>
            </div>
        </div>
        <div class="form-group">
            <label for="alternate_password" class="col-sm-3 control-label">支付密码</label>
            <div class="col-sm-7">
                <div class="input-group" data-toggle="tooltip" data-placement="top" title="初始支付密码为为您的登录密码">
                    <span class="input-group-addon" id="basic-addon1"><i class="fa fa-key" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" id="alternate_password" name="alternate_password" aria-describedby="basic-addon1"/>
                </div>
            </div>
        </div>
        <input type="hidden" name="action" value="confirm"/>
        <input type="hidden" name="cash" value="{{ $cash }}"/>
        <input type="hidden" name="type" value="{{ $cash_out_type }}"/>
        <input type="hidden" name="account" value="{{ $account->id }}"/>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-7">
                <button type="submit" class="btn btn-primary btn-block" id="confirmSubmit">立即提现</button>
            </div>
        </div>
    </form>
</div>
