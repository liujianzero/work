<div class="top-div" style="height: {{ $top_height }};">
    @if($top && $top->key == 'top-nav')
        <div class="widget-content" style="position: absolute;top: 0;left: 0;"
             data-type="widget-topnav" onmouseover='addNotice(this)'
             onmouseleave='delNotice(this)'>
            <div class="top-t">
                <div class="topnav">
                    <div class="top-text">
                        <a class="back" href="javascript:void(0);"><span>{{ $top->back }}</span></a>
                    </div>
                    <div class="first">
                        <a href="javascript:void(0);">{{ $top->btn }}</a>
                    </div>
                </div>
            </div>
            <input type="hidden" name="widget[{{ $top->increment }}][page]" value="{{ $top->page }}">
            <input type="hidden" name="widget[{{ $top->increment }}][key]" value="{{ $top->key }}">
            <input type="hidden" name="widget[{{ $top->increment }}][order]" value="{{ $top->order }}">
            <input type="hidden" name="widget[{{ $top->increment }}][increment]" value="{{ $top->increment }}">
            <input type="hidden" name="widget[{{ $top->increment }}][btn]" value="{{ $top->btn }}">
            <input type="hidden" name="widget[{{ $top->increment }}][back]" value="{{ $top->back }}">
            <input type="hidden" name="widget[{{ $top->increment }}][href]" value="{{ $top->href }}">
        </div>
    @endif
</div>

