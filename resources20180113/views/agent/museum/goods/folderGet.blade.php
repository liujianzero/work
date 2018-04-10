@foreach ($list as $v)
    <div class="col-md-2 col-sm-3 col-xs-4">
        <div class="thumbnail">
            <a href="javascript:selectGoods({{ $v->id }});" class="no-pjax">
                <img src="@if (! empty($v->cover_img) && file_exists($v->cover_img)){{ url($v->cover_img) }}@else{!! Theme::asset()->url('images/folder_no_cover.png') !!}@endif">
            </a>
            <div class="caption">
                <h3 data-toggle="tooltip" data-placement="top" title="{{ $v->name }}">{{ cut_str($v->name, 6) }}</h3>
            </div>
        </div>
    </div>
@endforeach