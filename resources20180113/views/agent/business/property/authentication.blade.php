<div class="container" id="task-pay">
    <div class="alert alert-warning alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <strong><i class="fa fa-lightbulb-o" aria-hidden="true"></i> 友情提示：</strong>以下账户信息以您提交的信息为准，非本站金融体系，请妥善填写，如出现信息误差，自行负责。
    </div>
    <div class="bs-callout bs-callout-warning row">
        <div class="col-sm-2">
            <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/property/images/bank-auth.jpg') }}" class="img-circle">
        </div>
        <div class="col-sm-8">
            <h4>银行卡绑定</h4>
            <p class="intro-p">平台会向银行卡打入一定的金额，输入该金额进行绑定</p>
        </div>
        <div class="col-sm-2">
            @if ($bankAuth)
                <button type="button" class="btn btn-info" onclick="bankList(this);">查看绑定</button>
            @else
                <button type="button" class="btn btn-warning" data-id="0" onclick="bindBank(this);">立即绑定</button>
            @endif
        </div>
    </div>
    <div class="bs-callout bs-callout-primary row">
        <div class="col-sm-2">
            <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/property/images/ali-auth.jpg') }}" class="img-circle">
        </div>
        <div class="col-sm-8">
            <h4>
                支付宝绑定
            </h4>
            <p class="intro-p">支付宝认证会在一个工作日内完成</p>
        </div>
        <div class="col-sm-2">
            @if ($aliAuth)
                <button type="button" class="btn btn-info" onclick="aliList(this);">查看绑定</button>
            @else
                <button type="button" class="btn btn-primary" data-id="0" onclick="bindAli(this);">立即绑定</button>
            @endif
        </div>
    </div>
</div>
