<?php

namespace App\Modules\Agent\Http\Controllers\Material;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class SetupController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'setup');
    }

    /**
     * 设置
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('设置');
        return $this->theme->scope($this->prefix . '.setup.index', $view)->render();
    }
}
