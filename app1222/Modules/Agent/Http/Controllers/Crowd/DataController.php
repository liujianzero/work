<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class DataController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'data');
    }

    /**
     * 数据
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('数据');
        return $this->theme->scope($this->prefix . '.data.index', $view)->render();
    }
}
