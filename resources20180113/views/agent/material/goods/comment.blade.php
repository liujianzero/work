<div class="container" id="comment-info">
    <div class="bs-callout bs-callout-warning row">
        <div class="col-sm-2">
            <img src="@if(file_exists($user->avatar)){{ "/$user->avatar" }}@else{{ Theme::asset()->url('images/defauthead.png') }}@endif" class="img-circle" style="width: 100px;">
        </div>
        <div class="col-sm-10">
            <h4>对 <var>@</var>{{ $user->nickname or $user->username }} 评价</h4>
            <hr/>
            <p class="intro-p">好评率：{{ CommonClass::applauseRate($user->uid) }}%</p>
            @if ($user->mobile && $user->mobile_status == 1)
                <p class="intro-p">手机：{{ $user->phone }}</p>
            @endif
            @if ($user->qq && $user->qq_status == 1)
                <p class="intro-p">QQ：{{ $user->qq }}</p>
            @endif
            @if ($user->wechat && $user->wechat_status == 1)
                <p class="intro-p">微信：{{ $user->wechat }}</p>
            @endif
        </div>
    </div>
    <form class="form-horizontal" id="validateFrom">
        <div class="form-group">
            <label for="type1@type2@type3" class="col-sm-3 control-label"><span class="need">*</span>总体评价</label>
            <div class="col-sm-9">
                <label class="radio-inline" style="padding-left: 0;">
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower1.png')}}"/> 好评 <input type="radio" id="type-1" name="type" value="1" checked/>
                </label>
                <label class="radio-inline">
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower2.png')}}"/> 中评 <input type="radio" id="type-2" name="type" value="2"/>
                </label>
                <label class="radio-inline">
                    <img src="{{ Theme::asset()->url('images/myOrder/task/flower3.png')}}"/> 差评 <input type="radio" id="type-3" name="type" value="3"/>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label for="comment" class="col-sm-3 control-label">评价内容</label>
            <div class="col-sm-8">
                <textarea class="form-control" id="comment" name="comment" rows="5"></textarea>
            </div>
        </div>
        <div class="form-group">
            <label for="function-star1" class="col-sm-3 control-label"><span class="need">*</span>工作速度</label>
            <div class="col-sm-9">
                <div id="function-star1"></div>
            </div>
        </div>
        <div class="form-group">
            <label for="function-star2" class="col-sm-3 control-label"><span class="need">*</span>工作质量</label>
            <div class="col-sm-9">
                <div id="function-star2"></div>
            </div>
        </div>
        <div class="form-group">
            <label for="function-star3" class="col-sm-3 control-label"><span class="need">*</span>工作态度</label>
            <div class="col-sm-9">
                <div id="function-star3"></div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-8">
                <button type="button" class="btn btn-primary" onclick="commentSubmit(this);">保存</button>
            </div>
        </div>
        <input type="hidden" name="task_id" value="{{ $work->task_id }}" />
        <input type="hidden" name="work_id" value="{{ $work->id }}"/>
    </form>
</div>