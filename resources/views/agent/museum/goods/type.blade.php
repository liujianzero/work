@forelse ($list as $v)
    <tr data-type="{{ $v->id }}">
        <td>{{ $v->name }}</td>
        <td>{{ $v->num }}</td>
        <td>
            <button onclick="getAttrList({{ $v->id }})" class="btn btn-primary btn-sm"><i class="fa fa-list"></i> 属性列表</button>
            <button onclick="editType(this)" data-name="{{ $v->name }}" data-id="{{ $v->id }}" type="button" class="btn btn-info btn-sm"><i class="fa fa-edit"></i> 编辑</button>
            @if (! $v->num)
                <button onclick="removeType(this)"  data-name="{{ $v->name }}" data-id="{{ $v->id }}" type="button" class="btn btn-danger btn-sm" ><i class="fa fa-trash"></i> 移除</button>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="text-center">没有找到匹配的记录</td>
    </tr>
@endforelse