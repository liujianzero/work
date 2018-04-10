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