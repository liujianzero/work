<div class="container" style="width: 95%;height: 95%;padding: 20px;">
    <form class="form-horizontal" id="validateFrom">
        <div class="row">
            <div class="form-group">
                <label for="title" class="col-sm-2 control-label"><span class="need">*</span> 题目</label>
                <div class="col-sm-8">
                    <input id="title" name="title" value="{{ $info->title or null }}" type="text" class="form-control"/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="score" class="col-sm-2 control-label"><span class="need">*</span> 分数</label>
                <div class="col-sm-4">
                    <input id="score" name="score" value="{{ $info->score or null }}" type="number" class="form-control"/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="type" class="col-sm-2 control-label"><span class="need">*</span> 类型</label>
                <div class="col-sm-8">
                    <label class="radio-inline">
                        <input type="radio" name="type" value="single" @if(!isset($info->type) || (isset($info->type) && $info->type == 'single')) checked @endif onchange="toggleSubject(this)"> 单选
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="type" value="multiple" @if(isset($info->type) && $info->type == 'multiple') checked @endif onchange="toggleSubject(this)"> 多选
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="value" class="col-sm-2 control-label"><span class="need">*</span> 答案</label>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-info" onclick="addAnswer(this);">添加答案</button>
                </div>
                <div class="col-sm-6">
                    <p class="form-control-static">请选择答案序号及勾选正确答案并填入答案内容</p>
                </div>
            </div>
        </div>
        <div class="row">
            @if($action == 'edit')
                @foreach($info->storeSubjectAnswers as $answer)
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-2">
                            <select class="form-control" onchange="selectLetter(this);">
                                @foreach($letter as $v)
                                    <option value="{{ $v->upper }}" @if($v->upper == $answer->option) selected @endif>{{ $v->upper }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="@if($info->type == 'single'){{ 'radio' }}@else{{ 'checkbox' }}@endif" class="answers" name="subject[checked][]" value="{{ $answer->option }}" @if($answer->is_right == 'Y') checked @endif>
                                </span>
                                <input type="text" class="form-control" name="subject[{{ $answer->option }}][answer]" value="{{ $answer->title }}">
                            </div>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-danger" onclick="delAnswer(this);"><i class="fa fa-minus"></i></button>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- A --}}
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-2">
                        <select class="form-control" onchange="selectLetter(this);">
                            @foreach($letter as $v)
                                <option value="{{ $v->upper }}">{{ $v->upper }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <input type="radio" class="answers" name="subject[checked][]" checked value="{{ $letter[0]['upper'] }}">
                            </span>
                            <input type="text" class="form-control" name="subject[{{ $letter[0]['upper'] }}][answer]">
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger" onclick="delAnswer(this);"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                {{-- B --}}
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-2">
                        <select class="form-control" onchange="selectLetter(this);">
                            @foreach($letter as $v)
                                <option value="{{ $v->upper }}" @if($v->upper == $letter[1]['upper']) selected @endif>{{ $v->upper }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <input type="radio" class="answers" name="subject[checked][]" value="{{ $letter[1]['upper'] }}">
                            </span>
                            <input type="text" class="form-control" name="subject[{{ $letter[1]['upper'] }}][answer]">
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger" onclick="delAnswer(this);"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
            @endif
        </div>
        <div class="row">
            <div class="form-group">
                <label for="value" class="col-sm-2 control-label"></label>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary" id="formSubmit">提交</button>
                </div>
            </div>
        </div>
        <input type="hidden" name="id" value="{{ $info->id or null }}"/>
    </form>
</div>