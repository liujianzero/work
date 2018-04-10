<?php
/**
 * 商城-后台基类控制器
 */

namespace App\Http\Controllers;

use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\UserUrlModel;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Theme;

class AgentAdminController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //主题obj
    public $theme;

    public function __construct()
    {
        $this->theme = $this->initTheme('agent.layout');
    }

    /**
     * 初始化主题
     *
     * @param string $layout
     * @param string $theme
     * @return mixed
     */
    public function initTheme($layout = 'default', $theme = 'default')
    {
        return Theme::uses($theme)->layout($layout);
    }
}
