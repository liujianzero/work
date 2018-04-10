<style type="text/css">
    #post_number_div select,
    #post_number_div input{
        background: #181818;
        border: 1px solid #5d5d5d;
        color: #fff;
        border-radius: 3px !important;
    }
    .error{
        color: #f02727;
        font-size: 14px;
    }
</style>
<div id="post_number_div" class="container" style="width: 100%;margin: 30px 0;">
    <div class="row">
        <div class="col-sm-10 col-sm-offset-1">
            <div class="row" style="margin-bottom: 20px">
                <div class="col-sm-3 text-right" style="color: #fff">
                    订单号
                </div>
                <div class="col-sm-8">
                    {{ $info->order_sn }}
                </div>
            </div>
            <form id="validateFrom" class="form-horizontal">
                <div class="form-group">
                    <label for="express_id" class="col-sm-3 control-label">物流公司</label>
                    <div class="col-sm-8">
                        <select class="form-control" id="express_id" name="express_id">
                            <option value="">请选择</option>
                            @foreach ($express as $v)
                                <option value="{{ $v->id }}">{{ $v->express_name }}</option>
                            @endforeach
                        </select>
                        <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 如果需要更多公司请联系平台开通</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="post_number" class="col-sm-3 control-label">物流单号</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="post_number" name="post_number">
                    </div>
                </div>
                <div class="form-group">
                    <label for="post_number_confirm" class="col-sm-3 control-label">校验单号</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="post_number_confirm" name="post_number_confirm">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-10">
                        <button type="submit" id="formSubmit" class="btn btn-primary">确认发货</button>
                    </div>
                </div>
                <input type="hidden" name="id" value="{{ $info->id }}"/>
            </form>
        </div>
    </div>
</div>