<div class="body-div" style="height: {{ $body_height }};">
    @if ($body && count($body))
        @foreach ($body as $item)
            @if($item->key == 'text')
                <div class="widget-content" data-type="widget-text"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class='btns'>
                            <p class="text-p"><a href="">{{ $item->value }}</a></p>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][value]" value="{{ $item->value }}">
                </div>
            @elseif($item->key == 'panorama')
                <div class="widget-content" data-type="widget-pic" onmouseover='addNotice(this)'
                     onmouseleave='delNotice(this)'>
                    <div>
                        <div class='content-head'>
                            <iframe id="iframe" scrolling='no' src="{{ $item->src }}"></iframe>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][src]" value="{{ $item->src }}">
                </div>
            @elseif($item->key == 'button')
                <div class="widget-content" data-type="widget-btn" onmouseover='addNotice(this)'
                     onmouseleave='delNotice(this)'>
                    <div>
                        <div class='btns'>
                            <p class='btn-p'><a href="#">{{ $item->text }}</a></p>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][text]" value="{{ $item->text }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][href]" value="{{ $item->href }}">
                </div>
            @elseif($item->key == 'category')
                <div class="widget-content" data-type="widget-cat" onmouseover='addNotice(this)'
                     onmouseleave='delNotice(this)'>
                    <div>
                        <div class="classfly">
                            <ul class="row">
                                @foreach($item->li as $k => $v)
                                    <li class="active col-sm-3 col-xs-3">
                                        <span class="img"><img src="{{ $v->img }}"/></span>
                                        <p class="groom" data-guid="{{ $k }}"><a href="#">{{ $v->text }}</a></p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    @foreach($item->li as $k => $v)
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][text]" value="{{ $v->text }}">
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][img]" value="{{ $v->img }}">
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][href]" value="{{ $v->href }}">
                    @endforeach
                </div>
            @elseif($item->key == 'personal')
                <div class="widget-content" data-type="widget-pcenter"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class="pcenter">
                            <div class="banner">
                                <div class="thumbnail">
                                    <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/pic13.png') }}"/>
                                    <p class="p1">昵称：Viktor</p>
                                    <p class="p2">等级：星球之光</p>
                                </div>
                            </div>
                            <div class="container">
                                <div class="ull">
                                    <ul class="row">
                                        <li class="col-xs-12 col-sm-12 distributor" @if($item->distributor == 'off') style="display: none;" @endif>
                                            <a href="#">
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/pic8.png') }}"
                                                     class="logo"/>
                                                <span>申请成为分销商</span>
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/arrow.png') }}"
                                                     class="arrow pull-right"/>
                                            </a>
                                        </li>
                                        <li class="col-xs-12 col-sm-12 orders" @if($item->orders == 'off') style="display: none;" @endif>
                                            <a href="#">
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/pic12.png') }}"
                                                     class="logo"/>
                                                <span>全部订单</span>
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/arrow.png') }}"
                                                     class="arrow pull-right"/>
                                            </a>
                                        </li>
                                        <li class="col-xs-12 col-sm-12 cart" @if($item->cart == 'off') style="display: none;" @endif>
                                            <a href="#">
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/pic9.png') }}"
                                                     class="logo"/>
                                                <span>购物车</span>
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/arrow.png') }}"
                                                     class="arrow pull-right"/>
                                            </a>
                                        </li>
                                        <li class="col-xs-12 col-sm-12 collection" @if($item->collection == 'off') style="display: none;" @endif>
                                            <a href="#">
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/pic10.png') }}"
                                                     class="logo"/>
                                                <span>我的收藏</span>
                                                <img src="{{ Theme::asset()->url(Theme::get('dir_prefix') . '/shop/images/arrow.png') }}"
                                                     class="arrow pull-right"/>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][distributor]" value="{{ $item->distributor }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][orders]" value="{{ $item->orders }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][cart]" value="{{ $item->cart }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][collection]" value="{{ $item->collection }}">
                </div>
            @elseif($item->key == 'title')
                <div class="widget-content" data-type="widget-addtitle"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class="addtitle clearfix">
                            <div class="col-xs-12 padr">
                                <h4 class="fl">{{ $item->text }}</h4>
                                <div class="addright fr">
                                    <span>1000</span>
                                    <i class="fa fa-heart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][text]" value="{{ $item->text }}">
                </div>
            @elseif($item->key == 'comment')
                <div class="widget-content" data-type="widget-comment"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class="comment">
                            <p><span class="text-c">0</span>条评论</p>
                            <div class="c-textarea">
                                <textarea name="" placeholder="我也来说几句..."></textarea>
                            </div>
                            <div class="c-btn">
                                <span class="com-btn">发布</span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                </div>
            @elseif($item->key == 'brief-introduction')
                <div class="widget-content" data-type="widget-titlelist"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div class="top-t">
                        <div class="titlelist">
                            <div class="row">
                                <div class="col-xs-12 pad">
                                    <div class="title">
                                        <h5 class="brief-title">{{ $item->title }}</h5>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-6 padr margint">
                                            <div class="border">
                                                <h6>级别：<span class="brief-level">{{ $item->level }}</span></h6>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 padr margint">
                                            <div class="border">
                                                <h6>单位：<span class="brief-company">{{ $item->company }}</span></h6>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 padr margint">
                                            <div class="border">
                                                <h6>类别：<span class="brief-cat">{{ $item->cat }}</span></h6>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 padr margint">
                                            <div class="border">
                                                <h6>年代：<span class="brief-year">{{ $item->year }}</span></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][title]" value="{{ $item->title }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][level]" value="{{ $item->level }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][company]" value="{{ $item->company }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][cat]" value="{{ $item->cat }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][year]" value="{{ $item->year }}">
                </div>
            @elseif($item->key == 'detailed-introduction')
                <div class="widget-content" data-type="widget-details"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div class="top-t">
                        <div class="details">
                            <div class="row mar">
                                <div class="col-xs-12 padr">
                                    <div class="title">
                                        <h5>详情介绍</h5>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12 padr">
                                            <p class="introduce">西汉,照明用具。这个造型美观的青铜羊灯,它塑造了一只憨态可掬跪卧状的山羊形象。羊形体肥硕,腹腔中空,四肢跪卧,昂首,口微张,目视前方,胡须下垂,双角向前卷曲</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][text]" value="{{ $item->text }}">
                </div>
            @elseif($item->key == 'mp3')
                <div class="widget-content" data-type="widget-mp3" onmouseover='addNotice(this)'
                     onmouseleave='delNotice(this)'>
                    <div>
                        <div class="mp3">
                            <div class="title">
                                <h5>词条播报</h5>
                            </div>
                            <audio id="audio-player" class="js-player" src="{{ $item->src }}" controls="controls" style="width: 100%;"></audio>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][src]" value="{{ $item->src }}">
                </div>
            @elseif($item->key == 'mp4')
                <div class="widget-content" data-type="widget-video"
                     onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class="video clearfix">
                            <div class="title">
                                <h5>相关视频</h5>
                            </div>
                            <div class="col-xs-12 padr">
                                <video id="video-player" src="{{ $item->src }}" width="100%;"></video>
                                <div id="controls">
                                    <div class="col-xs-1">
                                        <i class="fa fa-play"></i>
                                    </div>
                                    <div class="col-xs-3">
                                        <span class="hour">00</span>:<span class="minute">00</span>:<span class="second">00</span>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="progress">
                                            <div
                                                class="progress-bar video-progress progress-bar-striped active"
                                                role="progressbar" aria-valuenow="0"
                                                aria-valuemin="0" aria-valuemax="100"
                                                style="width: 0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][src]" value="{{ $item->src }}">
                </div>
            @elseif($item->key == 'image-list')
                <div class="widget-content" data-type="widget-piclist" onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div>
                        <div class="picture">
                            <ul class="row">
                                @foreach($item->li as $k => $v)
                                    <li class="col-sm-4 col-xs-4" data-guid="{{ $k }}">
                                        <a href="javascript:void(0);" class="link"><img class="img" src="{{ $v->img }}"/></a>
                                        <p class="textp">{{ $v->title }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    @foreach($item->li as $k => $v)
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][id]" value="{{ $v->id }}">
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][title]" value="{{ $v->title }}">
                        <input type="hidden" name="widget[{{ $item->increment }}][li][{{ $k }}][img]" value="{{ $v->img }}">
                    @endforeach
                </div>
            @elseif($item->key == 'image-introduction')
                <div class="widget-content" data-type="widget-imgs" onmouseover='addNotice(this)' onmouseleave='delNotice(this)'>
                    <div class="top-t">
                        <div class="imgs">
                            <div class="row mar">
                                <div class="col-xs-12 padr">
                                    <div class="title">
                                        <h5>相关图片</h5>
                                    </div>
                                    <div class="double_img">
                                        <img class="d_img1" data-guid="first" src="{{ $item->first }}" width="100%" height="100%"/>
                                        <img class="d_img2" data-guid="second" src="{{ $item->second }}" width="100%" height="100%"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="widget[{{ $item->increment }}][page]" value="{{ $item->page }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][key]" value="{{ $item->key }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][order]" value="{{ $item->order }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][increment]" value="{{ $item->increment }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][first]" value="{{ $item->first }}">
                    <input type="hidden" name="widget[{{ $item->increment }}][second]" value="{{ $item->second }}">
                </div>
            @endif
        @endforeach
    @endif
