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