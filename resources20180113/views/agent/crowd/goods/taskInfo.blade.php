<div class="container" id="task-info">
    <ul class="nav nav-pills nav-justified">
        @foreach ($nav as $v)
            <li role="presentation" class="{{ $v['class'] }}" data-tab="{{ $v['tab'] }}"><a href="javascript:void(0);">{{ $v['name'] }}</a></li>
        @endforeach
    </ul>

    {{-- 投稿 --}}
    <div class="row task-info-div" style="display: block;">
        @forelse ($work as $v)
            @if ($info->type_id == 2)
                <div class="bs-callout bs-callout-info row">
                    <div class="col-sm-2">
                        <img src="@if(file_exists($v->avatar)){{ "/$v->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
                    </div>
                    <div class="col-sm-10">
                        <h4>
                            {{ $v->nickname or $v->username }}
                            |
                            <span class="label-tender">标价：￥{{ $v->bidding_price }}</span>
                            |
                            <span class="label-tender">开发周期：{{ $v->work_time }}天</span>
                            |
                            好评率：{{ applause_rate($v->comments, $v->good) }}%
                            @if ($info->status == 3 && $v->status == 0)
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" data-task_id="{{ $info->id }}" data-nickname="{{ $v->nickname or $v->username }}" onclick="selectWork(this);"><i class="fa fa-trophy" aria-hidden="true"></i> 选TA</button>
                                </div>
                            @endif
                        </h4>
                        <p class="intro-p">提交于：<code>{{ $v->created_at }}</code></p>
                        <hr/>
                        @if ($v->desc)
                            <button type="button" class="btn btn-info btn-sm" id="desc-{{ $v->id }}" data-index="0" onclick="descTips(this, '{{ $v->desc }}');">查看附加信息</button>
                        @else
                            无附加信息
                        @endif
                        <div>
                            <form style="float: left;">
                                <a class="btn btn-link" style="padding-left: 0;"><pre><i class="fa fa-paperclip fa-rotate-90"></i> 附件（{{ count($v->childrenAttachment) }}）</pre></a>
                            </form>
                            @foreach ($v->childrenAttachment as $key => $attachment)
                                <form method="post" action="{{ route('agent.common.attachment.download', $attachment->attachment_id) }}"  style="float: left;">
                                    {!! csrf_field() !!}
                                    <button class="btn btn-link"><i class="fa fa-cloud-download" aria-hidden="true"></i> 附件{{ $key + 1 }}.{{ $attachment->type }}</button>
                                </form>
                            @endforeach
                        </div>
                        @if($v->status == 1)
                            <div class="selected"></div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bs-callout bs-callout-info row">
                    <div class="col-sm-2">
                        <img src="@if(file_exists($v->avatar)){{ "/$v->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
                    </div>
                    <div class="col-sm-10">
                        <h4>
                            {{ $v->nickname or $v->username }}
                            |
                            好评率：{{ applause_rate($v->comments, $v->good) }}%
                            @if ($info->status == 3 && $v->status == 0)
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" data-task_id="{{ $info->id }}" data-nickname="{{ $v->nickname or $v->username }}" onclick="selectWork(this);"><i class="fa fa-trophy" aria-hidden="true"></i> 选TA</button>
                                </div>
                            @endif
                        </h4>
                        <p class="intro-p">提交于：<code>{{ $v->created_at }}</code></p>
                        <hr/>
                        @if ($v->desc)
                            <button type="button" class="btn btn-info btn-sm" id="desc-{{ $v->id }}" data-index="0" onclick="descTips(this, '{{ $v->desc }}');">查看附加信息</button>
                        @else
                            无附加信息
                        @endif
                        <p class="intro-p"><a href="{{ $domain }}/view-{{ $v->action_id }}" class="btn btn-link" target="_blank" style="padding-left: 0;"><i class="fa fa-paperclip fa-rotate-90"></i> 查看作品</a></p>
                        @if($v->status == 1)
                            <div class="selected"></div>
                        @endif
                    </div>
                </div>
            @endif
        @empty
            <div class="bs-callout bs-callout-warning text-center">
                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/goods/images/nomessage.png') }}"/>
                <h4>暂无投稿记录</h4>
            </div>
        @endforelse
        @if ($work->hasPages())
            <nav aria-label="Page navigation" class="text-right">
                <ul class="pagination">
                    {!! ajax_page($work, 'ajaxPage(this)', ['id' => $info->id, 'type' => 'work']) !!}
                </ul>
            </nav>
        @endif
    </div>

    {{-- 交付 --}}
    <div class="row task-info-div">
        @forelse ($delivery as $v)
            @if ($info->type_id == 2)
                <div class="bs-callout bs-callout-primary row">
                    <div class="col-sm-2">
                        <img src="@if(file_exists($v->avatar)){{ "/$v->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
                    </div>
                    <div class="col-sm-10">
                        <h4>
                            {{ $v->nickname or $v->username }}
                            |
                            <span class="label-tender">标价：￥{{ $v->bidding_price }}</span>
                            |
                            <span class="label-tender">开发周期：{{ $v->work_time }}天</span>
                            |
                            好评率：{{ applause_rate($v->comments, $v->good) }}%
                            @if ($v->status == 2)
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" onclick="deliveryCheck(this);"><i class="fa fa-credit-card" aria-hidden="true"></i> 验收付款</button>
                                </div>
                            @elseif ($v->status == 3 && ! CommonClass::ownerEvalute($v->task_id, $uid, $v->uid))
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" data-task_id="{{ $info->id }}" onclick="commentWork(this);"><i class="fa fa-heart" aria-hidden="true"></i> 去评价</button>
                                </div>
                            @endif
                        </h4>
                        <p class="intro-p">提交于：<code>{{ $v->created_at }}</code></p>
                        <hr/>
                        <p class="intro-p">最晚交稿时间：<code>{{ $info->delivery_deadline }}</code></p>
                        <a href="{{ $domain }}/view-{{ $v->action_id }}" class="btn btn-link" style="padding-left: 0;" target="_blank"><i class="fa fa-paperclip fa-rotate-90"></i> 验收作品</a>
                    </div>
                </div>
            @else
                <div class="bs-callout bs-callout-primary row">
                    <div class="col-sm-2">
                        <img src="@if(file_exists($v->avatar)){{ "/$v->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
                    </div>
                    <div class="col-sm-10">
                        <h4>
                            {{ $v->nickname or $v->username }}
                            |
                            好评率：{{ applause_rate($v->comments, $v->good) }}%
                            @if ($v->status == 2)
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" onclick="deliveryCheck(this);"><i class="fa fa-credit-card" aria-hidden="true"></i> 验收付款</button>
                                </div>
                            @elseif ($v->status == 3 && ! CommonClass::ownerEvalute($v->task_id, $uid, $v->uid))
                                <div class="pull-right">
                                    <button type="button" class="btn btn-primary btn-sm" data-id="{{ $v->id }}" data-task_id="{{ $info->id }}" onclick="commentWork(this);"><i class="fa fa-heart" aria-hidden="true"></i> 去评价</button>
                                </div>
                            @endif
                        </h4>
                        <p class="intro-p">提交于：<code>{{ $v->created_at }}</code></p>
                        <hr/>
                        <a href="{{ $domain }}/view-{{ $v->action_id }}" class="btn btn-link" style="padding-left: 0;" target="_blank"><i class="fa fa-paperclip fa-rotate-90"></i> 验收作品</a>
                    </div>
                </div>
            @endif
        @empty
            <div class="bs-callout bs-callout-warning text-center">
                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/goods/images/nomessage.png') }}"/>
                <h4>暂无交付记录</h4>
            </div>
        @endforelse
        @if ($delivery->hasPages())
            <nav aria-label="Page navigation" class="text-right">
                <ul class="pagination">
                    {!! ajax_page($delivery, 'ajaxPage(this)', ['id' => $info->id, 'type' => 'delivery']) !!}
                </ul>
            </nav>
        @endif
    </div>

    {{-- 评价 --}}
    <div class="row task-info-div">
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
    </div>

    {{-- 维权 --}}
    {{--<div class="row task-info-div">
        维权
    </div>--}}
</div>