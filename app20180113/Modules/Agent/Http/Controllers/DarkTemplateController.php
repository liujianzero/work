<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Http\Controllers\AgentAdminController;
use App\Modules\Agent\Model\StorePage;
use App\Modules\Agent\Model\StoreTemplate;
use App\Modules\User\Model\DistrictModel;
use Illuminate\Http\Request;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\StoreConfig;
use Illuminate\Support\Facades\Route;

class DarkTemplateController extends AgentAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('agent.home');
    }

    // 展示自定义页面
    public function show($store = null, $page = null)
    {
        if (!$store || ($store && !StoreConfig::storeOpenStatus($store)) || !check_wap()) {
            abort(404);
        }
        $info = StorePage::from('store_pages as sp')
            ->select([
                'sp.*',
                'sc.store_name',
                'st.flag',
            ])
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'sp.store_id')
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sp.store_id', $store);
        if ($page) {
            $info->where('sp.page', $page);
        }
        $info = $info->oldest('page')->first();
        if ($info) {
            $info->top = json_decode($info->top);
            $info->body = json_decode($info->body);
            $info->bottom = json_decode($info->bottom);
            $info->module = 'agent';
            $this->theme->setTitle($info->page_name . ' - ' . $info->store_name);
            return $this->theme->scope("{$info->module}.{$info->flag}.pages.show", compact('info'))->render();
        } else {
            return redirect()->route('agent.shop.default', $store);
        }
    }

    //博物馆spring模板-首页
    public function dark(Route $route, $id)
    {
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = Route::currentRouteAction();
        list($controller, $action) = explode('@', $route);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $action) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $models = ModelsContentModel::select('id', 'title', 'content', 'transaction_mode', 'cover_img', 'upload_cover_image', 'create_time', 'price', 'is_goods')
                ->where('uid', $info->store_id)
                ->where('is_private', 0)
                ->where('is_on_sale', 'Y')
                ->orderBy('transaction_mode', 'desc')
                ->paginate(30);
            $view = [
                'id' => $id,
                'info' => $info,
                'models' => $models,
                'count' => $models->count(),
            ];
            $this->initTheme('agent.default_content');
            $this->theme->setTitle($info['store_name']);
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.index", $view)->render();
//            return $this->theme->scope("{$info->module}.{$info->flag}.shop.spring_model", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆-个人中心
    public function personal(Request $request, $id)
    {
        $this->initTheme('agent.default_content');
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $view = [
                'id' => $id,
                'info' => $info,
            ];
            $this->theme->setTitle('个人中心');
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.personal", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆-简介
    public function summary(Request $request, $id)
    {
        $this->initTheme('agent.default_content');
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $view = [
                'info' => $info,
                'name' => $info->store_name,
                'desc' => $info->store_desc,
            ];
            $this->theme->set('module', $info->module);
            $this->theme->setTitle('简介');
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.summary", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆-地址
    public function address(Request $request, $id)
    {
        $this->initTheme('agent.default_content');
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $province = DistrictModel::where('id', $info->province)->pluck('name');
            $city = DistrictModel::where('id', $info->city)->pluck('name');
            $area = DistrictModel::where('id', $info->area)->pluck('name');
            $view = [
                'info' => $info,
                'province' => $province,
                'city' => $city,
                'area' => $area,
                'detail' => $info->address,
            ];
            $this->theme->setTitle('地址');
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.addr", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆-订单
    public function order(Request $request, $id)
    {
        $this->initTheme('agent.default_content');
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $view = [
                'info' => $info,
                'id'   => $id,
            ];
            $this->theme->setTitle('预订');
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.order", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆-订单详情
    public function orderDetail(Request $request, $id)
    {
        $this->initTheme('agent.default_content');
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $id)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($id))) {
                abort('404');
            }
            $info->module = 'agent';
            $view = [
                'info' => $info,
                'id'   => $id,
            ];
            $this->theme->setTitle('预订');
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.order_detail", $view)->render();
        } else {
            abort('404');
        }
    }

    //博物馆商品-详情页
    public function goodsContent(Request $request, $id)
    {
        $models = ModelsContentModel::where('id', $id)->first();
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $models->uid)
            ->first();
        $route = $request->route()->getPrefix();
        $str = substr($route, 11);
        $theme = StoreTemplate::where('id', $info['template_id'])->first();
        if ($theme['theme'] == $str) {
            if (!$info || ($info && !StoreConfig::storeOpenStatus($models->uid))) {
                abort('404');
            }
            $info->module = 'agent';
            $view = [
                'id' => $id,
                'info' => $info,
                'models' => $models,
            ];
            $this->theme->set('module', $info->module);
            $this->initTheme('agent.default_content');
            $this->theme->setTitle($models['title']);
            return $this->theme->scope("{$info->module}.{$info->flag}.shop.dark_template.content", $view)->render();
        } else {
            abort('404');
        }
    }

}
