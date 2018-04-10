{{-- 电脑端信息 --}}
<style>
    /* layer */
    body .layui-layer-content{
        background: #1A1A1B;
        color: #fff;
        border-radius: 3px;
    }
    /* 整体样式 */
    .main{
        width: 800px;
        padding: 20px;
    }
</style>
<div class="row main">
    <div class="row">
        <div class="col-sm-3 text-right">
                <span>
                    商品名称
                </span>
        </div>
        <div class="col-sm-9">
            {{ $info->title }}
        </div>
    </div>
</div>