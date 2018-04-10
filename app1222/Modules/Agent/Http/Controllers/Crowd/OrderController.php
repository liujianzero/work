<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class OrderController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'order');
    }

    /**
     * 订单
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('订单');
        return $this->theme->scope($this->prefix . '.order.index', $view)->render();
    }
}
