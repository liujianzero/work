<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class SurveyController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'survey');
    }

    /**
     * 概况
     */
    public function index()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('概况');
        return $this->theme->scope($this->prefix . '.survey.index', $view)->render();
    }
}
