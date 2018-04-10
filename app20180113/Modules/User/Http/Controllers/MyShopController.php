<?php
namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\Attribute;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\GoodsAttribute;
use App\Modules\User\Model\GoodsCart;
use App\Modules\User\Model\GoodsType;
use App\Modules\User\Model\StoreConfig;
use App\Modules\User\Model\StoreType;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Auth;
use Crypt;
use Cache;
use DB;
use App\Modules\User\Model\NewbieTaskModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsOrderViewModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use App\Modules\User\Model\ModelsOrderServiceModel;
use App\Modules\User\Model\ModelsOrderMaterialModel;
use App\Modules\User\Model\ModelsOrderEvaluateModel;
use App\Modules\Agent\Model\StoreView;
use App\Modules\Agent\Model\AgentCustomer;
use Illuminate\Support\Facades\Session;

class MyShopController extends UserCenterController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('myShop.goods.myShop');//主题初始化
    }

    /* /------------------------------------- 商品上下架 -------------------------------------\ */

    /**
     * 发布商品界面
     */
    public function releaseGoods(Request $request)
    {
        //判断用户是否完成新手任务
        $newbieTaskList = NewbieTaskModel::getNewbieTaskList();
        if (!empty($newbieTaskList)) {
            $this->theme->setTitle('我的店铺');
            return $this->theme->scope('user.myShop.goods.needNewbieTask')->render();
        }
        $uid = Auth::User()->id;
        $this->theme->setTitle('发布商品');
        //默认文件夹的作品总数
        $defaultFolderCount = ModelsContentModel::where('uid', $uid)->where('enroll_status', 0)->where('folder_id', 0)->where('is_goods', 0)->count();
        //检测店家上架商品数量是否超过上限
        if (Auth::user()->user_type > 0) {
            $goodsCount = ModelsContentModel::where('uid', $uid)->where('is_goods', 1)->count();
            $allowNumber = DB::table('shop_power')->where('user_type_id', Auth::user()->user_type)->value('number');
            $permission = ['code' => true, 'msg' => ''];
            if ($goodsCount >= $allowNumber)
                $permission = ['code' => false, 'msg' => '已达到商品上限，请升级会员版本以提升上限！'];
        } else {
            $permission = ['code' => false, 'msg' => '对不起，只有会员才能发布商品，请先开通会员！'];
        }
        //数据赋值
        $data = [
            'defaultFolderCount' => $defaultFolderCount,
            'permission' => $permission
        ];
        return $this->theme->scope('user.myShop.goods.releaseGoods', $data)->render();
    }

    /**
     * 获取用户的文件夹
     */
    public function getUserFolders(Request $request)
    {
        $list = ['code'  => 'error'];
        if (Auth::check()) {
            $perPage = $request->get('page') == 1 ? 17 : 18;
            $uid = Auth::User()->id;
            $list = ModelsFolderModel::select('id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time')
                                     ->where('uid', $uid)->paginate($perPage);
            foreach ($list as &$v) {
                $v['count'] = ModelsContentModel::where('uid', $uid)->where('enroll_status', 0)->where('folder_id', $v['id'])->where('is_goods', 0)->count();
                if( !empty($v['cover_img']) && file_exists($v['cover_img']) ){
                    $v['cover_img'] = url($v['cover_img']);
                } else {
                    $v['cover_img'] = '/themes/default/assets/images/folder_no_cover.png';
                }
            }
        }
        return $list;
    }

    /**
     * 获取文件夹信息
     */
    public function getFolderInfo($id = 0)
    {
        $data = ['code' => 'error', 'data' => ''];
        if (Auth::check()) {
            $uid = Auth::User()->id;
            if ($id > 0) {
                $info = ModelsFolderModel::select('id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time')
                    ->where('id', $id)->where('uid', $uid)->first()->toArray();
                if (!empty($info['cover_img']) && file_exists($info['cover_img'])) {
                    $info['cover_img'] = url($info['cover_img']);
                } else {
                    $info['cover_img'] = '/themes/default/assets/images/folder_no_cover.png';
                }
            } else {
                $info = [
                    'id' => 0,
                    'name' => '默认文件夹',
                    'cover_img' => '/themes/default/assets/images/folder_no_cover.png',
                    'auth_type' => 0
                ];
            }
            if ($info['auth_type'] == 1) {
                $info['auth_name'] = '<i class="fa fa-lock"></i> 仅自己可见';
            } else {
                $info['auth_name'] = '<i class="fa fa-unlock"></i> 所有人可见';
            }
            $data = ['code' => 'success', 'data' => $info];
        }
        return response()->json($data);
    }

    /**
     * 获取某个文件夹的所有作品
     */
    public function getModels($id = 0)
    {
        $list = ['code' => 'error'];
        if (Auth::check()) {
            $uid  = Auth::User()->id;
            $list = ModelsContentModel::select('id', 'title', 'cover_img', 'upload_cover_image', 'is_private')
                                      ->where('uid', $uid)
                                      ->where('folder_id', $id)
                                      ->where('enroll_status', 0)
                                      ->where('is_goods', 0)
                                      ->orderBy('create_time', 'desc')
                                      ->paginate(12);
            foreach ($list as &$v) {
                if (!empty($v['upload_cover_image']) && file_exists($v['upload_cover_image'])) {
                    $v['image'] = url($v['upload_cover_image']);
                } else {
                    if (!empty($v['cover_img']) && file_exists($v['cover_img'])) {
                        $v['image'] = url($v['cover_img']);
                    } else {
                        $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                    }
                }
                unset($v['upload_cover_image'], $v['cover_img']);
            }
        }
        return $list;
    }

    /**
     * 获取作品信息
     */
    public function getModelInfo($id = 0)
    {
        $data = ['code' => 'error'];
        if (!$id) return $data;
        if (Auth::check()) {
            $uid = Auth::User()->id;
            $info = ModelsContentModel::select('id', 'title', 'models_id', 'paramaters', 'content')->where('id', $id)->where('uid', $uid)->where('is_goods', 0)->first()->toArray();
            $info['models_pid'] = TaskCateModel::where('id', $info['models_id'])->value('pid');
            $param = [];
            if (!empty($info['paramaters'])) {
                $tempArray = explode('|', $info['paramaters']);
                foreach ($tempArray as $k => $v) {
                    $paraArray = explode('：', $v);
                    $param[$k] = $paraArray;
                }
            }
            $flag = count($param) - 1;
            $data = ['code' => 'success', 'info' => $info, 'param' => $param, 'flag' => $flag];
        }
        return response()->json($data);
    }

    /**
     * 获取分类
     */
    public function getCategory($id = 0)
    {
        $data = ['code' => 'error'];
        if (Cache::has('category_' . $id)) {
            $list = Cache::get('category_' . $id);
        } else {
            $list = TaskCateModel::select('id', 'name', 'pid')->where('pid', $id)->get()->toArray();
            Cache::put('category_' . $id, $list, 30 *24);
        }
        if ($list) {
            $data = ['code' => 'success', 'data' => $list];
        }
        return response()->json($data);
    }

    /**
     * 商品入库
     */
    public function addGoods(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->all();
            $old = ModelsContentModel::find($data['id'])->toArray();
            if ($old && $old['is_goods'] == 0) {// 有该作品
                $update = [
                    'update_time' => time(),
                    'title' => $request->get('title'),
                    'content' => $request->get('content'),
                    'paramaters' => $request->get('paramater'),
                    'models_id' => $request->get('models_id'),
                    'transaction_mode' => $request->get('transaction_mode'),
                    'is_goods' => 1,
                    'price' => $request->get('price'),
                    'goods_type_id' => $request->get('goods_type_id')
                ];
                if ($update['transaction_mode'] == 2) {
                    $update['view_mode'] = $request->get('view_mode');
                }
                $status = ModelsContentModel::where('id', $data['id'])->update($update);
                if ($status) {
                    $attr = [];
                    $time = date('Y-m-d H:i:s');
                    if (!empty($request->get('goods_spec_list'))) {
                        $tmp1 = explode(',', $request->get('goods_spec_list'));
                        $tmp1 = array_filter(array_unique($tmp1));
                        sort($tmp1, SORT_NUMERIC);
                        foreach ($tmp1 as $v) {
                            $tmp2 = explode('-', $v);
                            $attr[] = [
                                'user_id' => $uid,
                                'goods_id' => $data['id'],
                                'attribute_id' => $tmp2[0],
                                'attr_price' => $this->priceFormat(floatval($tmp2[2])),
                                'attr_value' => trim($tmp2[1]),
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                    }
                    if (!empty($request->get('goods_attr_list'))) {
                        $tmp1 = explode(',', $request->get('goods_attr_list'));
                        $tmp1 = array_filter(array_unique($tmp1));
                        foreach ($tmp1 as $v) {
                            $tmp2 = explode('-', $v);
                            $attr[] = [
                                'user_id' => $uid,
                                'goods_id' => $data['id'],
                                'attribute_id' => $tmp2[0],
                                'attr_price' => null,
                                'attr_value' => trim($tmp2[2]),
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                    }
                    if (count($attr) > 0) {
                        GoodsAttribute::insert($attr);
                    }
                    $ret = ['code' => 'success'];
                } else {
                    $ret = ['code' => 'error', 'msg' => '上架失败！'];
                }
            } else {
                $ret = ['code' => 'error', 'msg' => '没有该商品！'];
            }
        } else {
            $ret = ['code' => 'error', 'msg' => '未登录！'];
        }
        return response()->json($ret);
    }

    /**
     * 获取商品列表
     */
    public function getGoods(Request $request)
    {
        $list = ['code'  => 'error'];
        if (Auth::check()) {
            $perPage = $request->get('page') == 1 ? 19 : 20;
            $uid = Auth::User()->id;
            $list = ModelsContentModel::select('id', 'title', 'cover_img', 'upload_cover_image', 'price', 'transaction_mode')
                ->where('uid', $uid)->where('is_goods', 1)->orderBy('create_time', 'desc')->paginate($perPage);
            foreach ($list as &$v) {
                if (!empty($v['upload_cover_image']) && file_exists($v['upload_cover_image'])) {
                    $v['image'] = url($v['upload_cover_image']);
                } else {
                    if (!empty($v['cover_img']) && file_exists($v['cover_img'])) {
                        $v['image'] = url($v['cover_img']);
                    } else {
                        $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                    }
                }
                unset($v['upload_cover_image'], $v['cover_img']);
            }
        }
        return $list;
    }

    /**
     * 获取单个商品信息
     */
    public function getGoodsInfo($id = 0)
    {
        $data = ['code' => 'error'];
        if (!$id) return $data;
        if (Auth::check()) {
            $uid = Auth::User()->id;
            $info = ModelsContentModel::select('id', 'title', 'models_id', 'paramaters', 'content', 'price', 'transaction_mode', 'view_mode', 'goods_type_id')->where('id', $id)->where('uid', $uid)->where('is_goods', 1)->first()->toArray();
            $info['models_pid'] = TaskCateModel::where('id', $info['models_id'])->value('pid');
            $param = [];
            if (!empty($info['paramaters'])) {
                $tempArray = explode('|', $info['paramaters']);
                foreach ($tempArray as $k => $v) {
                    $paraArray = explode('：', $v);
                    $param[$k] = $paraArray;
                }
            }
            $flag = count($param) - 1;
            $data = ['code' => 'success', 'info' => $info, 'param' => $param, 'flag' => $flag];
        }
        return response()->json($data);
    }

    /**
     * 编辑商品处理
     */
    public function editGoods(Request $request)
    {
        $ret = ['code' => 'error', 'msg' => '修改失败！'];
        if (Auth::check()) {
            $uid = Auth::User()->id;
            $update = [
                'update_time' => time(),
                'title' => $request->get('title'),
                'content' => $request->get('content'),
                'paramaters' => $request->get('paramater'),
                'models_id' => $request->get('models_id'),
                'transaction_mode' => $request->get('transaction_mode'),
                'price' => $request->get('price'),
                'goods_type_id' => $request->get('goods_type_id')
            ];
            $goods_id = $request->get('id');
            if ($update['transaction_mode'] == 2) {
                $update['view_mode'] = $request->get('view_mode');
            }
            $goods_type_id = ModelsContentModel::where('id', $goods_id)
                ->where('is_goods', 1)
                ->where('uid', $uid)
                ->value('goods_type_id');
            $result = ModelsContentModel::where('id', $goods_id)
                ->where('is_goods', 1)
                ->where('uid', $uid)
                ->update($update);
            if ($result) {
                //更新购物车商品
                GoodsCart::where('goods_id', $goods_id)
                    ->update(['is_effective' => 'N']);
                //更新属性
                if ($goods_type_id != $update['goods_type_id']) {
                    GoodsAttribute::where('goods_id', $goods_id)->where('user_id', $uid)->delete();
                }
                $goods_spec_list = $request->get('goods_spec_list');
                $goods_attr_list = $request->get('goods_attr_list');
                GoodsAttribute::makeAttr($goods_spec_list, $goods_attr_list, $goods_id);
                $ret = ['code' => 'success', 'id' => $request->get('id')];
            }
        }
        return response()->json($ret);
    }

    /**
     * 商品下架处理
     */
    public function setGoodsShelves($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $old = ModelsContentModel::find($id)->toArray();
            if ($old && $old['is_goods'] == 1) {// 存在该商品
                $update = [
                    'price' => 0,
                    'transaction_mode' => 0,
                    'is_goods' => 0,
                    'view_mode' => 'normal',
                    'goods_type_id' => 0
                ];
                $status = ModelsContentModel::where('id', $id)->where('uid', $uid)->update($update);
                if ($status) {
                    //删除购物车商品
                    GoodsCart::where('goods_id', $id)->delete();
                    //删除属性
                    GoodsAttribute::where('goods_id', $id)->where('user_id', $uid)->delete();
                    $ret = ['code' => 'success', 'id' => $id];
                } else {
                    $ret = ['code' => 'error', 'msg' => '下架失败！'];
                }
            } else {
                $ret = ['code' => 'error', 'msg' => '该商品已下架，请勿重复提交！'];
            }
        } else {
            $ret = ['code' => 'error', 'msg' => '未登录！'];
        }
        return response()->json($ret);
    }

    /* /------------------------------------- 价格 -------------------------------------\ */

    /**
     * 格式化价格 *.**
     */
    protected function priceFormat($price)
    {
        return number_format($price, 2, '.', '');
    }

    /* /------------------------------------- 商品属性 -------------------------------------\ */

    /**
     * 属性类型
     */
    public function goodsType(Request $request)
    {
        //判断用户是否完成新手任务
        $newbieTaskList = NewbieTaskModel::getNewbieTaskList();
        if (!empty($newbieTaskList)) {
            $this->theme->setTitle('我的店铺');
            return $this->theme->scope('user.myShop.goods.needNewbieTask')->render();
        }
        //获取列表
        $list = GoodsType::where('user_id', Auth::user()->id);
        $perPage = $request->get('perPage') ? $request->get('perPage') : 18;
        $list = $list->latest()->paginate($perPage);
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('属性类型');
        return $this->theme->scope('user.myShop.goods.type', $view)->render();
    }

    /**
     * 属性类型-添加
     */
    public function addType(Request $request)
    {
        if (Auth::check()) {
            $name = $request->get('name', null);
            if (empty($name)) {
                return response()->json(['code' => '1101', 'msg' => '类型名称不能为空']);
            }
            $data = [
                'name' => $name,
                'user_id' => Auth::user()->id
            ];
            if (GoodsType::create($data)) {
                return response()->json(['code' => '1000', 'msg' => '新增成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '新增失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 属性类型-编辑
     */
    public function editType(Request $request)
    {
        if (Auth::check()) {
            $id = $request->get('id', 0);
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $name = $request->get('name', null);
            if (empty($name)) {
                return response()->json(['code' => '1101', 'msg' => '类型名称不能为空']);
            }
            $data = [
                'name' => $name
            ];
            $uid = Auth::user()->id;
            $ret = GoodsType::where('id', $id)->where('user_id', $uid)->update($data);
            if ($ret) {
                return response()->json(['code' => '1000', 'msg' => '编辑成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '编辑失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 属性类型-移除
     */
    public function delType(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $id = $request->get('id', 0);
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = GoodsType::where('id', $id)->where('user_id', $uid)->delete($id);
            if ($ret) {
                return response()->json(['code' => '1000', 'msg' => '移除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '移除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 获取属性类型列表
     */
    public function getType(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data =  GoodsType::where('user_id', $uid)->lists('name', 'id');
            if (count($data) > 0) {
                $view = [
                    'type' => $data,
                    'attr' => $this->handleAttr($request->all())
                ];
                return response()->json(['code' => '1000', 'data' => view('goods.attr', $view)->render()]);
            } else {
                return response()->json(['code' => '1102', 'msg' => '没有数据']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 根据已有属性进行赋值
     */
    protected function handleAttr($data)
    {
        $ret = [
            'list' => [],
            'manual' => [],
            'l' => 0,
            'm' => 0,
            'type_id' => 0,
            'price' => []
        ];
        if ($data['goods_id'] == 0) {//新增商品修改属性
            if ($data['id'] > 0) {
                $ret = Attribute::getAttr($data['id']);
                if (empty($ret)) {
                    $ret = [
                        'list' => [],
                        'manual' => [],
                        'l' => 0,
                        'm' => 0,
                        'type_id' => 0,
                        'price' => []
                    ];
                    return $ret;
                }
                $ret['type_id'] = $data['id'];
                $ret['price'] = [];
                if (!empty($data['spec'])) {
                    $tmp1 = explode(',', $data['spec']);
                    $tmp1 = array_filter(array_unique($tmp1));
                    $tmp2 = [];
                    foreach ($tmp1 as $v) {
                        $tmp3 = explode('-', $v);
                        $tmp3[2] = $this->priceFormat($tmp3[2]);
                        $tmp2[] = $tmp3;
                    }
                    $ret['price'] = $tmp2;
                    foreach ($ret['list'] as &$v) {
                        $checked = [];
                        foreach ($v['value'] as $k1 => $v1) {
                            $checked[$k1] = 0;
                            foreach ($ret['price'] as $v2) {
                                if ($v['id'] == $v2[0] && $v1 == $v2[1]) {
                                    $checked[$k1] = 1;
                                }
                            }
                        }
                        $v['checked'] = $checked;
                    }
                }
                if (!empty($data['attr'])) {
                    $tmp1 = explode(',', $data['attr']);
                    $tmp1 = array_filter(array_unique($tmp1));
                    $tmp2 = [];
                    foreach ($tmp1 as $v1) {
                        $tmp2[] = explode('-', $v1);
                    }
                    foreach ($ret['manual'] as $k1 => $v1) {
                        foreach ($tmp2 as $v2) {
                            if ($v1['id'] == $v2[0] && $v1['name'] == $v2[1]) {
                                $ret['manual'][$k1]['value'] = $v2[2];
                            }
                        }
                    }
                }
                return $ret;
            } else {
                return $ret;
            }
        } else {//修改商品属性
            if ($data['id'] > 0) {
                $ret = Attribute::getAttr($data['id']);
                if (empty($ret)) {
                    $ret = [
                        'list' => [],
                        'manual' => [],
                        'l' => 0,
                        'm' => 0,
                        'type_id' => 0,
                        'price' => []
                    ];
                    return $ret;
                }
                $ret['type_id'] = $data['id'];
                $ret['price'] = [];
                $tmp = GoodsAttribute::where('goods_id', $data['goods_id'])
                    ->where('user_id', Auth::user()->id)->get()->toArray();
                if (count($tmp) > 0) {
                    $price_id = [];
                    foreach ($ret['list'] as &$v) {//筛选规格
                        $checked = [];
                        $attr_id = [];
                        foreach ($v['value'] as $k1 => $v1) {
                            $checked[$k1] = 0;
                            $attr_id[$k1] = 0;
                            foreach ($tmp as $v2) {
                                if ($v['id'] == $v2['attribute_id'] && $v1 == $v2['attr_value']) {
                                    $checked[$k1] = 1;
                                    $price_id[] = $attr_id[$k1] = $v2['id'];
                                }
                            }
                        }
                        $v['checked'] = $checked;
                        $v['attr_id'] = $attr_id;
                    }
                    foreach ($ret['manual'] as $k1 => $v1) {//筛选属性
                        foreach ($tmp as $v2) {
                            if ($v1['id'] == $v2['attribute_id']) {
                                $ret['manual'][$k1]['value'] = $v2['attr_value'];
                                $ret['manual'][$k1]['goods_attr_id'] = $v2['id'];
                            }
                        }
                    }
                    if (count($price_id) > 0) {//规格价格列表
                        $price = [];
                        $tmp = GoodsAttribute::where('goods_id', $data['goods_id'])
                            ->where('user_id', Auth::user()->id)->whereIn('id', $price_id)
                            ->get()->toArray();
                        foreach ($tmp as $v1) {
                            $price[] = [
                                $v1['attribute_id'],
                                $v1['attr_value'],
                                $v1['attr_price'],
                                $v1['id']
                            ];
                        }
                        $ret['price'] = $price;
                    }
                    return $ret;
                } else {
                    return $ret;
                }
            } else {
                return $ret;
            }
        }
    }

    /**
     * 属性
     */
    public function goodsAttr(Request $request, $id = 0)
    {
        //判断用户是否完成新手任务
        $newbieTaskList = NewbieTaskModel::getNewbieTaskList();
        if (!empty($newbieTaskList)) {
            $this->theme->setTitle('我的店铺');
            return $this->theme->scope('user.myShop.goods.needNewbieTask')->render();
        }
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        //返回链接
        $page = $request->get('p', 1);
        $href = route('myShop.goodsType') . '?page=' . $page;
        //获取列表
        $uid = Auth::user()->id;
        $list = $info = GoodsType::where('user_id', $uid)
            ->where('id', $id)
            ->first();
        $perPage = $request->get('perPage') ? $request->get('perPage') : 18;
        $list = $list->attributes()->where('user_id', $uid)->latest()->paginate($perPage);
        //获取类型列表
        $type = GoodsType::where('user_id', $uid)->lists('name', 'id');
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all(),
            'href' => $href,
            'info' => $info,
            'type' => $type,
            'page' => $page
        ];
        $this->theme->setTitle($info->name . '的属性');
        return $this->theme->scope('user.myShop.goods.attr', $view)->render();
    }

    /**
     * 属性-添加
     */
    public function addAttr(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'name' => 'required',
            'goods_type_id' => 'required',
            'input_type' => 'required'
        ]);
        $data = $request->except('_token', 'p');
        if ($data['input_type'] == 'list') {
            if (empty($data['value'])) {
                return back()->with(['err' => '可选值列表不能为空']);
            }
            $data['value'] = trim($data['value']);
        } else {
            $data['value'] = null;
        }
        //用户
        $uid = Auth::user()->id;
        $data['user_id'] = $uid;
        //新增数据
        if (Attribute::create($data)) {
            return redirect('/user/goodsAttr/' . $data['goods_type_id'] . '?p=' . $request->get('p', 1))
                ->with(['suc' => '新增成功']);
        } else {
            return back()->with(['err' => '新增失败']);
        }
    }

    /**
     * 属性-编辑@页面
     */
    public function editAttrPage(Request $request, $id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        //用户信息
        $uid = Auth::user()->id;
        //获取信息
        $info = Attribute::where('id', $id)->where('user_id', $uid)->first();
        //获取类型列表
        $type = GoodsType::where('user_id', $uid)->lists('name', 'id');
        //返回链接
        $page = $request->get('p', 1);
        $href = route('myShop.goodsAttr', ['id' => $info->goods_type_id]) . '?page=' . $page;
        //数据赋值
        $view = [
            'type' => $type,
            'info' => $info,
            'href' => $href
        ];
        $this->theme->setTitle('编辑属性：' . $info->name);
        return $this->theme->scope('user.myShop.goods.editAttr', $view)->render();
    }

    /**
     * 属性-编辑
     */
    public function editAttr(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'name' => 'required',
            'goods_type_id' => 'required',
            'input_type' => 'required',
            'id' => 'required'
        ]);
        $data = $request->except('_token', 'id', 'p');
        $id = $request->get('id');
        if ($data['input_type'] == 'list') {
            if (empty($data['value'])) {
                return back()->with(['err' => '可选值列表不能为空']);
            }
            $data['value'] = trim($data['value']);
        } else {
            $data['value'] = null;
        }
        //用户
        $uid = Auth::user()->id;
        $ret = Attribute::where('id', $id)->where('user_id', $uid)->update($data);
        //更新数据
        if ($ret) {
            return redirect('/user/goodsAttr/' . $data['goods_type_id'] . '?p=' . $request->get('p', 1))
                ->with(['suc' => '编辑成功']);
        } else {
            return back()->with(['err' => '编辑失败']);
        }
    }

    /**
     * 属性-移除
     */
    public function delAttr(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $id = $request->get('id', 0);
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = Attribute::where('id', $id)->where('user_id', $uid)->delete($id);
            if ($ret) {
                return response()->json(['code' => '1000', 'msg' => '移除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '移除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 获取属性列表
     */
    public function getAttr($id = 0)
    {
        if (Auth::check()) {
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $data = Attribute::getAttr($id);
            if (count($data) > 0) {
                $manual = view('goods.manual', ['list' => $data['manual']])->render();
                $list = view('goods.list', ['list' => $data['list']])->render();
                $data['manual'] = $manual;
                $data['list'] = $list;
                return response()->json(['code' => '1000', 'data' => $data]);
            } else {
                return response()->json(['code' => '1102', 'msg' => '没有数据']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 商品属性-移除
     */
    public function delGoodsAttr(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $goods_attr_id = $request->get('goods_attr_id', 0);
            $goods_id = $request->get('goods_id', 0);
            if ($goods_attr_id <= 0 || $goods_id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = GoodsAttribute::where('id', $goods_attr_id)
                ->where('user_id', $uid)
                ->where('goods_id', $goods_id)
                ->delete($goods_attr_id);
            if ($ret) {
                return response()->json(['code' => '1000', 'msg' => '移除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '移除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /* /------------------------------------- 店铺管理-查看店铺 -------------------------------------\ */

    /**
     * 查看店铺-店铺列表
     */
    public function stores(Request $request)
    {
        //店铺类型
        $type = StoreType::getList();
        //数据赋值
        $view = [
            'type' => $type,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('查看店铺');
        return $this->theme->scope('user.myShop.store.list', $view)->render();
    }

    /**
     * 查看店铺-ajax@获取店铺列表
     */
    public function getStoreList(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $list = UserModel::from('users as u')
                ->select([
                    'u.*',
                    'sc.store_name',
                    'sc.store_auth',
                    'sc.store_status',
                    'sc.assure_status',
                    'sc.auth_status',
                    'sc.open_status',
                    'sc.expire_at',
                    'st.name as store_type_name',
                    'st.flag',
                ])
                ->where('u.pid', $uid);
            if ($type = $request->input('type')) {
                $list = $list->where('u.store_type_id', $type);
            }
            $list = $list->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
                ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
                ->orderBy('u.created_at', 'desc')
                ->paginate(9);
            if ($list->lastPage()) {
                $time = date('Y-m-d H:i:s');
                $view = [
                   'list' => $list,
                   'time' => $time,
                ];
                return response()->json([
                    'code' => '1000',
                    'page' => $list->lastPage(),
                    'data' => view('store.list', $view)->render()
                ]);
            } else {
                if ($request->get('page') == 1) {
                    return response()->json([
                        'code' => '1111',
                        'data' => view('store.empty')->render()
                    ]);
                } else {
                    return response()->json(['code' => '1102', 'msg' => '没有数据']);
                }
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 查看店铺-选择要创建的店铺类型
     */
    public function storeType()
    {
        //店铺类型
        $type = StoreType::getList();
        //数据赋值
        $view = [
            'type' => $type
        ];
        $this->theme->setTitle('选择要创建的店铺类型');
        return $this->theme->scope('user.myShop.store.type', $view)->render();
    }

    /**
     * 查看店铺-创建店铺页面
     */
    public function storeCreate($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = StoreType::find($id);
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        //查询主营商品
        $major = TaskCateModel::where('pid', 0)->lists('name', 'id');
        //查询省信息
        $province = DistrictModel::findTree(0);
        //数据赋值
        $view = [
            'store_type_id' => $id,
            'info' => $info,
            'province' => $province,
            'major' => $major
        ];
        $this->theme->setTitle('请完善店铺信息');
        return $this->theme->scope('user.myShop.store.create', $view)->render();
    }

    /**
     * 查看店铺-创建店铺处理
     */
    public function storeCreating(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'nickname' => [
                'required',
                'regex:/^[\w\x{4e00}-\x{9fa5}\-\+]+$/ui'
            ],
            'major_business' => 'required',
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'road' => [
                'required',
                'regex:/^[\w\x{4e00}-\x{9fa5}\-\+]+$/ui'
            ],
            'agree' => 'accepted'
        ], [
            'nickname.required' => '请输入店铺名称',
            'nickname.regex' => '店铺名称不能含有特殊字符',
            'major_business.required' => '请选择主营商品',
            'province.required' => '请选择省份',
            'city.required' => '请选择城市',
            'area.required' => '请选择地区',
            'road.required' => '请输入详细地址',
            'road.regex' => '详细地址不能含有特殊字符',
            'agree.accepted' => '您必须同意服务条款'
        ]);
        $data = $request->all();
        // 用户id
        $uid = Auth::user()->id;
        // 店铺账号（用户信息）
        $salt = \CommonClass::random(4);
        $password = '123456';
        $user = [
            'name' => UserModel::genUsername(),
            'password' => UserModel::encryptPassword($password, $salt),
            'alternate_password' => UserModel::encryptPassword($password, $salt),
            'salt' => $salt,
            'pid' => $uid,
            'store_type_id' => $data['store_type_id'],
            'status' => 1
        ];
        // 店铺信息 （用户详细信息）
        $userDetail = [
            'nickname' => $data['nickname'],
            'province' => $data['province'],
            'city' => $data['city'],
            'area' => $data['area'],
            'road' => $data['road'],
            'uid' => 0
        ];
        // 店铺配置
        $storeConfig = [
            'store_name' => $data['nickname'],
            'province' => $data['province'],
            'city' => $data['city'],
            'area' => $data['area'],
            'address' => $data['road'],
            'store_id' => 0,
            'store_type_id' => $data['store_type_id'],
            'major_business' => $data['major_business'],
            'expire_at' => date('Y-m-d H:i:s', strtotime('+7 day')),
            'open_status' => 'on',
        ];
        $result = DB::transaction(function () use ($user, $userDetail, $storeConfig)
        {
            $result = UserModel::create($user);
            $userDetail['uid'] = $storeConfig['store_id'] = $result['id'];
            UserDetailModel::create($userDetail);
            StoreConfig::create($storeConfig);
        });
        $result = is_null($result) ? true : false;
        if ($result) {
            return redirect()->route('shop.list');
        } else {
            return back()->with(['err' => '创建店铺失败']);
        }
    }

    /**
     * 设计师通过切换店铺登录相应的店铺管理员
     */
    public function agentAdminLogin(Request $request, $id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $agent = UserModel::from('users as u')
            ->select([
                'u.*',
                'sc.store_name',
                'sc.store_thumb_logo as store_logo',
                'sc.store_auth',
                'sc.store_status',
                'sc.assure_status',
                'sc.auth_status',
                'sc.open_status',
                'sc.expire_at',
                'sc.store_desc',
                'sc.qq',
                'sc.mobile_register',
                'sc.created_at',
                'st.name as store_type_name',
                'st.flag',
                'c.name as store_cat_name',
            ])
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->leftJoin('cate as c', 'c.id', '=', 'sc.major_business')
            ->where('u.id', $id)
            ->where('u.pid', $uid)
            ->first();
        if (! $agent) {
            return back()->with(['err' => '参数错误']);
        }
        Session::put('agentAdmin', $agent);
        $edit = $request->input('edit', null);
        if ($edit != 1) {
            return redirect()->route('agent.admin.index');
        } else {
            return redirect()->route("agent.{$agent->flag}.setup.index");
        }
    }

    // ajax@删除店铺
    public function storeDelete($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => 1001, 'msg' => '非法操作']);
        }
        $uid = $this->user->id;
        $info = UserModel::from('users as u')
            ->select([
                'u.*',
                'sc.auth_status',
            ])
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
            ->where('u.id', $id)
            ->where('u.pid', $uid)
            ->first();
        if (! $info || ($info && $info->auth_status == 3)) {
            return response()->json(['code' => 1100, 'msg' => '参数错误']);
        }
        $status = DB::transaction(function () use ($info) {
            UserModel::where('id', $info->id)->delete();
            UserDetailModel::where('uid', $info->id)->delete();
            $ids = ModelsOrderModel::where('shop_id', $info->id)->lists('id')->toArray();
            ModelsOrderModel::where('shop_id', $info->id)->delete();
            ModelsOrderGoodsModel::whereIn('order_id', $ids)->delete();
            ModelsOrderMaterialModel::where('shop_id', $info->id)->delete();
            ModelsOrderViewModel::where('shop_id', $info->id)->delete();
            ModelsOrderServiceModel::where('shop_id', $info->id)->delete();
            ModelsOrderEvaluateModel::where('shop_id', $info->id)->delete();
            ModelsContentModel::where('uid', $info->id)->delete();
            AgentCustomer::where('store_id', $info->id)->delete();
            StoreView::where('store_id', $info->id)->delete();
            TaskModel::where('uid', $info->id)->delete();
        });
        if (is_null($status)) {
            return response()->json(['code' => 1000, 'msg' => '删除成功']);
        } else {
            return response()->json(['code' => 1001, 'msg' => '删除失败']);
        }
    }

    // 店铺续费页面 @ajax
    public function storeRenew($id = 0)
    {
        $uid = $this->user->id;
        $info = UserModel::from('users as u')
            ->select([
                'u.*',
                'sc.store_name',
                'sc.expire_at',
            ])
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
            ->where('u.id', $id)
            ->where('u.pid', $uid)
            ->first();
        if (! $info) {
            return response()->json(['code' => 1001, 'msg' => '参数错误']);
        }
        $first = '1';
        $years = [
            $first => '0.01',
            '2' => '0.02',
            '3' => '0.03',
        ];

        $view = [
            'years' => $years,
            'info' => $info,
            'first' => $first,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view('store.renew', $view)->render(),
        ]);
    }

    /**
     * 字符串截取
     */
    public static function cutStr($string, $sublen, $start =0, $code ='UTF-8')
    {
        if ($code =='UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if (count($t_string[0]) - $start > $sublen) {
                return join('', array_slice($t_string[0], $start, $sublen)) . '...';
            }
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start  = $start*2;
            $sublen = $sublen*2;
            $strlen = strlen($string);
            $tmpstr = '';
            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i,1)) > 129) {
                        $tmpstr .= substr($string, $i,2);
                    } else {
                        $tmpstr .= substr($string, $i,1);
                    }
                }
                if (ord(substr($string, $i,1)) > 129) {
                    $i++;
                }
            }
            if (strlen($tmpstr) < $strlen ) {
                $tmpstr .= '...';
            }
            return $tmpstr;
        }
    }
}
