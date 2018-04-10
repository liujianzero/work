@if (count($list) > 0)
    <div class="row">
        <div class="form-group">
            <label for="goods_type_id" class="col-sm-3 control-label"><span class="txt">规格</span></label>
            <div class="col-sm-9"></div>
        </div>
    </div>
    @foreach($list as $k => $v)
        <div class="row">
            <div class="form-group">
                <label for="goods_type_id-{{ $v['id'] }}{{ $k }}" class="col-sm-3 control-label">{{ $v['name'] }}</label>
                <div class="col-sm-9">
                    @foreach($v['value'] as $k1 => $v1)
                        <label class="checkbox-inline">
                            <input type="checkbox" data-goods_attr_id="@if(isset($v['attr_id'][$k1])){{ $v['attr_id'][$k1] }}@else{{ 0 }}@endif" data-index="{{ $v['id'] }}-{{ $v1 }}" data-id="{{ $v['id'] }}" value="{{ $v1 }}"> {{ $v1 }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endif