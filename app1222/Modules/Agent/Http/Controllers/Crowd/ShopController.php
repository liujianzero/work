<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class ShopController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'shop');
    }

    /**
     * 店铺
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('店铺');
        return $this->theme->scope($this->prefix . '.shop.index', $view)->render();
    }

    /**
     * 店铺-选择模板
     */
    public function selectTemplate()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('选择模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.selectTemplate', $view)->render();
    }

    /**
     * 店铺-购买模板
     */
    public function buyTemplate()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.buyTemplate', $view)->render();
    }
}
