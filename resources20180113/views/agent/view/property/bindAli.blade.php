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
                    <span class="title">填写打入账号金额</span>
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

    <form class="form-horizontal" id="aliFrom">
        @if ($info->status == -1)
            <div class="form-group">
                <label for="realname" class="col-sm-3 control-label"><span class="need">*</span>真实姓名</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="realname" name="realname"/>
                </div>
            </div>
            <div class="form-group">
                <label for="alipay_name" class="col-sm-3 control-label"><span class="need">*</span>支付宝姓名</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="alipay_name" name="alipay_name"/>
                </div>
            </div>
            <div class="form-group">
                <label for="alipay_account" class="col-sm-3 control-label"><span class="need">*</span>支付宝账号</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="alipay_account" name="alipay_account"/>
                </div>
            </div>
            <div class="form-group">
                <label for="alipay_account_confirmation" class="col-sm-3 control-label"><span class="need">*</span>确认支付宝账号</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="alipay_account_confirmation" name="alipay_account_confirmation"/>
                </div>
            </div>
            <input type="hidden" name="type" value="auth"/>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-7">
                    <button type="submit" class="btn btn-primary" id="aliSubmit">立即申请</button>
                </div>
            </div>
        @else
            <div class="form-group">
                <div class="col-sm-12 text-center">
                    <p class="form-control-static text-center size" style="color: #737373;">
                        @if ($info->status == 0)
                            <i class="fa fa-hourglass-1" aria-hidden="true"></i>
                            正在审核中，我们会尽快为您的账户安排打款
                        @elseif ($info->status == 1)
                            <i class="fa fa-hourglass-2" aria-hidden="true"></i>
                            已经向您的账户中支付了一笔款项，请输入正确的打款金额
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
            @if ($info->status == 1)
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
                        <button type="submit" class="btn btn-primary btn-block" id="aliSubmit">确认</button>
                    </div>
                </div>
            @endif
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-8">
                    <table class="table table-hover">
                        <tr>
                            <td colspan="2"><b>您的账号信息 <i class="fa fa-angle-double-right" aria-hidden="true" onclick="showInfo(this);" style="cursor: pointer;"></i></b></td>
                        </tr>
                        <tr style="display: none;">
                            <td>申请时间</td>
                            <td>{{ $info->created_at }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>真实姓名</td>
                            <td>{{ $info->realname }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>支付宝姓名</td>
                            <td>{{ $info->alipay_name }}</td>
                        </tr>
                        <tr style="display: none;">
                            <td>支付宝账户</td>
                            <td>{{ star_replace($info->alipay_account, 3, 4) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif
    </form>
</div>
