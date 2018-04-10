{{-- 属性 --}}
@if (count($data['manual']))
    <div class="form-group">
        <label for="goods_type_id" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"><span class="label label-primary">属性</span></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
            <p class="form-control-static">用于展示商品参数</p>
        </div>
    </div>
    @foreach ($data['manual'] as $v)
        <div class="form-group">
            <label for="manual-{{ $v['attr_id'] }}" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label">{{ $v['name'] }}</label>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                <input type="hidden" name="manual[{{ $v['attr_id'] }}][attr_id]" value="{{ $v['attr_id'] }}"/>
                <input type="hidden" name="manual[{{ $v['attr_id'] }}][goods_attr_id]" value="{{ $v['goods_attr_id'] }}"/>
                <input type="text" class="form-control" id="manual-{{ $v['attr_id'] }}" name="manual[{{ $v['attr_id'] }}][val]" value="{{ $v['value'] }}">
            </div>
        </div>
    @endforeach
@endif
{{-- /属性 --}}

{{-- 规格 --}}
@if (count($data['list']))
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"><span class="label label-primary">规格</span></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
            <p class="form-control-static">用户可根据自身所需选取商品</p>
        </div>
    </div>
    @foreach ($data['list'] as $key => $val)
        <div class="form-group">
            <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label">{{ $val['name'] }}</label>
            <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
                <div class="row">
                    @foreach ($val['value'] as $k => $v)
                        <div class="col-xs-4 col-sm-4 col-md-3 col-lg-2">
                            <label class="checkbox-inline">
                                <input type="hidden" name="list[{{ $index = $v['attr_id'] . '_' . $k }}][attr_id]" value="{{ $v['attr_id'] }}"/>
                                <input type="hidden" name="list[{{ $index }}][goods_attr_id]" value="{{ $v['goods_attr_id'] }}"/>
                                <input type="hidden" name="list[{{ $index }}][val]" value="{{ $v['name'] }}"/>
                                <input type="checkbox" data-price="{{ $v['price'] or '0.00' }}" data-index="{{ $index }}" data-goods_attr_id="{{ $v['goods_attr_id'] }}" name="list[{{ $index }}][checked]" value="{{ $v['name'] }}" @if ($v['checked']) checked @endif> <abbr title="{{ $v['name'] }}">{{ cut_str($v['name'], 3) }}</abbr>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endif
{{-- /规格 --}}

{{-- 库存表 --}}
<div class="type-table" @if (! count($data['table'])) style="display: none;" @endif>
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"><span class="label label-primary">库存</span></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-4">
            <p class="form-control-static">为每一种组合设置相应的库存量，支持批量设置</p>
        </div>
    </div>
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
            <div class="input-group">
                <input type="text" class="form-control" value="0">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" onclick="oneKeySetNumber(this);">一键设置库存</button>
                </span>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"></label>
        <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8 table-responsive attr-goods-number">
            @if (count($data['table']))
                <table class="table table-hover table-bordered table-condensed">
                    <thead>
                        <tr>
                            @foreach($data['table']['head'] as $v)
                                <th>{{ $v->name }}</th>
                            @endforeach
                            <th>库存</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['table']['body'] as $key => $value)
                            <tr>
                                @foreach ($value as $k => $v)
                                    <td>
                                        @if ($data['table']['head'][$k]['id'] = $v['attr_id'])
                                            {{ $v['val'] }}
                                            <input type="hidden" name="stock[{{ $key }}][name][]" value="{{ $v['val'] }}"/>
                                            <input type="hidden" name="stock[{{ $key }}][attr_id][]" value="{{ $v['attr_id'] }}"/>
                                            <input type="hidden" name="stock[{{ $key }}][goods_attr_id][]" value="{{ $v['goods_attr_id'] }}"/>
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    <div>
                                        <label class="sr-only" for="">属性库存</label>
                                        <input type="text" class="form-control" id="" name="stock[{{ $key }}][val]" value="{{ $data['table']['list'][$data['table']['stock'][$key]] }}">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                            <tr>
                                <td colspan="{{ count($data['table']['head']) + 2 }}">
                                    <span class="label label-success">共有 {{ count($data['table']['body']) }} 种组合</span>
                                </td>
                            </tr>
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
{{-- /库存表 --}}

{{-- 价格 --}}
<div class="price-table" @if (! count($data['price'])) style="display: none;" @endif>
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"><span class="label label-primary">价格</span></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-4">
            <p class="form-control-static">设置单个规格的价格，支持批量设置</p>
        </div>
    </div>
    <div class="form-group">
        <label for="" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label"></label>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
            <div class="input-group">
                <input type="text" class="form-control" data-price="0.00" value="0.00">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" onclick="oneKeySetPrice(this);">一键设置价格</button>
                </span>
            </div>
        </div>
    </div>
    <div class="price-table-list">
        @foreach($data['price'] as $v)
            <div class="form-group" data-index="list_{{ $v['index'] }}">
                <label for="{{ $v['index'] }}" class="col-xs-3 col-sm-3 col-md-3 col-lg-3 control-label">{{ $v['attr_value'] }}</label>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-3">
                    <div class="input-group">
                        <div class="input-group-addon">￥</div>
                        <input type="text" class="form-control" id="{{ $v['index'] }}" data-price="{{ $v['attr_price'] or '0.00' }}" name="list[{{ $v['index'] }}][price]" value="{{ $v['attr_price'] or '0.00' }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
{{-- /价格 --}}