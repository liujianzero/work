{{--修改信息--}}
<div id="newcustomer" class="updated">
    <div class="pull-center">
        <form id="updatedFrom" action="/customer/edit/upd" class="sca-from" method="post" data-url="{!! Theme::get('dir_prefix') !!}">
            <div class="pull-main clearfix">
                <div class="pull-l col-md-3"><label for=""><i style="color: #ff0000;">*</i>&nbsp;客户身份:</label></div>
                <div class="pull-r col-md-9">
                    @foreach ($vip as $v)
                        <label class="radio-inline">
                            <input type="radio" name="vip" value="{{ $v['val'] }}" @if ($info->vip == $v['val']) checked @endif> {{ $v['name'] }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="pull-main form-group clearfix">
                <label for="" class="col-md-3 tar control-label">手机号:</label>
                <div class="col-md-9">
                    <input type="text" name="mobile" maxlength="11" class="form-control" value="{{ $info->mobile }}"/>
                </div>
            </div>
            <div class="pull-main form-group clearfix">
                <label for="" class="col-md-3 tar control-label">姓名:</label>
                <div class="col-md-9">
                    <input type="text" name="name" class="form-control" value="{{ $info->name }}" />
                </div>
            </div>
            <div class="pull-main form-group clearfix">
                <label for="" class="col-md-3 tar control-label">微信号:</label>
                <div class="col-md-9">
                    <input type="text" name="WeChat" class="form-control" value="{{ $info->wechat }}"/>
                </div>
            </div>
            <div class="pull-main form-group clearfix">
                <label for="" class="col-md-3 tar control-label">备注:</label>
                <div class="col-md-9">
                    <textarea class="form-control resize" name="remark" maxlength="200" placeholder="备注不要超过200字">{{ $info->remark }}</textarea>
                </div>
            </div>
            <div class="pull-main form-group clearfix">
                <label for="" class="col-md-3 tar control-label"></label>
                <div class="col-md-9">
                    <button id="updatedSubmit" class="btn btn-primary">提交</button>
                </div>
            </div>
            {!! csrf_field() !!}
        </form>
    </div>
</div>

{!! Theme::asset()->container('common-js')->usePath()->add('customer-js-customer', Theme::get('dir_prefix') . '/customer/js/customer.js') !!}