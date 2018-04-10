@forelse ($list as $v)
    <tr data-subject="{{ $v->id }}">
        <td>{{ $v->id }}</td>
        <td title="{{ $v->title }}">{{ cut_str($v->title, 30) }}</td>
        <td>{{ $v->score }}</td>
        <td>
            @if ($v->type == 'single')
                单选
            @else
                多选
            @endif
        </td>
        <td>
            <button type="button" class="btn btn-info btn-sm" onclick="editSubject(this)" data-id="{{ $v->id }}">
                <i class="fa fa-edit"></i> 编辑
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeSubject(this)" data-title="{{ $v->title }}" data-id="{{ $v->id }}">
                <i class="fa fa-trash"></i> 移除
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td class="text-center" colspan="4">没有找到匹配的记录</td>
    </tr>
@endforelse