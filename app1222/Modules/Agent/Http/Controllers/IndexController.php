<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Http\Controllers\AgentAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class IndexController extends AgentAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 根据店铺类型切换相应的后台
     */
    public function Index()
    {
        $admin = Session::get('agentAdmin');
        $flag = $admin->storeType->flag;
        return redirect()->route('agent.' . $flag . '.index');
    }
}