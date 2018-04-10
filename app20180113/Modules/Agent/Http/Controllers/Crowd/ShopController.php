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

    // 店铺
    public function index()
    {
        $view = [];
        $this->theme->setTitle('店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.index', $view)->render();
    }

    // 店铺-选择模板
    public function selectTemplate()
    {
        $view = [];
        $this->theme->setTitle('选择模板-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.selectTemplate', $view)->render();
    }

    // 店铺-购买模板
    public function buyTemplate()
    {
        $view = [];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.buyTemplate', $view)->render();
    }
}
