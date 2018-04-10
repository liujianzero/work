<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class GoodsController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'goods');
    }

    /**
     * 商品
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('商品');
        return $this->theme->scope($this->prefix . '.goods.index', $view)->render();
    }

    /**
     * 商品-添加商品
     */
    public function addGoods()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('添加商品-商品');
        return $this->theme->scope($this->prefix . '.goods.addGoods', $view)->render();
    }

    /**
     * 商品-分销商城
     */
    public function distributionGoods()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('分销商城-商品');
        return $this->theme->scope($this->prefix . '.goods.distribution', $view)->render();
    }
}
