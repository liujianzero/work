<div class="container" id="task-pay">
    <div class="alert alert-warning alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <strong><i class="fa fa-lightbulb-o" aria-hidden="true"></i> 友情提示：</strong>以下账户信息以您提交的信息为准，非本站金融体系，请妥善填写，如出现信息误差，自行负责。
    </div>
    <div class="row">
        <div class="col-sm-12">
            <ul class="wizard-steps">
                <li class="active" data-target="#step1">
                    <span class="step">1</span>
                    <span class="title">填写信息</span>
                </li>
                <li class="@if($info->status >= 0) active @endif" data-target="#step2">
                    <span class="step">2</span>
                    <span class="title">打款中</span>
                </li>
                <li class="@if($info->status >= 1) active @endif" data-target="#step3">
                    <span class="step">3</span>
                    <span class="title">填写打入卡内金额</span>
                </li>
                <li class="@if($info->status >= 2) active @endif" data-target="#step4">
                    <span class="step">4</span>
                    <span class="title">
                        @if ($info->status == 3)
                            认证失败
                        @else
                            认证成功
                        @endif
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <form class="form-horizontal" id="bankFrom">
        @if ($info->status == -1)
            <div class="form-group">
                <label for="bank_name" class="col-sm-3 control-label"><span class="need">*</span>银行名称</label>
                <div class="col-sm-6">
                    <select class="form-control" id="bank_name" name="bank_name">
                        <option value="">请选择</option>
                        @foreach ($bank as $k => $v)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    <span class="input-tips"><i class="fa fa-info-circle" aria-hidden="true"></i> 仅支持该行储蓄卡，不支持信用卡和存折</span>
                </div>
            </div>
            <div class="form-group">
                <label for="realname" class="col-sm-3 control-label"><span class="need">*</span>开户人</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="realname" name="realname"/>
                    <span class="input-tips"><i class="fa fa-info-circle" aria-hidden="true"></i> 银行开户人真实姓名</span>
                </div>
            </div>
            <div class="form-group">
                <label for="deposit_name" class="col-sm-3 control-label"><span class="need">*</span>开户行支行</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="deposit_name" name="deposit_name"/>
                </div>
            </div>
            <div class="form-group">
                <label for="province&city&area" class="col-sm-3 control-label"><span class="need">*</span>开户行地区</label>
                <div class="col-sm-2">
                    <label for="province" class="sr-only">省份</label>
                    <select class="form-control" id="province" name="province" onchange="getCity(this);">
                        <option value="">请选择</option>
                        @foreach ($province as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="city" class="sr-only">城市</label>
                    <select class="form-control" id="city" name="city" onchange="getArea(this);">
                        <option value="">请选择</option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="area" class="sr-only">地区</label>
                    <select class="form-control" id="area" name="area">
                        <option value="">请选择</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="bank_account" class="col-sm-3 control-label"><span class="need">*</span>银行卡号</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="bank_account" name="bank_account"/>
                </div>
            </div>
            <div class="form-group">
                <label for="bank_account_confirmation" class="col-sm-3 control-label"><span class="need">*</span>确认银行卡号</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="bank_account_confirmation" name="bank_account_confirmation"/>
                </div>
            </div>
            <input type="hidden" name="type" value="auth"/>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary" id="bankSubmit">立即申请</button>
                </div>
            </div>
        @else
            <div class="form-group">
                <div class="col-sm-12 text-center">
                    <p class="form-control-static text-center size" style="color: #737373;">
                        @if ($info->status == 0)
                            <i class="fa fa-hourglass-1" aria-hidden="true"></i>
                            正在审核中，我们会尽快为您的卡号安排打款
                        @elseif ($info->status == 1)
                            <i class="fa fa-hourglass-2" aria-hidden="true"></i>
                            已经向您的卡号中支付了一笔款项，请输入正确的打款金额
                        @elseif ($info->status == 2)
                            <i class="fa fa-smile-o" aria-hidden="true"></i>
                            恭喜，您已经通过了绑定认证
                        @elseif ($info->status == 3)
                            <i class="fa fa-frown-o" aria-hidden="true"></i>
                            很遗憾，绑定失败
                        @endif
                    </p>
                </div>
            </div>
            @if($info->status == 1)
                <div class="form-group">
                    <label for="user_get_cash" class="col-sm-3 control-label"><span class="need">*</span>打款金额</label>
                    <div class="col-sm-7">
                        <div class="input-group">
                            <span class="input-group-addon" id="basic-addon1">￥</span>
                            <input type="text" class="form-control" id="user_get_cash" name="user_get_cash"/>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="type" value="cash"/>
                <input type="hidden" name="id" value="{{ $info->id }}"/>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-7">
                        <button type="submit" class="btn btn-primary btn-block" id="bankSubmit">确认</button>
                    </div>
                </div>
            @endif
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-8">
                    <table class="table table-hover">
                        <tr>
                            <td colspan="2"><b>您的卡号信息 <i class="fa fa-angle-double-right" aria-hidden="true" onclick="showInfo(this);" style="cursor: pointer;"></i></b></td>
                        </tr>
                        <tr style="display: none;">
                            <td>申请时间</td>
                            <td>{{ $info->created_at }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>开户人</td>
                            <td>{{ $info->realname }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>开户银行</td>
                            <td>{{ $info->bank_name }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>开户行支行</td>
                            <td>{{ $info->deposit_name }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>开户行地区</td>
                            <td>{{ $info->deposit_area }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>银行卡号</td>
                            <td>{{ star_replace($info->bank_account, 4, 10) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif
    </form>
</div>
