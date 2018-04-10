<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Theme;

class AgentAdminController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // 主题obj
    public $theme;

    public function __construct()
    {
        $this->theme = $this->initTheme('agent.layout');
    }

    // 初始化主题
    public function initTheme($layout = 'default', $theme = 'default')
    {
        return Theme::uses($theme)->layout($layout);
    }
}
