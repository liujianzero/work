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
    @if ($count)
        <form class="form-horizontal" id="cashFrom">
            <div class="form-group">
                <label for="balance" class="col-sm-3 control-label">我的资产</label>
                <div class="col-sm-7">
                    <p class="form-control-static">￥{{ $balance or '0.00' }}</p>
                </div>
            </div>
            <div class="form-group">
                <label for="cash" class="col-sm-3 control-label"><span class="need">*</span>提现金额</label>
                <div class="col-sm-7">
                    <div class="input-group">
                        <span class="input-group-addon" id="basic-addon1">￥</span>
                        <input type="text" class="form-control" id="cash" name="cash">
                    </div>
                    <span class="input-tips">
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        今日已提现 {{ $sum }} 元，当前可提现 {{ $max_cash }} 元
                        <button type="button" class="btn btn-link" data-sum="{{ $max_cash }}" onclick="cashAll(this);">全部体现</button>
                    </span>
                </div>
            </div>
            <hr/>

            @if (count($ali_list))
                <div class="ali-div">
                    <div class="form-group">
                        <label for="cash2ali" class="col-sm-3 control-label">
                            <span class="label label-primary">提现至支付宝</span>
                        </label>
                        @if (count($bank_list))
                            <div class="col-sm-7">
                                <button type="button" class="btn btn-warning btn-sm" data-type="bank" onclick="switchType(this);"><i class="fa fa-toggle-on" aria-hidden="true"></i> 切换至银行卡</button>
                            </div>
                        @endif
                    </div>
                    <div class="form-group">
                        @foreach ($ali_list as $v)
                            <div class="col-sm-4 text-center">
                                <label class="radio-inline">
                                    <input type="radio" name="account" value="{{ $v->id }}"/>
                                    <span class="withdrawals-item">
                                        <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/property/images/ali-list.jpg') }}" />
                                        <hr/>
                                        <span>{{ star_replace($v->alipay_account, 3, 4) }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                        <div class="col-sm-offset-1 col-sm-11 error-content">

                        </div>
                    </div>
                </div>
            @endif

            @if (count($bank_list))
                <div class="bank-div" style="display: @if (count($ali_list)){{ 'none' }}@else{{ 'block' }}@endif">
                    <div class="form-group">
                        <label for="cash2ali" class="col-sm-3 control-label">
                            <span class="label label-warning">提现至银行卡</span>
                        </label>
                        @if (count($ali_list))
                            <div class="col-sm-7">
                                <button type="button" class="btn btn-primary btn-sm" data-type="ali" onclick="switchType(this);"><i class="fa fa-toggle-on" aria-hidden="true"></i> 切换至支付宝</button>
                            </div>
                        @endif
                    </div>
                    <div class="form-group" id="viewport">
                        @foreach ($bank_list as $v)
                            <div class="col-sm-4 text-center">
                                <label class="radio-inline">
                                    <input type="radio" name="account" value="{{ $v->id }}"/>
                                    <span class="withdrawals-item">
                                        <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . "/property/images/bank/{$bank[$v->bank_name]}.jpg") }}" />
                                        <hr/>
                                        <span>{{ star_replace($v->bank_account, 4, 10) }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                        <div class="col-sm-offset-1 col-sm-11 error-content">

                        </div>
                    </div>
                </div>
            @endif

            <input type="hidden" name="action" value="apply"/>
            <input type="hidden" id="type" name="type" value="@if (count($ali_list)){{ 'ali' }}@else{{ 'bank' }}@endif"/>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-7">
                    <button type="submit" class="btn btn-default" id="cashSubmit">下一步</button>
                </div>
            </div>
        </form>
    @else
        <div class="row">
            <div class="col-sm-12">
                <div class="bs-callout bs-callout-danger text-center">
                    <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/goods/images/nomessage.png') }}"/>
                    <h4>请先进行支付认证</h4>
                </div>
            </div>
        </div>
    @endif
</div>
