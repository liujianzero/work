<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class CustomerController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'customer');
    }

    /**
     * 客户
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('客户');
        return $this->theme->scope($this->prefix . '.customer.index', $view)->render();
    }
}
