@if (count($list) > 0)
    <div class="row">
        <div class="form-group">
            <label for="goods_type_id" class="col-sm-3 control-label"><span class="txt">属性</span></label>
            <div class="col-sm-9"></div>
        </div>
    </div>
    @foreach($list as $k => $v)
        <div class="row">
            <div class="form-group">
                <label for="manual-{{ $k }}{{ $v['id'] }}" class="col-sm-3 control-label">{{ $v['name'] }}</label>
                <div class="col-sm-5">
                    <input id="manual-{{ $k }}{{ $v['id'] }}" type="text" data-goods_attr_id="{{ $v['goods_attr_id'] }}" data-id="{{ $v['id'] }}" data-name="{{ $v['name'] }}" class="form-control"/>
                </div>
            </div>
        </div>
    @endforeach
@endif