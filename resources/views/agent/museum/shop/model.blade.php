@foreach ($list as $v)
    <div class="col-sm-2">
        <div class="thumbnail">
            <a href="javascript:void(0);" class="no-pjax" data-id="{{ $v->id }}" data-title="{{ $v->title }}" onclick="selectModels(this)">
                <img src="@if (file_exists($v->upload_cover_image)){{ "/$v->upload_cover_image" }}@elseif(file_exists($v->cover_img)){{ "/$v->cover_img" }}@else{!! Theme::asset()->url('images/folder_no_cover.png') !!}@endif">
            </a>
            <div class="caption">
                <h3 data-toggle="tooltip" data-placement="top" title="{{ $v->title }}">{{ cut_str($v->title, 4) }}</h3>
            </div>
        </div>
    </div>
@endforeach