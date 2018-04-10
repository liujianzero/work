<?php

namespace App\Modules\Agent\Http\Controllers\View;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class PropertyController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'property');
    }

    /**
     * 资产
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('资产');
        return $this->theme->scope($this->prefix . '.property.index', $view)->render();
    }
}
