<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Http\Controllers\AgentAdminController;
use App\Modules\User\Model\StoreConfig;
use Illuminate\Support\Facades\Session;

class AdminController extends AgentAdminController
{
    public $flag = '';
    public $module = 'agent';
    public $prefix = '';
    public $store = null;

    public function __construct()
    {
        parent::__construct();
        $this->store = Session::get('agentAdmin');
        $flag = $this->store->flag;
        $this->theme->set('module', $this->module);
        $this->theme->set('flag', $flag);
        $this->flag = $flag;
        $this->prefix = $this->module . '.' . $this->flag;
        $this->theme->set('route_prefix', $this->prefix);
        $this->theme->set('dir_prefix', $this->module . '/' . $this->flag);
        $this->theme->set('menu', $this->menuList());
        StoreConfig::storeOpenStatus($this->store->id);
    }

    // 后台菜单
    public function menuList()
    {
        $list = [
            [
                'name' => '概况',
                'icon' => 'gaikuang',
                'href' => $this->prefix . '.index',
                'active' => 'survey'
            ],
            [
                'name' => '店铺',
                'icon' => 'store',
                'href' => $this->prefix . '.shop.index',
                'active' => 'shop'
            ],
            [
                'name' => '商品',
                'icon' => 'xinxi',
                'href' => $this->prefix . '.goods.index',
                'active' => 'goods'
            ],
            [
                'name' => '订单',
                'icon' => 'dingdan',
                'href' => $this->prefix . '.order.index',
                'active' => 'order'
            ],
            [
                'name' => '客户',
                'icon' => 'kehu',
                'href' => $this->prefix . '.customer.index',
                'active' => 'customer'
            ],
            [
                'name' => '数据',
                'icon' => 'data-one',
                'href' => $this->prefix . '.data.index',
                'active' => 'data'
            ],
            [
                'name' => '资产',
                'icon' => 'zichan',
                'href' => $this->prefix . '.property.index',
                'active' => 'property'
            ],
            /*[
                'name' => '营销',
                'icon' => 'yingxiao',
                'href' => $this->prefix . '.marketing.index',
                'active' => 'marketing'
            ],*/
            [
                'name' => '设置',
                'icon' => 'shezhi',
                'href' => $this->prefix . '.setup.index',
                'active' => 'setup'
            ]
        ];

        return $list;
    }
}
