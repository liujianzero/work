<form class="form-horizontal" id="validateFrom">
    <div class="form-group">
        <label for="order_sn" class="col-sm-3 control-label">订单号</label>
        <div class="col-sm-8">
            <p class="form-control-static">{{ $info->order_sn }}</p>
        </div>
    </div>
    <div class="form-group">
        <label for="consignee" class="col-sm-3 control-label">买家</label>
        <div class="col-sm-8">
            <p class="form-control-static">{{ $info->consignee }}</p>
        </div>
    </div>
    <div class="form-group">
        <label for="mobile" class="col-sm-3 control-label">手机</label>
        <div class="col-sm-8">
            <p class="form-control-static">{{ $info->mobile }}</p>
        </div>
    </div>
    <div class="form-group">
        <label for="type-1&type-2&type-3" class="col-sm-3 control-label"><span class="need">*</span>总体评价</label>
        <div class="col-sm-9">
            <label class="radio-inline">
                <img src="{{ Theme::asset()->url('images/myOrder/task/flower1.png')}}"/> 好评 <input type="radio" id="type-1" name="shop_evaluate" value="1" checked/>
            </label>
            <label class="radio-inline">
                <img src="{{ Theme::asset()->url('images/myOrder/task/flower2.png')}}"/> 中评 <input type="radio" id="type-2" name="shop_evaluate" value="2"/>
            </label>
            <label class="radio-inline">
                <img src="{{ Theme::asset()->url('images/myOrder/task/flower3.png')}}"/> 差评 <input type="radio" id="type-3" name="shop_evaluate" value="3"/>
            </label>
        </div>
    </div>
    <div class="form-group">
        <label for="shop_comment" class="col-sm-3 control-label">评语</label>
        <div class="col-sm-8">
            <textarea class="form-control" id="shop_comment" name="shop_comment" rows="3" placeholder="这次合作是否愉快，请给出评语！"></textarea>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-8">
            <button type="submit" class="btn btn-primary" id="formSubmit">保存</button>
        </div>
    </div>
    <input type="hidden" name="id" value="{{ $info->id }}"/>
</form>
