<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Http\Controllers\AgentAdminController;
use App\Modules\Agent\Model\StoreCart;
use App\Modules\Agent\Model\StoreComment;
use App\Modules\Agent\Model\StoreGood;
use App\Modules\Agent\Model\StoreLearn;
use App\Modules\Agent\Model\StorePage;
use App\Modules\Agent\Model\StorePageDetail;
use App\Modules\Agent\Model\StoreSubject;
use App\Modules\Agent\Model\StoreTheme;
use App\Modules\Agent\Model\StoreThemePage;
use App\Modules\Agent\Model\StoreView;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\Attribute;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\GoodsAttribute;
use App\Modules\User\Model\GoodsStock;
use App\Modules\User\Model\GoodsType;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\StoreConfig;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PagesController extends AgentAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('agent.home');
    }

    public $store;

    // 展示自定义页面
    public function show(Request $request, $store = null, $page = null)
    {
        if (!$store) {
            abort(404);
        }
        if (!StoreConfig::storeOpenStatus($store)) {
            dd('店铺已关闭');
        }
//        if (!check_wap()) {
//            dd('请使用移动端访问');
//        }
        $shop = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $store)
            ->first();
        if ($shop->theme == 'diy') {
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
//			if (!$info) {
//                dd('该页面不存在');
//            }
            StoreView::view($request, $store);
            $info->top = json_decode($info->top);
            $tmp = json_decode($info->body);
            $temporary = [];
            foreach ($tmp as &$v) {
                switch ($v->key) {
                    case 'comment':
                        if (isset($temporary['comment'])) {
                            $v->total_comment = $temporary['comment'];
                        } else {
                            $v->total_comment = StoreComment::where('store_id', $info->store_id)->count();
                        }
                        break;
                    case 'study':
                        $ids = [];
                        foreach ($v->li as $order) {
                            $ids[] = $order;
                        }
                        $subject_tmp = StoreSubject::where('store_id', $info->store_id)
                            ->with('storeSubjectAnswers')
                            ->whereIn('id', $ids)
                            ->get()->toArray();
                        $subjects = [];
                        foreach ($subject_tmp as $key => $subject) {
                            $subjects[$subject['id']] = $subject;
                        }
                        $v->subjects = $subjects;
                        break;
                    default:
                        break;
                }
            }
            $info->body = $tmp;
            $info->bottom = json_decode($info->bottom);
            $info->module = 'agent';
            $this->theme->setTitle($info->page_name . ' - ' . $info->store_name);
            return $this->theme->scope("{$info->module}.{$info->flag}.pages.show", compact('info'))->render();
        } else {
            $info = StoreThemePage::from('store_theme_pages as stp')
                ->select([
                    'stp.*',
                    'st.flag as theme',
                ])
                ->leftJoin('store_themes as st', 'st.id', '=', 'stp.store_theme_id')
                ->where('st.flag', $shop->theme)
                ->where('st.store_type_id', $shop->store_type_id);
            if ($page) {
                $info->where('stp.page', $page);
            }
            $info = $info->first();
            if (!$info) {
                dd('该页面不存在');
            }
            $this->theme->setTitle($info->name);
            $shop->module = 'agent';
            $extra = $this->getThemeData($request, $shop, $info);
            $shop->dir = "{$shop->module}/{$shop->flag}/theme/{$info->theme}";
            return $this->theme->scope("{$shop->dir}/{$info->page}", compact('shop', 'info', 'extra'))->render();
        }
    }

    // 获取默认主题数据
    private function getThemeData($request, $shop, $info)
    {
        if ($shop->flag == 'museum') {
            switch ($info->page) {
                case 'index':
//                    $models = ModelsContentModel::where('uid', $shop->store_id)/*->limit(6)*/->get();
                    $models = StoreGood::where('store_id', $shop->store_id)
//                        ->where('models_id', $request['id'])
                        ->where('is_on_sale', 'Y')
                        ->get();
                    $page_detail = StorePageDetail::where('store_id', $shop->store_id)->first();
                    $extra = [
                        'models' => $models,
                        'page_detail' => $page_detail,
                    ];
                    break;
                case 'personal':
                    $extra = [];
                    break;
                case 'summary':
                    $page_detail = StorePageDetail::where('store_id', $shop->store_id)->first();
                    $extra = [
                        'page_detail' => $page_detail,
                    ];
                    break;
                case 'address':
                    $page_detail = StorePageDetail::where('store_id', $shop->store_id)->first();
                    $extra = [
                        'page_detail' => $page_detail,
                    ];
                    break;
                case 'order':
                    $extra = [];
                    break;
                case 'content':
                    //博物馆
//                    $models = ModelsContentModel::where('id', $request['id'])->first();//old与原作品绑定
                    $models = StoreGood::where('store_id', $shop->store_id)
                        ->where('models_id', $request['id'])
                        ->where('is_on_sale', 'Y')
                        ->first();//20180122更新:与原作品分离管理
                    if (! $models) {
                        dd('该商品不存在');
                    }
                    $more_goods = StoreGood::where('store_id', $shop->store_id)
                        ->where('is_on_sale', 'Y')
                        ->orderByRaw("RAND()")
                        ->take(3)
                        ->get();
                    //电子商务
                    $tmp = Attribute::where('goods_type_id', $models['goods_type_id'])->get();//商品的属性
                    $goods = GoodsAttribute::where('goods_id', $request['id'])->get();//商品属性值的详细信息
                    $attribute = [];// 属性
                    $spec = [];// 规格
                    $stock = [];//属性组合套餐
                    foreach ($tmp as $k => $v) {
                        if ($v['input_type'] == 'list') {
                            $list = explode(',', str_replace("\r\n", ',', $v['value']));//例：味道->酸甜，甘甜；颜色->紫色，灰白
                            $_tmp = [];
                            $_status = true;
                            foreach ($list as $item) {
                                foreach ($goods as $value) {
                                    if ($v['id'] == $value['attribute_id'] && $item == $value['attr_value']) {
                                        $_tmp[] = [//商品的属性信息（例：酸甜->￥1.00）
                                            'attr_id' => $value['attribute_id'],
                                            'id' => $value['id'],
                                            'name' => $value['attr_value'],
                                            'price' => $value['attr_price'],
                                        ];
                                        if ($_status) {
                                            $stock [] = $value->id;
                                            $_status = false;
                                        }
                                    }
                                }
                            }
                            if (count($_tmp)) {
                                $spec [] = [
                                    'name' => $v->name,
                                    'children' => $_tmp,
                                ];
                            }
                        } else {
                            foreach ($goods as $value) {
                                if ($v['id'] == $value['attribute_id']) {
                                    $attribute[] = [
                                        'name' => $v['name'],
                                        'value' => $value['attr_value'],
                                    ];
                                }
                            }
                        }
                    }
                    $goods_number = GoodsStock::where('goods_id', $request['id'])
                        ->where('goods_attr_id', implode(',', $stock))
                        ->value('goods_number');
                    $extra = [
                        'models' => $models,
                        'id' => $request['id'],
//                        'type' => $type,
//                        'attr' => $attr,
                        'attribute' => $attribute,// 属性
                        'spec' => $spec,//规格
                        'goods_number' => $goods_number,//库存数
                        'more_goods' => $more_goods,//更多作品参数
                    ];
                    break;
                case 'shopping_cart':
                    $tmp = StoreCart::where('user_id', Auth::user()->id)
                        ->orderBy('created_at', 'desc')
                        ->get()->toArray();
                    $list = [];
                    foreach ($tmp as $v) {
                        $img = StoreGood::select('goods_cover')
                            ->where('models_id', $v['goods_id'])
                            ->first();
                        if (!empty($img['goods_cover']) && file_exists($img['goods_cover'])) {
                            $v['image'] = url($img['goods_cover']);
                        } else {
                            $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                        }
                        $list[$v['shop_id']]['children'][] = $v;
                        $list[$v['shop_id']]['shop_id'] = $v['shop_id'];
                    }
                    foreach ($list as &$v1) {
                        $v1['shop_name'] = StoreConfig::where('store_id', $v1['shop_id'])->value('store_name');
                        $v1['shop_cover'] = StoreConfig::where('store_id', $v1['shop_id'])->value('store_logo');
                    }
                    $extra = [
                        'list' => $list,
                        'goods_number' => count($tmp),
                    ];
                    break;
                case 'firm_order':
                    $ids = explode(',', $request['id']);
                    $uid = Auth::user()->id;
                    $tmp = StoreCart::where('user_id', $uid)
                       ->whereIn('id', $ids)
                        ->get()->toArray();
                    $list = [];
                    $price = 0;
                    foreach ($tmp as $v) {
                        $img = StoreGood::select('goods_cover')
                            ->where('models_id', $v['goods_id'])
                            ->first();
                        if (!empty($img['goods_cover']) && file_exists($img['goods_cover'])) {
                            $v['image'] = url($img['goods_cover']);
                        } else {
                            $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                        }
                        $list[$v['shop_id']]['children'][] = $v;
                        $list[$v['shop_id']]['shop_id'] = $v['shop_id'];
                        $price += $v['goods_number'] * $v['goods_price'];
                    }
                    foreach ($list as &$v1) {
                        $v1['shop_name'] = StoreConfig::where('store_id', $v1['shop_id'])->value('store_name');
                        $v1['shop_cover'] = StoreConfig::where('store_id', $v1['shop_id'])->value('store_logo');
                    }
                    $extra = [
                        'list' => $list,
                        'price' => $price,
                    ];
                    break;
                default:
                    $extra = [];
                    break;
            }
        } else {
            $extra = [];
        }
        return $extra;
    }

    //博物馆-更改背景
    public function editPic(Request $request)
    {
        $data = $request->except('_token');
        $this->store = Session::get('agentAdmin');
        $uid = $this->store->id;
        $pic = $data['editPic'];
        $result = StoreConfig::where('store_id', $uid)->update(['pic' => $pic]);
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '更换成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '更换失败']);
        }
    }

    //博物馆-选择模板@ajax
    public function select(Request $request)
    {
        $data = $request->except('_token');
        $this->store = Session::get('agentAdmin');
        $uid = $this->store->id;
        $type = $data['type'];
        $result = DB::transaction(function () use ($type, $uid) {
            StoreConfig::where('store_id', $uid)->update(['theme' => $type]);
        });
        $outcome = is_null($result) ? true : false;
        if ($outcome) {
            return response()->json(['code' => '1000', 'msg' => '使用成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '使用失败']);
        }
    }

    //获取商品对应的库存量@ajax-20180119
    /*public function get_goods_number(Request $request)
    {
        $attr = $request->input('attr');
        $goods_id = $request->input('goods_id');
        $data = GoodsStock::where('goods_id', $goods_id)
            ->where('goods_attr_id', $attr)
            ->value('goods_number');

        return ['code' => 1000, 'data' => $data];
    }*/

    // 评论
    public function comment(Request $request)
    {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $data['client_ip'] = $request->getClientIp();
        $data['store_id'] = $request->input('store_id');
        if (Auth::check()) {
            $data['user_id'] = Auth::user()->id;
            $has = StoreComment::where('user_id', $data['user_id']);
        } else {
            $data['user_id'] = 0;
            $has = StoreComment::where('client_ip', $data['client_ip']);
        }
        $has = $has->where('store_id', $data['store_id'])->whereBetween('created_at', [$start, $end])->latest()->first();
        if ($has) {
            return redirect()->back()->with(['err' => '一天只能评论一次']);
        } else {
            $data['content'] = e($request->input('content'));
            if (!$data['content']) {
                return redirect()->back()->with(['err' => '请输入评论内容']);
            }
            if (StoreComment::create($data)) {
                return redirect()->back()->with(['suc' => '评论成功']);
            } else {
                return redirect()->back()->with(['err' => '评论失败']);
            }
        }
    }

    // 答题
    public function answer(Request $request)
    {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $data['client_ip'] = $request->getClientIp();
        $data['store_id'] = $request->input('store_id');
        if (Auth::check()) {
            $data['user_id'] = Auth::user()->id;
            $has = StoreLearn::where('user_id', $data['user_id']);
        } else {
            $data['user_id'] = 0;
            $has = StoreLearn::where('client_ip', $data['client_ip']);
        }
        $has = $has->where('store_id', $data['store_id'])->whereBetween('created_at', [$start, $end])->latest()->first();
        if ($has) {
            return redirect()->back()->with(['err' => '一天只能答题一次']);
        } else {
            $answers = $request->input('subject');
            $ids = array_pluck($answers, 'id');
            $subject_tmp = StoreSubject::whereIn('id', $ids)
                ->where('store_id', $data['store_id'])
                ->with('storeSubjectAnswers')
                ->get()
                ->toArray();
            if (!count($subject_tmp)) {
                return redirect()->back()->with(['err' => '好像出了点错']);
            }
            $subjects = [];
            $total_number = count($ids);
            $total_score = 0;
            foreach ($subject_tmp as $key => $subject) {
                $tmp = [];
                foreach ($subject['store_subject_answers'] as $v) {
                    if ($v['is_right'] == 'Y') {
                        $tmp[] = $v['option'];
                    }
                }
                sort($tmp);
                $subjects[$subject['id']] = [
                    'answer' => $tmp,
                    'score' => $subject['score'],
                ];
                $total_score += $subject['score'];
            }
            $right_number = 0;
            $right_score = 0;
            $is_answer = true;
            foreach ($answers as $v) {
                $chk = array_pull($v, 'chk');
                if (!$chk) {
                    $is_answer = false;
                    break;
                }
                sort($chk);
                if ($chk == $subjects[$v['id']]['answer']) {
                    $right_number++;
                    $right_score += $subjects[$v['id']]['score'];
                }
            }
            if (!$is_answer) {
                return redirect()->back()->with(['err' => '您还有题目未选择答案']);
            }
            $data['total_number'] = $total_number;
            $data['right_number'] = $right_number;
            $data['total_score'] = $total_score;
            $data['right_score'] = $right_score;
            if (StoreLearn::create($data)) {
                $people = StoreLearn::where('right_score', '<', $right_score)->count();
                $str = "您得分：{$data['right_score']} 分，总分：{$data['total_score']} 分，打败全国 {$people} 人。";
                return redirect()->back()->with(['suc_subject' => $str, 'suc' => $str]);
            } else {
                return redirect()->back()->with(['err' => '答题失败']);
            }
        }
    }

    //商品-加入购物车
    public function addCart(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->all();
            //商品信息
            $good = StoreGood::where('models_id', $data['goods_id'])
                ->where('is_goods', 1)
                ->where('is_on_sale', 'Y')
                ->first();
            if (! $good) {
                return response()->json(['code' => '1001', 'msg' => '商品不存在']);
            }
            //商品属性
            if (isset($data['attr']) && count($data['attr']) > 0) {
                $attr = '';
                $attr_id = [];
                $price = 0.00;
                foreach ($data['attr'] as $v) {
                    $attr_id[] = $v;
                    $tmp = GoodsAttribute::where('id', $v)
                        ->where('goods_id', $good->models_id)
                        ->first();
                    $attr .=$tmp->Attribute->name . '：' . $tmp->attr_value . '；';
                    $price +=$tmp->attr_price;
                }
                $attr_id = implode(',', $attr_id);
            } else {
                $price = 0.00;
                $attr = $attr_id = null;
            }
            $good->store_price +=$price;
            //判断商品是否已经存在购物车
            $has = StoreCart::where('goods_id', $good->id)
                ->where('user_id', $uid)
                ->where('goods_attr_id', $attr_id)
                ->first();
            if ($has) {//更新数量
                if (StoreCart::where('id', $has->id)->increment('goods_number', $data['number'])) {
                    return response()->json(['code' => 1000, 'msg' => '更新购物车成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '更新购物车失败']);
                }
            } else {//新增购物车
                $cart = [
                    'user_id' => $uid,
                    'shop_id' => $good->store_id,
                    'goods_id' => $good->models_id,
                    'goods_name' => $good->goods_name,
                    'goods_price' => $good->goods_price + $price,
//                    'goods_price' => ($good->goods_price + $price)*$data['number'],
                    'goods_number' => $data['number'],
                    'goods_attr' => $attr,
                    'goods_attr_id' => $attr_id
                ];
                if (StoreCart::create($cart)) {
                    return response()->json(['code' => 1000, 'msg' => '加入购物车成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '加入购物车失败']);
                }
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    //商品-立即购买
    /*public function goods_buy(Request $request, $store = null)
    {
        $data = $request->except('_token');
        $shop = StoreGood::where('models_id', $data['goods_id'])->first();
        $shop->module = 'agent';
//        $info = StoreConfig::where('store_id', $shop->store_id)->first();
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('store_id', $shop->store_id)
            ->first();
//        $view = [
//            'info' => $info,
//            'seller_name' => $info['store_name'],
//            'store_logo' => $info['store_logo'],
//        ];
        return redirect()->route("{$shop->module}.pages.show",[$shop->store_id, 'firm_order']);
//        return $this->theme->scope("{$shop->module}/{$info->flag}/theme/{$info->theme}/firm_order", $view);


//        $info = StoreConfig::from('store_configs as sc')
//            ->select([
//                'sc.*',
//                'st.flag',
//            ])
//            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
//            ->where('store_id', $shop->store_id)
//            ->first();
//        $view = [
//            'info' => $info,
//            'seller_name' => $info['store_name'],
//            'store_logo' => $info['store_logo'],
//        ];
//        return redirect()->route("{$shop->module}.pages.show",[$shop->store_id, 'firm_order'], $view);
//        return $this->theme->scope("{$shop->module}/{$info->flag}/theme/{$info->theme}/firm_order", $view);

    }*/

    //购物车-删除商品
    public function delCart($id)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $status = StoreCart::where('user_id', $uid)
                ->where('id', $id)
                ->delete();
            if ($status) {
                return response()->json(['code' => '1000', 'msg' => '删除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '删除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登陆过期']);
        }
    }

    //购物车-更新商品的数量
    public function change_number(Request $request)
    {
        /*if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->except('_token');
//            dd($data);exit;
            if ($data['id'] <= 0 || $data['number'] <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $status = StoreCart::where('user_id', $uid)
                ->where('id', $data['id'])
                ->update(['goods_number' => $data['number']]);
            if ($status) {
                return response()->json(['code' => '1000']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '更改数量失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }*/
        $data = $request->get('normal');
        $goods_id = [];
        $update = [];
        foreach ($data as $v) {
            if (isset($v['id']) && !empty($v['id'])) {
                $goods_id[] = $v['id'];
            }
            $update[] = [
                'id' => $v['number_id'],
                'goods_number' => $v['number'],
            ];
        }
        $uid = Auth::user()->id;
        foreach ($update as $v) {
            $id = $v['id'];
            unset($v['id']);
            StoreCart::where('user_id', $uid)
                ->where('id', $id)
                ->update($v);
        }
        return redirect()->route('agent.pages.show',['store' => '420', 'page' => 'firm_order' . '?id=' . implode(',', $goods_id)]);
    }


    public function get_address(){
        $data = DistrictModel::get();
        if ($data) {
            $ech = json_encode($data);
            return $ech;
        } else {
            return response()->json(['code' => '1004', 'msg' => '参数错误']);
        }
    }
}
