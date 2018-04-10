<?php

namespace App\Http\Controllers;

use App\Modules\Manage\Model\ConfigModel;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Support\Facades\Route;
use Theme;
use App\Modules\Task\Model\TaskCateModel;
use Cache;

class BasicController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //主题obj
    public $theme;

    //面包屑
    public $breadcrumb;

    public function __construct()
    {
        $this->checkInstall();
        //获取主题配置
        $themeConfig = \CommonClass::getConfig('theme');
        //初始化主题
        if ($themeConfig) {
            $this->theme = $this->initTheme('default', $themeConfig);
        } else {
            $this->theme = $this->initTheme();
        }
        //前端多彩主题
        $skin_color_config = \CommonClass::getConfig('skin_color_config');
        if($skin_color_config)
        {
            $this->theme->set('color', $skin_color_config);
        }

        //站点配置信息
        $siteConfig = ConfigModel::getConfigByType('site');
        $this->theme->set('site_config',$siteConfig);

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

    /**
     * 设置后台面包屑模板
     *
     * @return mixed
     */
    public function manageBreadcrumb()
    {
        return $this->theme->breadcrumb()->setTemplate('
            <ul class="breadcrumb">
            @foreach ($crumbs as $i => $crumb)
                @if ($i != (count($crumbs) - 1))
                <li>
                <i class="ace-icon fa fa-tasks home-icon"></i>
                <a href="{{ $crumb["url"] }}">{{ $crumb["label"] }}</a>
                </li>
                @else
                <li class="active">{{ $crumb["label"] }}</li>
                @endif
            @endforeach
            </ul>
        ');
    }

    public function checkInstall()
    {
        if (!file_exists(base_path('kppw.install.lck'))){
            header('Location:' . \CommonClass::getDomain() . '/install');
            die('未检测到安装文件');
        }
    }
}
