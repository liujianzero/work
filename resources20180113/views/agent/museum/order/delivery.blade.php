<form class="form-horizontal" id="validateFrom">
    <div class="form-group">
        <label for="order_sn" class="col-sm-3 control-label">订单号</label>
        <div class="col-sm-8">
            <p class="form-control-static">{{ $info->order_sn }}</p>
        </div>
    </div>
    <div class="form-group">
        <label for="express_id" class="col-sm-3 control-label"><span class="need">*</span>物流公司</label>
        <div class="col-sm-8">
            <select class="form-control" id="express_id" name="express_id">
                <option value="">请选择</option>
                @foreach ($express as $v)
                    <option value="{{ $v->id }}">{{ $v->express_name }}</option>
                @endforeach
            </select>
            <span class="input-tips"><i class="fa fa-info-circle"></i> 如果需要更多快递公司请联系平台开通</span>
        </div>
    </div>
    <div class="form-group">
        <label for="post_number" class="col-sm-3 control-label"><span class="need">*</span>物流单号</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="post_number" name="post_number">
        </div>
    </div>
    <div class="form-group">
        <label for="post_number_confirm" class="col-sm-3 control-label"><span class="need">*</span>校验单号</label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="post_number_confirm" name="post_number_confirm">
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-8">
            <button type="submit" class="btn btn-primary" id="formSubmit">确认发货</button>
        </div>
    </div>
    <input type="hidden" name="id" value="{{ $info->id }}"/>
</form>
