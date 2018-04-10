<form id="addFrom">
    <h4>新增收货地址</h4>
    <div class="fill clearfix">
        <div class="row content">
            <div class="col-sm-10">
                <div class="row">
                    <div id="validate-error-msg" class="col-sm-8 col-sm-offset-2">

                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">所在地区：</span>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="country"></label>
                            <select id="country" name="country" required class="form-control sect1">
                                <option value="1">中国大陆</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="province"></label>
                            <select id="province" name="province" onchange="checkprovince(this)" required class="form-control sect2">
                                <option value="">请选择</option>
                                @foreach($province as $v)
                                    <option value="{{ $v['id'] }}">{{ $v['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="city"></label>
                            <select id="city" name="city" onchange="checkcity(this)" required class="form-control sect2">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="area"></label>
                            <select id="area" name="area" required class="form-control sect2">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">详细地址：</span>
                    </div>
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label class="sr-only" for="address"></label>
                            <textarea id="addresses" name="address" rows="3" required class="form-control" placeholder="例如街道名称，门牌名称，楼层和房间号等信息"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">邮政编码：</span>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label class="sr-only" for="zip_code"></label>
                            <input type="text" class="inp1 form-control" id="zip_code" name="zip_code" required placeholder="邮政编码，不清楚请填000000"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">收货人：</span>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label class="sr-only" for="consignee"></label>
                            <input type="text" class="inp1 form-control" id="consignee" name="consignee" required placeholder="收货人"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">手机号码：</span>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="mobile_prefix_id"></label>
                            <select id="mobile_prefix_id" name="mobile_prefix_id" required class="form-control sect3">
                                @foreach($prefix as $v)
                                    <option value="{{ $v['id'] }}">{{ $v['country'] }} +{{ $v['prefix'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label class="sr-only" for="mobile"></label>
                            <input type="text" id="mobile" name="mobile" required class="inp1 form-control" placeholder="手机号码"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <span class="txt-area">固定电话：</span>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label class="sr-only" for="tel_prefix_id"></label>
                            <select id="tel_prefix_id" name="tel_prefix_id" required class="form-control sect3">
                                @foreach($prefix as $v)
                                    <option value="{{ $v['id'] }}">{{ $v['country'] }} +{{ $v['prefix'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label class="sr-only" for="tel">固定电话</label>
                            <div class="input-group" id="tel">
                                <input type="text" name="tel_area_code" class="inp1 form-control" placeholder="区号"/>
                                <div class="input-group-addon input-middle">-</div>
                                <input type="text" name="tel" class="inp1 form-control" placeholder="固定电话"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <div class="checkbox">
                            <label for="is_default">
                                <input id="is_default" name="is_default" type="checkbox" value="Y" checked="checked"/> 默认地址
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-right">
                        <div class="btn-save">
                            <button id="formSubmit" type="submit">保存</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>