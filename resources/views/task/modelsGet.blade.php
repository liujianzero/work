@foreach ($list as $v)
    <div class="col-xs-12 col-sm-2">
        <div class="thumbnail">
            <a href="javascript:void(0);" onclick="selectModels(this, '{{ $v->id }}', '{{ $v->title }}');">
                <img src="@if (file_exists($v->upload_cover_image)){{ url($v->upload_cover_image) }}@elseif(file_exists($v->cover_img)){{ url($v->cover_img) }}@else{!! Theme::asset()->url('images/folder_no_cover.png') !!}@endif">
            </a>
            <div class="caption">
                <span class="hidden-xs" title="{{ $v->title }}">{{ cut_str($v->title, 10) }}</span>
                <span class="visible-xs-block text-center" title="{{ $v->title }}">{{ cut_str($v->title, 15) }}</span>
            </div>
        </div>
    </div>
@endforeach