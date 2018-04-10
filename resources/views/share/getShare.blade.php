<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="width=device-width,user-scalable=no" name="viewport">
    <title>自定义百度分享URL和图标样式</title>
    <link rel="stylesheet" href="/themes/default/assets/css/share/style.css">
    <script src="/E2.0_vr/js/libs/jquery-2.1.1.js"></script>
</head>
<body>
<style type="text/css">
    .bdsharebuttonbox a{width:60px!important; height:60px!important; margin:0 auto 10px!important; float:none!important; padding:0!important; display:block;}
    .bdsharebuttonbox a img{width:60px; height:60px;}
    .bdsharebuttonbox .bds_tsina{background:url('/themes/default/assets/images/share/gbRes_6.png') no-repeat center center/60px 60px;}
    .bdsharebuttonbox .bds_qzone{background:url('/themes/default/assets/images/share/gbRes_4.png') no-repeat center center/60px 60px;}
    .bdsharebuttonbox .bds_tqq{background:url('/themes/default/assets/images/share/gbRes_5.png') no-repeat center center/60px 60px;}
    .bdsharebuttonbox .bds_weixin{background:url('/themes/default/assets/images/share/gbRes_2.png') no-repeat center center/60px 60px;}
    .bdsharebuttonbox .bds_sqq{background:url('/themes/default/assets/images/share/gbRes_3.png') no-repeat center center/60px 60px;}
    .bdsharebuttonbox .bds_renren{background:url('/themes/default/assets/images/share/gbRes_1.png') no-repeat center center/60px 60px;}
    .bd_weixin_popup .bd_weixin_popup_foot{position:relative; top:-12px;}
</style>
<div class="gb_resLay">
    <div class="gb_res_t"><span>分享到</span><i></i></div>
    <div class="bdsharebuttonbox">
        <ul class="gb_resItms">
            <li><a title="分享到微信" href="#" class="bds_weixin" data-cmd="weixin" data-url="{{ $url }}"></a>微信好友</li>
            <li><a title="分享到QQ好友" href="#" class="bds_sqq" data-cmd="sqq" data-url="{{ $url }}"></a>QQ好友</li>
            <li><a title="分享到QQ空间" href="#" class="bds_qzone" data-cmd="qzone" data-url="{{ $url }}"></a>QQ空间</li>
            <li><a title="分享到腾讯微博" href="#" class="bds_tqq" data-cmd="tqq" data-url="{{ $url }}"></a>腾讯微博</li>
            <li><a title="分享到新浪微博" href="#" class="bds_tsina" data-cmd="tsina" data-url="{{ $url }}"></a>新浪微博</li>
            <li><a title="分享到人人网" href="#" class="bds_renren" data-cmd="renren" data-url="{{ $url }}"></a>人人网</li>
        </ul>
    </div>
</div>
<script type="text/javascript">
    //全局变量，动态的文章ID
    var ShareURL = "";
    //绑定所有分享按钮所在A标签的鼠标移入事件，从而获取动态ID
    $(function () {
        $(".bdsharebuttonbox a").mouseover(function () {
            ShareURL = $(this).attr("data-url");
        });
    });

    /*
     * 动态设置百度分享URL的函数,具体参数
     * cmd为分享目标id,此id指的是插件中分析按钮的ID
     *，我们自己的文章ID要通过全局变量获取
     * config为当前设置，返回值为更新后的设置。
     */
    function SetShareUrl(cmd, config) {
        if (ShareURL) {
            config.bdUrl = ShareURL;
        }
        return config;
    }

    //插件的配置部分，注意要记得设置onBeforeClick事件，主要用于获取动态的文章ID
    window._bd_share_config = {
        "common": {
            onBeforeClick: SetShareUrl, "bdSnsKey": {}, "bdText": ""
            , "bdMini": "2", "bdMiniList": false, "bdPic": "","bdSign": "off","bdStyle": "0", "bdSize": "24"
        }, "share": {}
    };
    //插件的JS加载部分
    with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?cdnversion='+~(-new Date()/36e5)];
</script>
</body>
</html>