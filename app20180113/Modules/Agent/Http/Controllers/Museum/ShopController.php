<?php

namespace App\Modules\Agent\Http\Controllers\Museum;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\StorePage;
use App\Modules\Agent\Model\StoreTemplate;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\StoreConfig;
use Illuminate\Http\Request;

class ShopController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'shop');
    }

    // 店铺
    public function index()
    {
        $data = $this->store;
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $data->id)
            ->first();
        $info->module = 'agent';
        $models = ModelsContentModel::select('id', 'title', 'content', 'transaction_mode', 'cover_img', 'upload_cover_image', 'create_time', 'price', 'is_goods')
            ->where('uid', $data->id)
            ->where('is_private', 0)
            ->where('is_on_sale', 'Y')
            ->orderBy('transaction_mode', 'desc')
            ->paginate(30);
        $template = StoreTemplate::where('id', $info['template_id'])->pluck('theme');
        $view = [
            'id' => $data->id,
            'info' => $info,
            'models' => $models,
            'route' => $template,
        ];
        $this->theme->setTitle('店铺');
        return $this->theme->scope("{$info->module}.{$info->flag}.shop.index", $view)->render();
    }

    // 店铺-选择模板
    public function selectTemplate()
    {
        $data = $this->store;
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $data->id)
            ->first();
        $info->module = 'agent';
        $template = StoreTemplate::from('store_templates as sc')
            ->select('sc.*')
//            ->orderBy('sc.id', 'desc')
            ->get();
        $view = [
            'info' => $info,
            'template' => $template,
        ];
        $this->theme->setTitle('选择模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.selectTemplate', $view)->render();
    }

    // 店铺-购买模板
    public function buyTemplate()
    {
        $view = [];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.buyTemplate', $view)->render();
    }

    // 店铺自定义模板
    public function customTemplate()
    {
        $view = [
            'uid' => $this->store->id,
        ];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.customTemplate', $view)->render();
    }

    //
    public function receiveCustomData(Request $request)
    {
        $data = $_POST;
        return \GuzzleHttp\json_encode($data);
    }

    // 店铺-保存发布模板(自定义)
    public function save(Request $request)
    {
        $data = $request->except('_token');
        $page = [];
        $top = null;
        $tmp = $body = [];
        $bottom = null;
        $time = date('Y-m-d H:i:s');
        foreach ($data['widget'] as $v) {
            switch (trim($v['key'])) {
                case 'top-nav':
                    $top = $v;
                    break;
                case 'bottom-nav':
                    $bottom = $v;
                    break;
                default:
                    $tmp[] = $v;
                    break;
            }
        }
        $sort = array_pluck($tmp, 'order');
        sort($sort);
        foreach ($sort as $v) {
            foreach ($tmp as $item) {
                if ($item['order'] == $v) {
                    $body[] = $item;
                }
            }
        }
        $page[] = [
            'group' => '',
            'page' => 1,
            'store_id' => $this->store->id,
            'top' => json_encode($top),
            'body' => json_encode($body),
            'bottom' => json_encode($bottom),
            'created_at' => $time,
            'updated_at' => $time,
        ];
        StorePage::insert($page);
        dump($page);
    }


}
