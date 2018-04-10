<div id="attr" class="row">
    <div class="col-sm-11">
        <form class="form-horizontal">
            <div class="row">
                <div class="form-group">
                    <label for="goods_type_id" class="col-sm-3 control-label">属性类型</label>
                    <div class="col-sm-5">
                        <select onchange="getAttrs(this);" id="goods_type_id" name="goods_type_id" data-m="{{ $attr['m'] or 0 }}" data-l="{{ $attr['l'] or 0 }}" class="form-control">
                            <option value="">请选择属性类型</option>
                            @foreach ($type as $k => $v)
                                <option value="{{ $k }}" @if($k == $attr['type_id']) selected @endif>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div id="attr_list">{{-- 属性列表 --}}
                @if (count($attr['manual']) > 0)
                    <div class="row">
                        <div class="form-group">
                            <label for="goods_type_id" class="col-sm-3 control-label"><span class="txt">属性</span></label>
                            <div class="col-sm-9"></div>
                        </div>
                    </div>
                    @foreach ($attr['manual'] as $k => $v)
                        <div class="row">
                            <div class="form-group">
                                <label for="manual-{{ $k }}{{ $v['id'] }}" class="col-sm-3 control-label">{{ $v['name'] }}</label>
                                <div class="col-sm-5">
                                    <input id="manual-{{ $k }}{{ $v['id'] }}" data-goods_attr_id="{{ $v['goods_attr_id'] }}" type="text" data-id="{{ $v['id'] }}" data-name="{{ $v['name'] }}" value="{{ $v['value'] }}" class="form-control"/>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div id="spec_list">{{-- 规格列表 --}}
                @if (count($attr['list']) > 0)
                    <div class="row">
                        <div class="form-group">
                            <label for="goods_type_id" class="col-sm-3 control-label"><span class="txt">规格</span></label>
                            <div class="col-sm-9"></div>
                        </div>
                    </div>
                    @foreach($attr['list'] as $k => $v)
                        <div class="row">
                            <div class="form-group">
                                <label for="goods_type_id-{{ $v['id'] }}{{ $k }}" class="col-sm-3 control-label">{{ $v['name'] }}</label>
                                <div class="col-sm-9">
                                    @foreach($v['value'] as $k1 => $v1)
                                        <label class="checkbox-inline">
                                            <input type="checkbox" @if(isset($v['checked'][$k1]) && $v['checked'][$k1] == 1) checked @endif data-goods_attr_id="@if(isset($v['attr_id'][$k1])){{ $v['attr_id'][$k1] }}@else{{ 0 }}@endif" data-index="{{ $v['id'] }}-{{ $v1 }}" data-id="{{ $v['id'] }}" value="{{ $v1 }}"> {{ $v1 }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div id="price_list">{{-- 价格列表 --}}
                @foreach($attr['price'] as $k => $v)
                    <div class="row" id="spec-list-{{ $v[0] }}-{{ $v[1] }}">
                        <div class="form-group">
                            <label for="goods_type_id" class="col-sm-3 control-label">{{ $v[1] }}</label>
                            <div class="col-sm-5">
                                <div class="input-group">
                                    <div class="input-group-addon">￥</div>
                                    <input type="text" class="form-control" data-id="{{ $v[0] }}" data-goods_attr_id="@if(isset($v[3])){{ $v[3] }}@else{{ 0 }}@endif" data-name="{{ $v[1] }}" value="{{ $v[2] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>
</div>