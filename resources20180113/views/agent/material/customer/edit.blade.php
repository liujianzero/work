<form class="form-horizontal" id="updateFrom" style="width: 95%;margin-top: 20px;">
    <div class="form-group">
        <label for="name" class="col-sm-3 control-label"><span class="need">*</span>姓名</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="name" name="name" value="{{ $info->name }}"/>
        </div>
    </div>
    <div class="form-group">
        <label for="vip" class="col-sm-3 control-label"><span class="need">*</span>客户身份</label>
        <div class="col-sm-8">
            @foreach ($vip as $k => $v)
                <label class="radio-inline" @if($k == 'Y') style="padding-left: 0;" @endif>
                    <input type="radio" id="vip" name="vip" value="{{ $k }}" @if($info->vip == $k) checked @endif> {{ $v }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="form-group">
        <label for="mobile" class="col-sm-3 control-label"><span class="need">*</span>手机号</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="mobile" name="mobile" value="{{ $info->mobile }}"/>
        </div>
    </div>
    <div class="form-group">
        <label for="wechat" class="col-sm-3 control-label">微信号</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="wechat" name="wechat" value="{{ $info->wechat }}"/>
        </div>
    </div>
    <div class="form-group">
        <label for="remark" class="col-sm-3 control-label">备注</label>
        <div class="col-sm-8">
            <textarea class="form-control" id="remark" name="remark" rows="3" maxlength="200" placeholder="备注不要超过200字">{{ $info->remark }}</textarea>
        </div>
    </div>
    <input type="hidden" name="id" value="{{ $info->id }}}"/>
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-8">
            <button type="submit" class="btn btn-info" id="updateSubmit">保存</button>
        </div>
    </div>
</form>