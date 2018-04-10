<div id="attr" class="container">
    <form class="form-horizontal" id="addAttrForm">
        <div class="row">
            <div class="form-group">
                <label for="name" class="col-sm-3 control-label"><span class="need">*</span> 属性名称</label>
                <div class="col-sm-8">
                    <input id="name" name="name" type="text" class="form-control"/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="goods_type_id" class="col-sm-3 control-label"><span class="need">*</span> 所属属性类型</label>
                <div class="col-sm-8">
                    <select id="goods_type_id" name="goods_type_id" class="form-control">
                        <option value="">请选择</option>
                        @foreach ($type as $k => $v)
                            <option value="{{ $k }}" @if($k == $pid) selected @endif>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="name" class="col-sm-3 control-label"><span class="need">*</span> 属性值的录入方式</label>
                <div class="col-sm-8">
                    <label class="radio-inline">
                        <input type="radio" name="input_type" value="manual"> 手工录入
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="input_type" value="list" checked> 从下面的列表中选择<span style="color: red">（一行代表一个可选值）</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label for="value" class="col-sm-3 control-label">可选值列表</label>
                <div class="col-sm-8">
                    <textarea id="value" name="value" class="form-control" rows="6"></textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-8 col-sm-offset-3">
                <button id="addAttrSubmit" type="submit" class="btn btn-primary btn-block">提交</button>
            </div>
        </div>
    </form>
</div>