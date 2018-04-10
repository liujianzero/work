<?php

namespace App\Modules\Agent\Http\Controllers\Museum;

use App\Modules\Agent\Http\Controllers\AdminController;
use Illuminate\Http\Request;

class MarketingController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'marketing');
    }

    // 营销
    public function index()
    {
        $view = [];
        $this->theme->setTitle('营销');
        return $this->theme->scope($this->prefix . '.marketing.index', $view)->render();
    }
}
