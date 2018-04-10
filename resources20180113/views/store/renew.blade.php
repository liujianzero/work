<form class="form-horizontal" target="_blank" onsubmit="layer.closeAll();" style="width: 95%;margin-top: 20px;">
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label">续费店铺</label>
    <div class="col-sm-7">
      <p class="form-control-static">{{ $info->store_name }}</p>
    </div>
  </div>
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label">到期时间</label>
    <div class="col-sm-7">
      <p class="form-control-static">{{ $info->expire_at or '已过期' }}</p>
    </div>
  </div>
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label">续费时长</label>
    <div class="col-sm-7">
      @foreach ($years as $k => $v)
          <label class="radio-inline" @if ($k == $first) style="padding-left: 0;" @endif>
            <input type="radio" name="type_id" id="type_id-{{ $k }}" value="{{ $k }}" data-id="{{ $info->id }}" data-price="{{ $v }}" @if ($k == $first) checked @endif> {{ $k }} 年
          </label>
      @endforeach
    </div>
  </div>
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label">支付金额</label>
    <div class="col-sm-7">
      <p class="form-control-static">￥<span id="renew-cash">{{ $years[$first] }}</span></p>
    </div>
  </div>
  <div class="form-group">
    <div class="col-sm-offset-3 col-sm-7">
      <a href="/member/bounty/{{ $info->id }}/store_renew/{{ $first }}" class="btn btn-primary" id="goto-store-renew">立即购买</a>
    </div>
  </div>
</form>
