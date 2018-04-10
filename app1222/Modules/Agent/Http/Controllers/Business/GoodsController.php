<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\GoodsCategory;
use App\Modules\User\Model\ModelsContentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GoodsController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'goods');
    }

    /**
     * 商品
     */
    public function index(Request $request)
    {
        /* 请求的条件 */
        $merge = $request->all();
        /* 列表信息 */
        $list = ModelsContentModel::where('is_goods', '1')
            ->where('uid', $this->store->id);
        if ($title = $request->get('title')) {
            $list = $list->where('title', 'LIKE', "%{$request->get('title')}%");
        }
        if ($goods_cat_id = $request->get('goods_cat_id')) {
            $list = $list->where('goods_cat_id', $goods_cat_id);
        }
        // 当前匹配条数
        $count = $list->count();
        // 每页显示的条数
        $perPage = $request->get('perPage') ? $request->get('perPage') : '10';
        $list  = $list->orderBy('create_time', 'DESC')->paginate($perPage);
        /* 筛选条件 */
        // 选项卡
        $screen = [
            [
                'txt' => '在售中',
                'name' => 'screen',
                'value' => 'Y'
            ],
            [
                'txt' => '已售罄',
                'name' => 'screen',
                'value' => '-1'
            ],
            [
                'txt' => '仓库中',
                'name' => 'screen',
                'value' => 'N'
            ]
        ];
        // 每页显示的条数
        $perPageList = [
            [
                'name' => '每页显示10条数据',
                'value' => '10'
            ],
            [
                'name' => '每页显示20条数据',
                'value' => '20'
            ],
            [
                'name' => '每页显示30条数据',
                'value' => '30'
            ],
            [
                'name' => '每页显示40条数据',
                'value' => '40'
            ],
            [
                'name' => '每页显示50条数据',
                'value' => '50'
            ]
        ];
        // 商品分组
        $cat = GoodsCategory::getList($this->store->id);
        /* 数据赋值 */
        $view = [
            'list' => $list,
            'count' => $count,
            'perPage' => $perPage,
            'screen' => $screen,
            'perPageList' => $perPageList,
            'merge' => $merge,
            'cat' => $cat
        ];
        $this->theme->setTitle('商品');
        return $this->theme->scope($this->prefix . '.goods.index', $view)->render();
    }

    /**
     * 商品-添加商品分组
     */
    public function catStore(Request $request)
    {
        if ($this->store) {
            $this->validate($request, [
                'cat_name' => 'required',
                'parent_id' => [
                    'regex:/^[\d]+$/'
                ],
                'sort_order' => [
                    'regex:/^[\d]+$/'
                ]
            ], [
                'cat_name.required' => '请填写分组名称',
                'parent_id.regex' => '父级分组必须为整数',
                'sort_order.regex' => '推荐排序必须为整数'
            ]);
            $data = $request->all();
            $data['store_id'] = $this->store->id;
            $status = GoodsCategory::create($data);
            if ($status) {
                Cache::forget('store_cat_list@' . $this->store->id);
                return response()->json(['code' => '1000', 'msg' => '新建分组成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '新建分组失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 商品-添加商品
     */
    public function addGoods()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('添加商品-商品');
        return $this->theme->scope($this->prefix . '.goods.addGoods', $view)->render();
    }

    /**
     * 商品-分销商城
     */
    public function distributionGoods()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('分销商城-商品');
        return $this->theme->scope($this->prefix . '.goods.distribution', $view)->render();
    }
}
