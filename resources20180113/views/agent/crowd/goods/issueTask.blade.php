<div class="container" id="task-issue">
    <ul class="nav nav-tabs nav-justified">
        <li role="presentation" class="active"><a href="javascript:void(0);">描述需求</a></li>
        <li role="presentation"><a href="javascript:void(0);">交易模式</a></li>
        <li role="presentation"><a href="javascript:void(0);">增值服务</a></li>
    </ul>
    <div class="row task-content">
        <form class="form-horizontal" id="validateTaskFrom" enctype="multipart/form-data">
            <div class="form-group">
                <div id="validate-error-msg" class="col-sm-6 col-sm-offset-4">

                </div>
            </div>
            {{-- 描述需求 --}}
            <div class="task-group" style="display: block;">
                <div class="form-group">
                    <label for="phone" class="col-sm-4 control-label"><span class="need">*</span>联系手机</label>
                    <div class="col-sm-4">
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="cate_pid cate_id" class="col-sm-4 control-label"><span class="need">*</span>任务类型</label>
                    <div class="col-sm-2">
                        <label for="cate_pid" class="sr-only">行业</label>
                        <select class="form-control" id="cate_pid" name="cate_pid" onchange="getCategory(this);">
                            <option value="">请选择</option>
                            @foreach ($cate as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label for="cate_id" class="sr-only">分类</label>
                        <select class="form-control" id="cate_id" name="cate_id">
                            <option value="">请选择</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="region_limit" class="col-sm-4 control-label">指定地域</label>
                    <div class="col-sm-6">
                        @foreach ($region_limit as $v)
                            <label class="checkbox-inline">
                                <input type="radio" id="region_limit" name="region_limit" value="{{ $v['val'] }}" @if ($v['val'] == 1) checked @endif> {{ $v['name'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-group region-select" style="display: none;">
                    <label for="province city area" class="col-sm-4 control-label"></label>
                    <div class="col-sm-2">
                        <label for="province" class="sr-only">省份</label>
                        <select class="form-control" id="province" name="province" onchange="getCity(this);">
                            <option value="">请选择</option>
                            @foreach ($province as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label for="city" class="sr-only">城市</label>
                        <select class="form-control" id="city" name="city" onchange="getArea(this);">
                            <option value="">请选择</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label for="area" class="sr-only">地区</label>
                        <select class="form-control" id="area" name="area">
                            <option value="">请选择</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="title" class="col-sm-4 control-label"><span class="need">*</span>需求标题</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" id="title" name="title">
                    </div>
                </div>
                <div class="form-group">
                    <label for="desc" class="col-sm-4 control-label"><span class="need">*</span>需求详情</label>
                    <div class="col-sm-6">
                        <textarea class="form-control" id="desc" name="desc"></textarea>
                        <span class="input-tips"><i class="fa fa-info-circle"></i> 可直接拖拽图片进行上传，支持的格式为：png、jpg、jpeg、gif、bmp，每张大小限制为：2MB，操作图片时请耐心等候片刻</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dropzone" class="col-sm-4 control-label">需求附件</label>
                    <div class="col-sm-6">
                        <div class="dropzone" id="dropzone">
                            <div class="fallback">
                                <input name="file" type="file" multiple/>
                            </div>
                        </div>
                        <div id="file_update"></div>
                        <span class="input-tips"><i class="fa fa-info-circle"></i> 可直接拖拽文件进行上传，支持的格式为：png、jpg、jpeg、gif、bmp、zar、doc、docx、xls、xlsx、ppt、pptx、pdf，每个大小限制为：2MB，最多只能添加3个文件，操作文件时请耐心等候片刻</span>
                    </div>
                </div>
            </div>
            {{-- /描述需求 --}}

            {{-- 交易模式 --}}
            <div class="task-group">
                <div class="form-group">
                    <label for="action_mode" class="col-sm-4 control-label"><span class="need">*</span>交易模式</label>
                    <div class="col-sm-6">
                        @foreach ($type as $k => $v)
                            <div class="col-sm-4" style="padding-left: 0;">
                                <label class="checkbox-inline">
                                    <input type="radio" id="type_id" data-index="{{ $k }}" name="type_id" value="{{ $v->id }}" @if (! $k) checked @endif> {{ $v->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                {{-- 招标 --}}
                <div class="action-mode-div" style="display: block;">
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-6">
                            <p class="form-control-static">先选中标威客，TA再工作。找到中标威客后托管赏金。</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bounty_select" class="col-sm-4 control-label"><span class="need">*</span>赏金</label>
                        <div class="col-sm-6">
                            <label class="checkbox-inline">
                                <input type="radio" id="bounty_select" name="bounty_select" value="0" checked> 直接填写赏金
                            </label>
                            <label class="checkbox-inline">
                                <input type="radio" id="bounty_select" name="bounty_select" value="1"> 选择赏金区间
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tender_bounty tender_bounty_id" class="col-sm-4 control-label"></label>
                        <div class="col-sm-4">
                            <div class="tender-bounty">
                                <label for="tender_bounty" class="sr-only">直接填写赏金</label>
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-rmb"></i></span>
                                    <input type="text" class="form-control" id="tender_bounty" name="tender_bounty">
                                </div>
                            </div>
                            <div class="tender-bounty" style="display: none;">
                                <label for="tender_bounty_id" class="sr-only">选择赏金区间</label>
                                <select class="form-control" id="tender_bounty_id" name="tender_bounty_id">
                                    <option value="">请选择</option>
                                    @foreach ($price_range as $v)
                                        <option value="{{ $v->id }}">￥{{ $v->min_price }} - {{ $v->max_price or '以上' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- 单人 --}}
                <div class="action-mode-div">
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-6">
                            <p class="form-control-static">威客们先工作，再选中标作品。只设置1个中标者。</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="single_bounty" class="col-sm-4 control-label"><span class="need">*</span>赏金</label>
                        <div class="col-sm-4">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-rmb"></i></span>
                                <input type="text" class="form-control" id="single_bounty" name="single_bounty" onchange="handlePrice();">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- 多人 --}}
                <div class="action-mode-div">
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-6">
                            <p class="form-control-static">威客们先工作，再选中标作品。设置多个中标者，平分赏金。</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="multiple_bounty" class="col-sm-4 control-label"><span class="need">*</span>赏金</label>
                        <div class="col-sm-4">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-rmb"></i></span>
                                <input type="text" class="form-control" id="multiple_bounty" name="multiple_bounty" onchange="setMultipleWorker(this);">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="multiple_worker_num" class="col-sm-4 control-label"><span class="need">*</span>中标人数</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="multiple_worker_num" name="multiple_worker_num" onchange="setMultipleWorker('#multiple_bounty');">
                            <span class="input-tips"></span>
                        </div>
                    </div>
                </div>
                {{-- 计件 --}}
                <div class="action-mode-div">
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-6">
                            <p class="form-control-static">威客们先工作，再选中标作品。合格一标，支付一标，均摊赏金。</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="job_bounty" class="col-sm-4 control-label"><span class="need">*</span>赏金</label>
                        <div class="col-sm-4">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-rmb"></i></span>
                                <input type="text" class="form-control" id="job_bounty" name="job_bounty" onchange="setJobWorker(this);">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="job_worker_num" class="col-sm-4 control-label"><span class="need">*</span>所需件数</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="job_worker_num" name="job_worker_num" onchange="setJobWorker('#job_bounty');">
                            <span class="input-tips"></span>
                        </div>
                    </div>
                </div>
                {{-- 时间 --}}
                <div class="form-group">
                    <label for="begin_at delivery_deadline" class="col-sm-4 control-label"><span class="need">*</span>竞标结束时间</label>
                    <div class="col-sm-4">
                        <div class="input-daterange input-group datepicker">
                            <span class="input-group-addon date-icon"><i class="fa fa-calendar bigger-110"></i></span>
                            <label for="delivery_deadline" class="sr-only">竞标结束时间</label>
                            <input type="text" class="form-control text-center" id="delivery_deadline" name="delivery_deadline" placeholder="结束时间" readonly style="background: #fff;cursor: pointer;"/>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /交易模式 --}}

            {{-- 增值服务 --}}
            <div class="task-group">
                @if (count($service))
                    @foreach($service as $k => $v)
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-2 col-md-offset-2 col-md-2 col-lg-offset-4 col-lg-2">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" data-price="{{ $v->price }}" data-index="{{ $k }}" name="product[]" value="{{ $v->id }}">
                                        <span>{{ substr($v->title, 3, 3) }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2 col-md-2 col-lg-1">
                                <p class="form-control-static height money">￥{{ $v->price }}</p>
                            </div>
                            <div class="col-sm-2 col-md-2 col-lg-1">
                                <p class="form-control-static height"><b>{{ $v->title }}</b></p>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">
                                <p class="form-control-static height">{{ $v->description }}</p>
                            </div>
                        </div>
                    @endforeach
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-2 col-md-offset-2 col-md-2  col-lg-offset-4 col-lg-2">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="product-all">
                                    全选
                                </label>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-1">
                            <p class="form-control-static"><b>暂无增值服务</b></p>
                        </div>
                    </div>
                @endif
            </div>
            {{-- /增值服务 --}}

            <hr/>

            {{-- 结算清单 --}}
            <div class="form-group">
                <div class="form-group">
                    <label for="settlement-list" class="col-sm-4 control-label"><span class="label label-info">结算清单</span></label>
                </div>
                <div class="form-group">
                    <label for="settlement-bounty" class="col-sm-4 control-label"></label>
                    <div class="col-sm-2 col-md-2 col-lg-1">
                        <p class="form-control-static"><b>托管赏金</b></p>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-2">
                        <p class="form-control-static">￥<span id="bounty-money">0</span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="settlement-product" class="col-sm-4 control-label"></label>
                    <div class="col-sm-2 col-md-2 col-lg-1">
                        <p class="form-control-static"><b>增值服务</b></p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-4 col-sm-offset-4 col-md-offset-4 col-md-4 col-lg-offset-4 col-lg-4">
                        <table class="table table-hover">
                            @foreach ($service as $k => $v)
                                <tr id="product-{{ $v->id }}" style="display: none;">
                                    <td>
                                        <span class="{{ $k % 2 ? ' active-odd' : ' active-even' }}">{{ substr($v->title, 3, 3) }}</span>
                                        {{ $v->title }}
                                    </td>
                                    <td>￥{{ $v->price }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
                <div class="form-group">
                    <label for="settlement-total" class="col-sm-4 control-label"></label>
                    <div class="col-sm-2 col-md-2 col-lg-1">
                        <p class="form-control-static"><b>应付总额</b></p>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-2">
                        <p class="form-control-static">￥<span id="total-price">0</span></p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="settlement-status" class="col-sm-4 control-label"></label>
                    <div class="col-sm-2 col-md-2 col-lg-1">
                        <p class="form-control-static"><b>任务状态</b></p>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <label class="checkbox-inline">
                            <input type="radio" id="status" name="status" value="1"> 暂不发布
                        </label>
                        <label class="checkbox-inline">
                            <input type="radio" id="status" name="status" value="2" checked> 正常发布
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="agree" name="agree" checked> 我已阅读并同意 <a data-toggle="modal" data-target=".bs-example-modal-lg">《任务发布协议》</a>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-offset-4 col-sm-2">
                    <button type="submit" class="btn btn-primary btn-block" id="taskFormSubmit">保存</button>
                </div>
            </div>
            {{-- /结算清单 --}}
        </form>
    </div>
</div>
