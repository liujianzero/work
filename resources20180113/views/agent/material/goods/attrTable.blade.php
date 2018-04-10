<table class="table table-hover table-bordered table-condensed">
    <thead>
        <tr>
            @foreach($head as $v)
                <th>{{ $v->name }}</th>
            @endforeach
            <th>库存</th>
        </tr>
    </thead>
    <tbody>
        @foreach($body as $key => $value)
            <tr>
                @foreach ($value as $k => $v)
                    <td>
                        @if ($head[$k]['id'] = $v['attr_id'])
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
                        <input type="text" class="form-control" id="" name="stock[{{ $key }}][val]" value="{{ $list[$stock[$key]] or 0 }}">
                    </div>
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="{{ count($head) + 2 }}">
                <span class="label label-success">共有 {{ count($body) }} 种组合</span>
            </td>
        </tr>
    </tbody>
</table>