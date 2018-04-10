@forelse ($comment as $v)
    <div class="bs-callout bs-callout-success row">
        <div class="col-sm-2">
            <img src="@if(file_exists($v->avatar)){{ "/$v->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
        </div>
        <div class="col-sm-10">
            <h4>
                <b>{{ $v->f_nickname or $v->f_username }}</b> <small>对</small> <b>{{ $v->nickname or $v->username }}</b> <small>评价</small>
                |
                @if ($v->type == 1)
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower1.png')}}"/>
                    <span style="color: #d81e06;">好评</span>
                @elseif ($v->type == 2)
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower2.png')}}"/>
                    <span style="color: #2a8818;">中评</span>
                @elseif ($v->type == 3)
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower3.png')}}"/>
                    <span style="color: #525252;">差评</span>
                @endif
            </h4>
            <p class="intro-p">提交于：<code>{{ $v->created_at }}</code></p>
            <hr/>
            <p class="intro-p">{{ $v->comment or '无评语' }}</p>
            <p class="intro-p">
                @if ($info->uid != $v->to_uid)
                    工作速度：
                    @for ($i = 0; $i < $v->speed_score; $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start2.png')}}"/>
                    @endfor
                    @for ($i = 0; $i < (5 - $v->speed_score); $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start1.png')}}"/>
                    @endfor
                    |
                    工作质量：
                    @for ($i = 0; $i < $v->quality_score; $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start2.png')}}"/>
                    @endfor
                    @for ($i = 0; $i < (5 - $v->quality_score); $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start1.png')}}"/>
                    @endfor
                    |
                    工作态度：
                    @for ($i = 0; $i < $v->attitude_score; $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start2.png')}}"/>
                    @endfor
                    @for ($i = 0; $i < (5 - $v->attitude_score); $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start1.png')}}"/>
                    @endfor
                @else
                    付款及时性：
                    @for ($i = 0; $i < $v->speed_score; $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start2.png')}}"/>
                    @endfor
                    @for ($i = 0; $i < (5 - $v->speed_score); $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start1.png')}}"/>
                    @endfor
                    |
                    合作愉快：
                    @for ($i = 0; $i < $v->quality_score; $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start2.png')}}"/>
                    @endfor
                    @for ($i = 0; $i < (5 - $v->quality_score); $i++)
                        <img src="{{ Theme::asset()->url('images/myOrder/task/start1.png')}}"/>
                    @endfor
                @endif
            </p>
        </div>
    </div>
@empty
    <div class="bs-callout bs-callout-warning text-center">
        <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/goods/images/nomessage.png') }}"/>
        <h4>暂无评价记录</h4>
    </div>
@endforelse
@if ($comment->hasPages())
    <nav aria-label="Page navigation" class="text-right">
        <ul class="pagination">
            {!! ajax_page($comment, 'ajaxPage(this)', ['id' => $info->id, 'type' => 'comment']) !!}
        </ul>
    </nav>
@endif