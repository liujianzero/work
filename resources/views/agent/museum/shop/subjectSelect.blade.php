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
            @if($guid)
                <button type="button" class="btn btn-info btn-sm" onclick="replaceSubject(this)" data-id="{{ $v->id }}" data-index="{{ $index }}" data-guid="{{ $guid }}">
                    <i class="fa fa-window-restore" aria-hidden="true"></i> 替换
                </button>
            @else
                <button type="button" class="btn btn-info btn-sm" onclick="selectSubject(this)" data-id="{{ $v->id }}" data-index="{{ $index }}">
                    <i class="fa fa-hand-pointer-o" aria-hidden="true"></i> 选择
                </button>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td class="text-center" colspan="4">没有找到匹配的记录</td>
    </tr>
@endforelse