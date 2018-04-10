@forelse ($list as $v)
    <tr data-type="{{ $v->id }}">
        <td>{{ $v->name }}</td>
        <td>{{ $v->type_name }}</td>
        <td>{{ $v->num }}</td>
        <td>
            @if ($v->input_type == 'list')
                列表选择
            @else
                手工录入
            @endif
        </td>
        <td>{{ str_replace("\r\n", '，', $v->value) }}</td>
        <td>
            <button onclick="editAttr({{ $v->id }})" class="btn btn-info btn-sm"><i class="fa fa-edit"></i> 编辑</button>
            @if (! $v->num)
                <button onclick="removeAttr(this)" data-name="{{ $v->name }}" data-id="{{ $v->id }}" type="button" class="btn btn-danger btn-sm" ><i class="fa fa-trash"></i> 移除</button>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center">没有找到匹配的记录</td>
    </tr>
@endforelse