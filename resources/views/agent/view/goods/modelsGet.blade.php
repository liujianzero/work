@foreach ($list as $v)
    <div class="col-md-2 col-sm-3 col-xs-4">
        <div class="thumbnail">
            <a href="javascript:addGoods('{{ $v->id }}', '{{ $v->title }}');" class="no-pjax">
                <img src="@if (file_exists($v->upload_cover_image)){{ url($v->upload_cover_image) }}@elseif(file_exists($v->cover_img)){{ url($v->cover_img) }}@else{!! Theme::asset()->url('images/folder_no_cover.png') !!}@endif">
            </a>
            <div class="caption">
                <h3 data-toggle="tooltip" data-placement="top" title="{{ $v->title }}">{{ cut_str($v->title, 6) }}</h3>
            </div>
        </div>
    </div>
@endforeach