</div>

<div class="bottom-div" style="height: {{ $bottom_height }};">
    @if($bottom && $bottom->key == 'bottom-nav')
        <div class="widget-content" style="position: absolute;bottom: 0;left: 0;"
             data-type="widget-bottomnav" onmouseover='addNotice(this)'
             onmouseleave='delNotice(this)'>
            <div class="bottom-b">
                <div class="bottomnav">
                    <ul class="row">
                        @foreach($bottom->li as $k => $v)
                            <li class="col-sm-4 col-xs-4">
                                <span class="pic"><img src="{{ $v->img }}" alt="{{ $v->text }}"/></span>
                                <p class="textp" data-guid="{{ $k }}"><a href="{{ $v->href }}">{{ $v->text }}</a></p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <input type="hidden" name="widget[{{ $bottom->increment }}][page]" value="{{ $bottom->page }}">
            <input type="hidden" name="widget[{{ $bottom->increment }}][key]" value="{{ $bottom->key }}">
            <input type="hidden" name="widget[{{ $bottom->increment }}][order]" value="{{ $bottom->order }}">
            <input type="hidden" name="widget[{{ $bottom->increment }}][increment]" value="{{ $bottom->increment }}">
            @foreach($bottom->li as $k => $v)
                <input type="hidden" name="widget[{{ $bottom->increment }}][li][{{ $k }}][text]" value="{{ $v->text }}">
                <input type="hidden" name="widget[{{ $bottom->increment }}][li][{{ $k }}][img]" value="{{ $v->img }}">
                <input type="hidden" name="widget[{{ $bottom->increment }}][li][{{ $k }}][href]" value="{{ $v->href }}">
            @endforeach
        </div>
    @endif
</div>
