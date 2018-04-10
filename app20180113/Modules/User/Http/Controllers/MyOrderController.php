<?php
namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use App\Modules\User\Model\Attribute;
use App\Modules\User\Model\CountryMobilePrefix;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\Express;
use App\Modules\User\Model\GoodsAttribute;
use App\Modules\User\Model\UserAddress;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Http\Request;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsOrderServiceModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsOrderEvaluateModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\ModelsOrderViewModel;
use App\Modules\User\Model\ModelsOrderMaterialModel;
use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Model\GoodsCart;
use ZipArchive;
use Session;
use Auth;
use Crypt;
use Cache;
use DB;

class MyOrderController extends UserCenterController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('myOrder.task');//主题初始化
        $shopCount = ModelsOrderServiceModel::where('shop_id', Auth::user()->id)
            ->whereNotIn('task_status', [0, 5])->count();
        $userCount = ModelsOrderServiceModel::where('user_id', Auth::user()->id)
            ->whereNotIn('task_status', [0, 5])->count();
        $this->theme->set('shopCount', $shopCount >= 100 ? 99 : $shopCount);
        $this->theme->set('userCount', $userCount >= 100 ? 99 : $userCount);
        //3天没支付关闭订单-针对出售商品
        $time = date('Y-m-d H:i:s');
        $three = date('Y-m-d H:i:s', strtotime($time . '-3 day'));
        $ids = ModelsOrderModel::where('order_status', 1)
            ->where('pay_status', 1)
            ->where('transaction_mode', 1)
            ->where('created_at', '<', $three)
            ->lists('id');
        if (count($ids) > 0) {
            ModelsOrderModel::whereIn('id', $ids)->update(['order_status' => '2']);
        }
        //15天没收货自动确认收货-针对出售商品
        $fifteen = date('Y-m-d H:i:s', strtotime($time . '-15 day'));
        $ids = ModelsOrderModel::where('order_status', 1)
            ->where('pay_status', 2)
            ->where('post_status', 2)
            ->where('transaction_mode', 1)
            ->where('post_at', '<', $fifteen)
            ->lists('id');
        if (count($ids) > 0) {
            ModelsOrderModel::whereIn('id', $ids)->update(['post_status' => '3']);
            //TODO: 批量打款给商家
        }
    }

    /* /------------------------------------- 价格 -------------------------------------\ */

    /**
     * 格式化价格 *.**
     */
    protected function priceFormat($price)
    {
        return number_format($price, 2, '.', '');
    }

    /* /------------------------------------- 出售商品 -------------------------------------\ */

    /**
     * 出售商品-订单填写页
     */
    public function goodsBuy($ids = '')
    {
        if (empty($ids)) {
            return redirect()->route('myCart.cart')->with(['err' => '非法操作']);
        }
        $ids = explode(',', $ids);
        $uid = Auth::user()->id;
        //获取列表
        $tmp = GoodsCart::where('user_id', $uid)
            ->whereIn('id', $ids)
            ->where('is_effective', 'Y')
            ->latest()
            ->get()->toArray();
        if (count($tmp) <= 0) {
            return redirect()->route('myCart.cart')->with(['err' => '请您选择商品后进行下单']);
        }
        $list = [];
        $price = 0;
        foreach ($tmp as &$v) {
            $img = ModelsContentModel::select('upload_cover_image', 'cover_img')
                ->where('id', $v['goods_id'])
                ->first();
            if (!empty($img['upload_cover_image']) && file_exists($img['upload_cover_image'])) {
                $v['image'] = url($img['upload_cover_image']);
            } else {
                if (!empty($img['cover_img']) && file_exists($img['cover_img'])) {
                    $v['image'] = url($img['cover_img']);
                } else {
                    $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                }
            }
            $list[$v['shop_id']]['children'][] = $v;
            $list[$v['shop_id']]['shop_id'] = $v['shop_id'];
            $price += $v['goods_number'] * $v['goods_price'];
        }
        foreach ($list as &$v1) {
            $v1['shop_name'] = UserDetailModel::where('uid', $v1['shop_id'])->value('nickname');
        }
        //地址列表
        $address = UserAddress::where('user_id', $uid)
            ->orderBy('is_default', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        //地区号码前缀
        $prefix = CountryMobilePrefix::getPrefix();
        //查询省信息
        $province = DistrictModel::findTree(0);
        //数据赋值
        $view = [
            'list' => $list,
            'price' => $this->priceFormat($price),
            'address' => $address,
            'prefix' => $prefix,
            'province' => $province
        ];
        $this->setToken();
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle('确认订单信息');
        return $this->theme->scope('user.myOrder.goods.goodsBuy', $view)->render();
    }

    /**
     * 出售商品-正式下单
     */
    public function goodsAdd(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'address' => [
                'required',
                'integer'
            ],
            'order' => 'required',
            'ids' => 'required'
        ], [
            'address.required' => '请选择收获地址',
            'address.integer' => '收获地址非法',
            'order' => '订单信息为空',
            'ids' => '没有选择商品'
        ]);
        //获取用户id
        $uid = Auth::user()->id;
        //获取所有数据
        $data = $request->only(['address', 'order']);
        //获取用户地址
        $address = UserAddress::where('id', $data['address'])->where('user_id', $uid)->first();
        $address->mobile = $address->mobiles->prefix . '-' . $address->mobile;
        if ($address->tel) {
            $address->tel = $address->tels->prefix . '-' . $address->tel_area_code . '-' . $address->tel;
        } else {
            $address->tel = null;
        }
        unset($address->id, $address->user_id, $address->mobile_prefix_id,
            $address->tel_prefix_id, $address->tel_area_code, $address->is_default,
            $address->created_at, $address->updated_at, $address->mobiles, $address->tels);
        $address = $address->toArray();
        //按商家进行下单
        $ids = [];
        $delete = [];
        foreach ($data['order'] as $v) {
            $time = date('Y-m-d H:i:s');
            //订单信息
            $order = $address;
            $order['user_id'] = $uid;
            $order['total_price'] = 0.00;
            $order['transaction_mode'] = 1;
            $order['user_desc'] = $v['msg'];
            $order['shop_id'] = $v['shop_id'];
            $order['from_at'] = $this->checkWap() ? 'wap' : 'web';
            //没有商品信息，结束本次循环
            if (count($v['id']) <= 0) {
                continue;
            }
            $tmp = GoodsCart::where('user_id', $uid)
                ->where('shop_id', $v['shop_id'])
                ->where('is_effective', 'Y')
                ->whereIn('id', $v['id'])
                ->get();
            $goods = [];
            foreach ($tmp as $v1) {
                $order['total_price'] += $v1->goods_price * $v1->goods_number;
                $goods[] = [
                    'order_id' => 0,
                    'goods_id' => $v1->goods_id,
                    'goods_name' => $v1->goods_name,
                    'goods_number' => $v1->goods_number,
                    'goods_price' => $v1->goods_price,
                    'goods_attr' => $v1->goods_attr,
                    'goods_attr_id' => $v1->goods_attr_id,
                    'created_at' => $time,
                    'updated_at' => $time
                ];
                $delete[] = $v1->id;
            }
            //执行数据库操作
            $order['order_sn'] = 'G' . $this->getOrderSn();
            $result = DB::transaction(function () use ($order, $goods)
            {
                $result = ModelsOrderModel::create($order);
                foreach ($goods as &$v2) {
                    $v2['order_id'] = $result['id'];
                }
                ModelsOrderGoodsModel::insert($goods);
                return $result;
            });
            //成功-存入id，用于批量付款
            if ($result) {
                $ids[] = $result->id;
            }
        }
        GoodsCart::whereIn('id', $delete)->where('user_id', $uid)->delete();
        return redirect()->route('myOrder.payment', ['ids' => implode(',', $ids), 'address' => $data['address']]);
    }

    /**
     * 出售商品-正式下单-新增收获地址-ajax
     */
    public function addAddress(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            //表单验证
            $this->validate($request, [
                'country' => 'required',
                'province' => 'required',
                'city' => 'required',
                'area' => 'required',
                'address' => [
                    'required',
                    'regex:/^[\w\x{4e00}-\x{9fa5}\-\+]+$/ui'
                ],
                'zip_code' => [
                    'required',
                    'regex:/^\d{6}$/'
                ],
                'consignee' => [
                    'required',
                    'regex:/(^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-z]{2,10}$)/ui'
                ],
                'mobile_prefix_id' => 'required',
                'mobile' => [
                    'required',
                    'regex:/^1[34578]\d{9}$/',
                ],
                'tel_prefix_id' => 'required',
                'tel_area_code' => [
                    'regex:/^\d{3,4}$/'
                ],
                'tel' => [
                    'regex:/^\d{7,8}$/'
                ]
            ], [
                'country.required' => '请选择国家',
                'province.required' => '请选择省份',
                'city.required' => '请选择城市',
                'area.required' => '请选择地区',
                'address.required' => '请输入详细地址',
                'address.regex' => '详细地址不能含有特殊字符',
                'zip_code.required' => '请输入邮政编码',
                'zip_code.regex' => '邮政编码为6位数字',
                'consignee.required' => '请输入收货人',
                'consignee.regex' => '只允许2-10位中文或英文名字',
                'mobile_prefix_id.required' => '请选择国家区号',
                'mobile.required' => '请输入手机号',
                'mobile.regex' => '手机号码格式不正确',
                'tel_prefix_id.required' => '请选择国家区号',
                'tel_area_code.regex' => '区号为3-4位',
                'tel.regex' => '电话为7-8位数字'
            ]);
            //数据处理
            $data = $request->all();
            $is_default = isset($data['is_default']) ? ($data['is_default'] == 'Y' ? true : false) : false;
            if ($is_default) {
                UserAddress::where('user_id', $uid)->update(['is_default' => 'N']);
            }
            $data['user_id'] = $uid;
            $status = UserAddress::create($data);
            if ($status) {
                return response()->json(['code' => '1000', 'msg' => '新增成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '新增失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-正式下单-删除地址-ajax
     */
    public function delAddress($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $status = UserAddress::where('id', $id)->where('user_id', $uid)->delete($id);
            if ($status) {
                return response()->json(['code' => '1000', 'msg' => '删除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '删除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }


    /**
     * 出售商品-正式下单-获取地址修改页面-ajax
     */
    public function editAddressPage($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            //数据处理
            $data = UserAddress::where('id', $id)->where('user_id', $uid)->first();
            if ($data) {
                //地区号码前缀
                $prefix = CountryMobilePrefix::getPrefix();
                //查询省信息
                $province = DistrictModel::findTree(0);
                // 查询城市数据
                if (! is_null($data['province'])) {
                    $city = DistrictModel::findTree($data['province']);
                } else {
                    $city = DistrictModel::findTree($province[0]['id']);
                }
                // 查询地区信息
                if (! is_null($data['city'])) {
                    $area = DistrictModel::findTree($data['city']);
                } else {
                    $area = DistrictModel::findTree($city[0]['id']);
                }
                $view = [
                    'info' => $data,
                    'prefix' => $prefix,
                    'province' => $province,
                    'city' => $city,
                    'area' => $area
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view('address.edit', $view)->render()
                ]);
            } else {
                return response()->json(['code' => '1102', 'msg' => '没有数据']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-正式下单-编辑地址-ajax
     */
    public function editAddress(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            //表单验证
            $this->validate($request, [
                'country' => 'required',
                'province' => 'required',
                'city' => 'required',
                'area' => 'required',
                'address' => [
                    'required',
                    'regex:/^[\w\x{4e00}-\x{9fa5}\-\+]+$/ui'
                ],
                'zip_code' => [
                    'required',
                    'regex:/^\d{6}$/'
                ],
                'consignee' => [
                    'required',
                    'regex:/(^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-z]{2,10}$)/ui'
                ],
                'mobile_prefix_id' => 'required',
                'mobile' => [
                    'required',
                    'regex:/^1[34578]\d{9}$/',
                ],
                'tel_prefix_id' => 'required',
                'tel_area_code' => [
                    'regex:/^\d{3,4}$/'
                ],
                'tel' => [
                    'regex:/^\d{7,8}$/'
                ]
            ], [
                'country.required' => '请选择国家',
                'province.required' => '请选择省份',
                'city.required' => '请选择城市',
                'area.required' => '请选择地区',
                'address.required' => '请输入详细地址',
                'address.regex' => '详细地址不能含有特殊字符',
                'zip_code.required' => '请输入邮政编码',
                'zip_code.regex' => '邮政编码为6位数字',
                'consignee.required' => '请输入收货人',
                'consignee.regex' => '只允许2-10位中文或英文名字',
                'mobile_prefix_id.required' => '请选择国家区号',
                'mobile.required' => '请输入手机号',
                'mobile.regex' => '手机号码格式不正确',
                'tel_prefix_id.required' => '请选择国家区号',
                'tel_area_code.regex' => '区号为3-4位',
                'tel.regex' => '电话为7-8位数字'
            ]);
            //数据处理
            $data = $request->all();
            $is_default = isset($data['is_default']) ? ($data['is_default'] == 'Y' ? true : false) : false;
            if ($is_default) {
                UserAddress::where('user_id', $uid)->update(['is_default' => 'N']);
            }
            $id = $data['id'];
            unset($data['id']);
            $status = UserAddress::where('id', $id)->where('user_id', $uid)->update($data);
            if ($status) {
                return response()->json(['code' => '1000', 'msg' => '编辑成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '编辑失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-正式下单-取消编辑地址-ajax
     */
    public function cancelAddress()
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            //地区号码前缀
            $prefix = CountryMobilePrefix::getPrefix();
            //查询省信息
            $province = DistrictModel::findTree(0);
            $view = [
                'prefix' => $prefix,
                'province' => $province
            ];
            return response()->json([
                'code' => '1000',
                'data' => view('address.add', $view)->render()
            ]);
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-下单成功-等待支付
     */
    public function payment($ids = '', $address = 0)
    {
        if (empty($ids)) {
            return redirect()->route('myCart.cart')->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $address = UserAddress::where('id', $address)->where('user_id', $uid)->first();
        $money = ModelsOrderModel::whereIn('id', explode(',', $ids))
            ->where('user_id', $uid)->sum('total_price');
        if (! $address || ! $money) {
            return redirect()->route('myCart.cart')->with(['err' => '参数错误']);
        }
        $view = [
            'address' => $address,
            'money' => $money,
            'ids' => $ids
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle('订单提交成功');
        return $this->theme->scope('user.myOrder.goods.payment', $view)->render();
    }

    /**
     * 出售商品-模拟支付
     */
    public function goodsPay($id = 0, $ids = '')
    {
        if ($id <= 0 && empty($ids)) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        if ($id > 0) {//单个订单支付
            $info =  ModelsOrderModel::find($id);
            $price = $info->total_price - $info->paid_price;//要支付的钱
            if ($price > 0) {
                $time = date('Y-m-d H:i:s');
                if ($info->payment_details){
                    $details = unserialize($info->payment_details);
                    $details[] = ['price' => $price, 'time' => $time];
                    $details = serialize($details);
                } else {
                    $details[] = ['price' => $price, 'time' => $time];
                    $details = serialize($details);
                }
                $order = [
                    'pay_status' => 2,
                    'pay_at' => $time,
                    'paid_price' => $price,
                    'payment_details' => $details
                ];
                $status = ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                if ($status) {
                    return redirect('/user/myOrderGoodsOut');
                } else {
                    return back()->with(['err' => '支付失败']);
                }
            } else {
                return back()->with(['err' => '已支付']);
            }
        } else {//批量订单支付
            $data = ModelsOrderModel::whereIn('id', explode(',', $ids))->get();
            foreach ($data as $v) {
                $price = $v->total_price - $v->paid_price;//要支付的钱
                if ($price > 0) {
                    $time = date('Y-m-d H:i:s');
                    if ($v->payment_details){
                        $details = unserialize($v->payment_details);
                        $details[] = ['price' => $price, 'time' => $time];
                        $details = serialize($details);
                    } else {
                        $details[] = ['price' => $price, 'time' => $time];
                        $details = serialize($details);
                    }
                    $order = [
                        'pay_status' => 2,
                        'pay_at' => $time,
                        'paid_price' => $price,
                        'payment_details' => $details
                    ];
                    ModelsOrderModel::where('user_id', $uid)->where('id', $v->id)->update($order);
                }
            }
            return redirect('/user/myOrderGoodsOut');
        }
    }

    /**
     * 出售商品-商品详情-web
     */
    public function infoWeb($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 1))) {//商品不存在 || （存在 && 不是商品 && 不是出售商品）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        if ($info->uid == $uid) {
            return back()->with(['err' => '不允许自购商品']);
        }
        //获取封面
        if (!empty($info->upload_cover_image) && file_exists($info->upload_cover_image)) {
            $info->img = $info->upload_cover_image;
        } else {
            if (!empty($info->cover_img) && file_exists($info->cover_img)) {
                $info->img = $info->cover_img;
            } else {
                $info->img = 'themes/default/assets/images/folder_no_cover.png';
            }
        }
        $info->img = url($info->img);
        //获取属性
        $attr = GoodsAttribute::getAttr($info);
        //数据赋值
        $view = [
            'info' => $info,
            'attr' => $attr
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle('商品详情');
        return $this->theme->scope('user.myOrder.goods.infoWeb', $view)->render();
    }

    /**
     * 出售商品-加入购物车
     */
    public function addCart(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->all();
            //商品
            $info = ModelsContentModel::where('id', $data['goods_id'])
                ->where('is_goods', 1)
                ->where('transaction_mode', 1)
                ->first();
            if (! $info) {
                return response()->json(['code' => '1101', 'msg' => '商品不存在']);
            }
            //属性
            if (isset($data['attr']) && count($data['attr']) > 0) {
                $attr = '';
                $attr_id = [];
                $price = 0.00;
                foreach ($data['attr'] as $v) {
                    $attr_id[] = $v;
                    $tmp = GoodsAttribute::where('id', $v)
                        ->where('goods_id', $info->id)
                        ->first();
                    $attr .= $tmp->Attribute->name . '：' . $tmp->attr_value . '；';
                    $price += $tmp->attr_price;
                }
                $attr_id = implode(',', $attr_id);
            } else {
                $price = 0.00;
                $attr = $attr_id = null;
            }
            $info->price += $price;
            //查询是否已经存在购物车
            $has = GoodsCart::where('goods_id', $info->id)
                ->where('user_id', $uid)
                ->where('goods_attr_id', $attr_id)
                ->where('is_effective', 'Y')
                ->first();
            if ($has) {//更新数量
                if (GoodsCart::where('id', $has->id)->increment('goods_number', $data['number'])) {
                    return response()->json(['code' => 1000, 'msg' => '更新购物车成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '更新购物车失败']);
                }
            } else {//新增购物车
                $cart = [
                    'user_id' => $uid,
                    'shop_id' => $info->uid,
                    'goods_id' => $info->id,
                    'goods_name' => $info->title,
                    'goods_price' => $info->price,
                    'goods_number' => $data['number'],
                    'goods_attr' => $attr,
                    'goods_attr_id' => $attr_id
                ];
                if (GoodsCart::create($cart)) {
                    return response()->json(['code' => 1000, 'msg' => '加入购物车成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '加入购物车失败']);
                }
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-立即购买
     */
    public function buyNow(Request $request)
    {
        $uid = Auth::user()->id;
        $data = $request->all();
        //商品
        $info = ModelsContentModel::where('id', $data['goods_id'])
            ->where('is_goods', 1)
            ->where('transaction_mode', 1)
            ->first();
        if (! $info) {
            return back()->with(['err' => '不存在该商品']);
        }
        //属性
        if (isset($data['attr']) && count($data['attr']) > 0) {
            $attr = '';
            $attr_id = [];
            $price = 0.00;
            foreach ($data['attr'] as $v) {
                $attr_id[] = $v;
                $tmp = GoodsAttribute::where('id', $v)
                    ->where('goods_id', $info->id)
                    ->first();
                $attr .= $tmp->Attribute->name . '：' . $tmp->attr_value . '；';
                $price += $tmp->attr_price;
            }
            $attr_id = implode(',', $attr_id);
        } else {
            $price = 0.00;
            $attr = $attr_id = null;
        }
        $info->price += $price;
        //查询是否已经存在购物车
        $has = GoodsCart::where('goods_id', $info->id)
            ->where('user_id', $uid)
            ->where('goods_attr_id', $attr_id)
            ->where('is_effective', 'Y')
            ->first();
        if ($has) {//更新数量
            if (! GoodsCart::where('id', $has->id)->update(['goods_number' => $data['number']])) {
                return back()->with(['err' => '更新购物车失败']);
            }
            $cart_id = $has->id;
        } else {//新增购物车
            $cart = [
                'user_id' => $uid,
                'shop_id' => $info->uid,
                'goods_id' => $info->id,
                'goods_name' => $info->title,
                'goods_price' => $info->price,
                'goods_number' => $data['number'],
                'goods_attr' => $attr,
                'goods_attr_id' => $attr_id
            ];
            $create = GoodsCart::create($cart);
            if (! $create) {
                return back()->with(['err' => '加入购物车失败']);
            }
            $cart_id = $create->id;
        }
        return redirect()->route('myOrder.myGoodsBuy', ['ids' => $cart_id]);
    }

    /**
     * 出售商品-已卖出的商品
     */
    public function myGoodsIn(Request $request)
    {
        //获取列表
        $list = ModelsOrderModel::where('shop_id', Auth::user()->id)
            ->where('transaction_mode', 1);
        $get = $request->get('name', null);
        if ($get && $get != 'order') {
            $list->where('order_status', 1);
            switch ($get) {
                case 'pay_status':
                    $list->where('pay_status', 1);
                    break;
                case 'post_wait':
                    $list->where('pay_status', 2)
                        ->where('post_status', 1);
                    break;
                case 'post_suc':
                    $list->where('pay_status', 2)
                        ->where('post_status', 2);
                    break;
                case 'shop_evaluate':
                    $list->where('pay_status', 2)
                        ->where('post_status', 3)
                        ->where('shop_evaluate', 'N');
                    break;
            }
        }
        $perPage = $request->get('perPage') ? $request->get('perPage') : 10;
        $list = $list->latest()->paginate($perPage);
        //订单状态
        $status = [
            [
                'name' => 'order',
                'desc' => '所有订单'
            ],
            [
                'name' => 'pay_status',
                'desc' => '待付款'
            ],
            [
                'name' => 'post_wait',
                'desc' => '待发货'
            ],
            [
                'name' => 'post_suc',
                'desc' => '待收货'
            ],
            [
                'name' => 'shop_evaluate',
                'desc' => '待评价'
            ]
        ];
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all(),
            'status' => $status
        ];
        $this->theme->setTitle('已卖出的商品');
        return $this->theme->scope('user.myOrder.goods.myGoodsIn', $view)->render();
    }

    /**
     * 出售商品-已卖出的商品-物流详情
     */
    public function inPostInfo($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if ($info->order_status != 1 || $info->pay_status != 2 || $info->post_status < 2) {
            return back()->with(['err' => '订单信息错误']);
        }
        $post = [
            'status' => 1001,
            'msg' => '暂无物流信息',
            'time' => date('Y-m-d H:i:s'),
            'result' => [
                'number' => '',
                'type' => '',
                'list' => '',
                'deliverystatus' => -1,
                'issign' => -1
            ],
        ];
        if (!empty($info->post_number) && $info->express) {
            $post = $this->expressQuery($info->post_number, $info->express->express_code);
        }
        //用户地址
        $region = [
            $info->province,
            $info->city,
            $info->area
        ];
        foreach ($region as &$v) {
            $v = DistrictModel::getDistrictName($v);
        }
        $info->address = implode('-', $region) . ' ' . $info->address;
        //数据赋值
        $view = [
            'info' => $info,
            'post' => $post
        ];
        $this->theme->setTitle('包裹信息');
        return $this->theme->scope('user.myOrder.goods.postIn', $view)->render();
    }

    /**
     * 出售商品-已卖出的商品-订单详情
     */
    public function goodsInInfo($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        //用户地址
        $region = [
            $info->province,
            $info->city,
            $info->area
        ];
        foreach ($region as &$v) {
            $v = DistrictModel::getDistrictName($v);
        }
        $info->address = implode('-', $region) . ' ' . $info->address;
        //数据赋值
        $view = [
            'info' => $info
        ];
        $this->theme->setTitle('订单详情');
        return $this->theme->scope('user.myOrder.goods.goodsInInfo', $view)->render();
    }

    /**
     * 出售商品-已卖出的商品-开始发货
     */
    public function goodsDelivery($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = ModelsOrderModel::where('id', $id)
                ->where('shop_id', $uid)
                ->first();
            if ($ret) {
                $express = Express::where('status', 'on')->get();
                $view = [
                    'express' => $express,
                    'info' => $ret
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view('goods.delivery', $view)->render()
                ]);
            } else {
                return response()->json(['code' => '1004', 'msg' => '订单取消失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-已卖出的商品-开始发货处理
     */
    public function delivery(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->all();
            if (intval($data['id']) <= 0) {
                return response()->json(['code' => '1001','msg' => '非法操作']);
            } elseif (intval($data['express_id']) <= 0) {
                return response()->json(['code' => '1110','msg' => '请选择物流公司']);
            } elseif (empty($data['post_number'])) {
                return response()->json(['code' => '1110','msg' => '请输入物流单号']);
            }
            $info = ModelsOrderModel::where('id', $data['id'])
                ->where('shop_id', $uid)
                ->first();
            if (! $info) {
                return response()->json(['code' => '1001','msg' => '参数错误']);
            }
            unset($data['id'], $data['post_number_confirm']);
            $data['post_status'] = 2;
            $data['post_at'] = date('Y-m-d H:i:s');
            $ret = ModelsOrderModel::where('id', $info->id)
                ->where('shop_id', $uid)
                ->update($data);
            if ($ret) {
                return response()->json(['code' => '1000','msg' => '发货成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '发货失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-已卖出的商品-商户评价
     */
    public function shopEvaluate($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if ($info->order_status != 1 || $info->post_status != 3 || $info->pay_status != 2) {
            return back()->with(['err' => '订单信息错误']);
        }
        $evaluate = [
            [
                'name' => '好评',
                'value' => '1',
                'number' => 1
            ],
            [
                'name' => '中评',
                'value' => '2',
                'number' => 2
            ],
            [
                'name' => '差评',
                'value' => '3',
                'number' => 3
            ]
        ];

        //数据赋值
        $view = [
            'info' => $info,
            'evaluate' => $evaluate
        ];
        $this->theme->setTitle('订单评价');
        return $this->theme->scope('user.myOrder.goods.shopEvaluate', $view)->render();
    }

    /**
     * 出售商品-已卖出的商品-商户评价处理
     */
    public function makeShopEvaluate(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'shop_evaluate' => 'required|integer',
            'order_id' => 'required|integer',
            'shop' => 'required'
        ], [
            'shop_evaluate.required' => '请选择-评价-等级'
        ]);
        $uid = Auth::user()->id;
        $data = $request->except('_token', 'shop', 'id', 'order_id');
        $status = $request->get('user');
        $order_id = $request->get('order_id');
        $id = $request->get('id');
        $info = ModelsOrderModel::select('id', 'shop_evaluate', 'user_id')
            ->where('id', $order_id)
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if (intval($id) > 0) {//更新数据
            if ($info->shop_evaluate == 'Y') {
                $result = ModelsOrderEvaluateModel::where('id', $id)->update($data);
            } else {
                $result = DB::transaction(function () use ($data, $id, $order_id)
                {
                    ModelsOrderModel::where('id', $order_id)->update(['shop_evaluate' => 'Y']);
                    ModelsOrderEvaluateModel::where('id', $id)->update($data);
                });
                $result = is_null($result) ? true : false;
            }
        } else {//新增数据
            $data['user_id'] = $info->user_id;
            $data['shop_id'] = $uid;
            $data['order_id'] = $info->id;
            $result = DB::transaction(function () use ($data)
            {
                ModelsOrderModel::where('id', $data['order_id'])->update(['shop_evaluate' => 'Y']);
                ModelsOrderEvaluateModel::create($data);
            });
            $result = is_null($result) ? true : false;
        }
        if ($result) {
            return redirect("/user/goods/order/inInfo/{$info->id}");
        } else {
            return back()->with(['err' => '评价失败']);
        }
    }

    /**
     * 出售商品-已购买的商品
     */
    public function myGoodsOut(Request $request)
    {
        //获取列表
        $list = ModelsOrderModel::where('user_id', Auth::user()->id)
            ->where('transaction_mode', 1);
        $get = $request->get('name', null);
        if ($get && $get != 'order') {
            $list->where('order_status', 1);
            switch ($get) {
                case 'pay_status':
                    $list->where('pay_status', 1);
                    break;
                case 'post_wait':
                    $list->where('pay_status', 2)
                        ->where('post_status', 1);
                    break;
                case 'post_suc':
                    $list->where('pay_status', 2)
                        ->where('post_status', 2);
                    break;
                case 'user_evaluate':
                    $list->where('pay_status', 2)
                        ->where('post_status', 3)
                        ->where('user_evaluate', 'N');
                    break;
            }
        }
        $perPage = $request->get('perPage') ? $request->get('perPage') : 10;
        $list = $list->latest()->paginate($perPage);
        //订单状态
        $status = [
            [
                'name' => 'order',
                'desc' => '所有订单'
            ],
            [
                'name' => 'pay_status',
                'desc' => '待付款'
            ],
            [
                'name' => 'post_wait',
                'desc' => '待发货'
            ],
            [
                'name' => 'post_suc',
                'desc' => '待收货'
            ],
            [
                'name' => 'user_evaluate',
                'desc' => '待评价'
            ]
        ];
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all(),
            'status' => $status
        ];
        $this->theme->setTitle('已购买的商品');
        return $this->theme->scope('user.myOrder.goods.myGoodsOut', $view)->render();
    }

    /**
     * 出售商品-已购买的商品-取消订单
     */
    public function cancelGoodsOrder($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = ModelsOrderModel::where('id', $id)
                ->where('user_id', $uid)
                ->update([
                    'order_status' => 2
                ]);
            if ($ret) {
                return response()->json(['code' => '1000', 'msg' => '订单取消成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '订单取消失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-已购买的商品-确认收货
     */
    public function sureGoodsOrder($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $ret = ModelsOrderModel::where('id', $id)
                ->where('user_id', $uid)
                ->update([
                    'post_status' => 3
                ]);
            if ($ret) {
                //TODO:打款给商家
                return response()->json(['code' => '1000', 'msg' => '确认收货成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '确认收货失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 出售商品-已购买的商品-订单详情
     */
    public function goodsOutInfo($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        //用户地址
        $region = [
            $info->province,
            $info->city,
            $info->area
        ];
        foreach ($region as &$v) {
            $v = DistrictModel::getDistrictName($v);
        }
        $info->address = implode('-', $region) . ' ' . $info->address;
        //数据赋值
        $view = [
            'info' => $info
        ];
        $this->theme->setTitle('订单详情');
        return $this->theme->scope('user.myOrder.goods.goodsOutInfo', $view)->render();
    }

    /**
     * 出售商品-已购买的商品-用户评价
     */
    public function userEvaluate($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if ($info->order_status != 1 || $info->post_status != 3 || $info->pay_status != 2) {
            return back()->with(['err' => '订单信息错误']);
        }
        $evaluate = [
            [
                'name' => '好评',
                'value' => '1',
                'number' => 1
            ],
            [
                'name' => '中评',
                'value' => '2',
                'number' => 2
            ],
            [
                'name' => '差评',
                'value' => '3',
                'number' => 3
            ]
        ];

        //数据赋值
        $view = [
            'info' => $info,
            'evaluate' => $evaluate
        ];
        $this->theme->setTitle('订单评价');
        return $this->theme->scope('user.myOrder.goods.userEvaluate', $view)->render();
    }

    /**
     * 出售商品-已购买的商品-用户评价处理
     */
    public function makeUserEvaluate(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'user_evaluate' => 'required|integer',
            'task_quality_star' => 'required|integer',
            'working_attitude_star' => 'required|integer',
            'making_speed_star' => 'required|integer',
            'order_id' => 'required|integer',
            'user' => 'required'
        ], [
            'user_evaluate.required' => '请选择-评价-等级',
            'task_quality_star.required' => '请选择-描述相符-星级',
            'working_attitude_star.required' => '请选择-服务态度-星级',
            'making_speed_star.required' => '请选择-物流服务-星级',
        ]);
        $uid = Auth::user()->id;
        $data = $request->except('_token', 'user', 'id', 'order_id');
        $status = $request->get('user');
        $order_id = $request->get('order_id');
        $id = $request->get('id');
        $info = ModelsOrderModel::select('id', 'user_evaluate', 'shop_id')
            ->where('id', $order_id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if (intval($id) > 0) {//更新数据
            if ($info->user_evaluate == 'Y') {
                $result = ModelsOrderEvaluateModel::where('id', $id)->update($data);
            } else {
                $result = DB::transaction(function () use ($data, $id, $order_id)
                {
                    ModelsOrderModel::where('id', $order_id)->update(['user_evaluate' => 'Y']);
                    ModelsOrderEvaluateModel::where('id', $id)->update($data);
                });
                $result = is_null($result) ? true : false;
            }
        } else {//新增数据
            $data['user_id'] = $uid;
            $data['shop_id'] = $info->shop_id;
            $data['order_id'] = $info->id;
            $result = DB::transaction(function () use ($data)
            {
                 ModelsOrderModel::where('id', $data['order_id'])->update(['user_evaluate' => 'Y']);
                 ModelsOrderEvaluateModel::create($data);
            });
            $result = is_null($result) ? true : false;
        }
        if ($result) {
            return redirect("/user/goods/order/outInfo/{$info->id}");
        } else {
            return back()->with(['err' => '评价失败']);
        }
    }

    /**
     * 出售商品-已购买的商品-物流详情
     */
    public function goodsPostInfo($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        if ($info->order_status != 1 || $info->pay_status != 2 || $info->post_status < 2) {
            return back()->with(['err' => '订单信息错误']);
        }
        $post = [
            'status' => 1001,
            'msg' => '暂无物流信息',
            'time' => date('Y-m-d H:i:s'),
            'result' => [
                'number' => '',
                'type' => '',
                'list' => '',
                'deliverystatus' => -1,
                'issign' => -1
            ],
        ];
        if (!empty($info->post_number) && $info->express) {
            $post = $this->expressQuery($info->post_number, $info->express->express_code);
        }
        //用户地址
        $region = [
            $info->province,
            $info->city,
            $info->area
        ];
        foreach ($region as &$v) {
            $v = DistrictModel::getDistrictName($v);
        }
        $info->address = implode('-', $region) . ' ' . $info->address;
        //数据赋值
        $view = [
            'info' => $info,
            'post' => $post
        ];
        $this->theme->setTitle('包裹信息');
        return $this->theme->scope('user.myOrder.goods.post', $view)->render();
    }

    /**
     * 获取物流信息（阿里云Api-全国快递物流查询接口-杭州网尚科技有限公司）
     */
    protected function expressQuery($number = '1202516745301', $type = 'YUNDA')
    {
        $key = $type . '_' . $number;
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $host = 'http://jisukdcx.market.alicloudapi.com';
            $path = '/express/query';
            $method = 'GET';
            $appcode = '7c2f10b20b154781a57c66663ed5185f';//密钥
            $headers = [];
            array_push($headers, 'Authorization:APPCODE ' . $appcode);
            $querys = "number={$number}&type={$type}";
            $bodys = "";
            $url = $host . $path . '?' . $querys;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos('$' . $host, 'https://')) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $data = json_decode(curl_exec($curl),true);
            if ($data['status'] == '0') {
                if ($data['result']['deliverystatus'] == '3') {
                    Cache::put($key, $data, 3 * 24 * 60);//已签收，缓存3天
                } else {
                    Cache::put($key, $data, 2 * 60);//其它，缓存2小时
                }
            } else {
                $data = [
                    'status' => 1001,
                    'msg' => '暂无物流信息',
                    'time' => date('Y-m-d H:i:s'),
                    'result' => [
                        'number' => '',
                        'type' => '',
                        'list' => '',
                        'deliverystatus' => -1,
                        'issign' => -1
                    ],
                ];
                Cache::put($key, $data, 2 * 60);//其它，缓存2小时
            }

        }
        return $data;
    }

    /**
     * 获取快递公司信息（阿里云Api-全国快递物流查询接口-杭州网尚科技有限公司）
     */
    protected function expressType()
    {
        //缓存一周
        $key = 'express_type';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $host = 'http://jisukdcx.market.alicloudapi.com';
            $path = '/express/type';
            $method = 'GET';
            $appcode = '7c2f10b20b154781a57c66663ed5185f';
            $headers = [];
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = '';
            $bodys = '';
            $url = $host . $path;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos('$' . $host, 'https://')) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $data = json_decode(curl_exec($curl),true);
            Cache::put($key, $data, 7 * 24 * 60);
        }
        //录入数据库
        /*foreach ($data['result'] as $v) {
            $create = [
                'express_name' => $v['name'],
                'express_code' => $v['type'],
                'express_letter' => $v['letter'],
                'express_tel' => $v['tel'],
                'express_number' => $v['number']
            ];
            Express::create($create);
        }*/
        //更新数据
        foreach ($data['result'] as $v) {
            $has = Express::where('express_code', $v['type'])->first();
            if ($has) {
                $update = [
                    'express_name' => $v['name'],
                    'express_letter' => $v['letter'],
                    'express_tel' => $v['tel'],
                    'express_number' => $v['number']
                ];
                Express::where('express_code', $v['type'])->update($update);
            } else {
                $create = [
                    'express_name' => $v['name'],
                    'express_code' => $v['type'],
                    'express_letter' => $v['letter'],
                    'express_tel' => $v['tel'],
                    'express_number' => $v['number']
                ];
                Express::create($create);
            }
        }
        return $data;
    }

    /* /------------------------------------- 出售素材 -------------------------------------\ */

    /**
     * 出售素材-订单填写页
     */
    public function materialBuy($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 3))) {//商品不存在 || （存在 && 不是商品 && 不是出售素材）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        if ($info->uid == $uid) {
            return back()->with(['err' => '不允许自购商品']);
        }
        $ext = ModelsOrderMaterialModel::where('user_id', $uid)->where('models_id', $id)->first();
        if ($ext && $ext->auth == 'Y') {
            return back()->with(['err' => '您已购买该素材']);
        }
        //获取封面
        if (!empty($info->upload_cover_image) && file_exists($info->upload_cover_image)) {
            $info->img = $info->upload_cover_image;
        } else {
            if (!empty($info->cover_img) && file_exists($info->cover_img)) {
                $info->img = $info->cover_img;
            } else {
                $info->img = 'themes/default/assets/images/folder_no_cover.png';
            }
        }
        $info->img = url($info->img);
        $view = [
            'info' => $info
        ];
        $this->setToken();
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->title . '-购买素材-填写订单信息');
        return $this->theme->scope('user.myOrder.material.materialBuy', $view)->render();
    }

    /**
     * 出售素材-正式下单
     */
    public function materialAdd(Request $request)
    {
        $id = $request->get('id');
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 3))) {//商品不存在 || （存在 && 不是商品 && 不是出售素材）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        if ($info->uid == $uid) {
            return back()->with(['err' => '不允许自购商品']);
        }
        //已经购买则不允许再购买
        $ext = ModelsOrderMaterialModel::where('user_id', $uid)->where('models_id', $id)->first();
        if ($ext && $ext->auth == 'Y') {
            return back()->with(['err' => '您已购买该素材']);
        }
        $material = [//出售素材信息
            'user_id' => $uid,
            'shop_id' => $info->uid,
            'models_id' => $info->id
        ];
        $order = [//订单信息
            'order_sn' => 'M' . $this->getOrderSn(),
            'user_id' => $uid,
            'from_at' => $this->checkWap() ? 'wap' : 'web',
            'total_price' => $info->price,
            'transaction_mode' => 3,
            'shop_id' => $info->uid,
            'action_id' => 0
        ];
        $orderGoods = [//订单商品信息
            'order_id' => 0,
            'goods_id' => $info->id,
            'goods_name' => $info->title,
            'goods_number' => 1,
            'goods_price' => $info->price
        ];

        //新增数据
        if ($ext) {
            $order['action_id'] = $ext->id;
            $result = DB::transaction(function () use ($order, $orderGoods)
            {
                $result = ModelsOrderModel::create($order);
                $orderGoods['order_id'] = $result['id'];
                ModelsOrderGoodsModel::create($orderGoods);
                return $result;
            });
        } else {
            $result = DB::transaction(function () use ($material, $order, $orderGoods)
            {
                $result = ModelsOrderMaterialModel::create($material);
                $order['action_id'] = $result['id'];
                $result = ModelsOrderModel::create($order);
                $orderGoods['order_id'] = $result['id'];
                ModelsOrderGoodsModel::create($orderGoods);
                return $result;
            });
        }
        if ($result) {//下单成功
            return redirect('/user/material/makeSure/' . $result->id);
        } else {
            return back()->with(['err' => '下单失败']);
        }
    }

    /**
     * 出售素材-确认订单
     */
    public function materialMakeSure($id = 0)
    {
        //是否合法
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {
            return back()->with(['err' => '参数错误']);
        }
        $view = [
            'info' => $info
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->material->goods->title . '-购买素材-确认订单信息');
        return $this->theme->scope('user.myOrder.material.materialMakeSure', $view)->render();
    }

    /**
     * 出售素材-模拟支付
     */
    public function materialPay($id = 0)
    {
        $info =  ModelsOrderModel::find($id);
        $price = $info->total_price - $info->paid_price;//要支付的钱
        if ($price > 0) {
            $time = date('Y-m-d H:i:s');
            if ($info->payment_details){
                $details = unserialize($info->payment_details);
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            } else {
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            }
            $order = [
                'pay_status' => 2,
                'pay_at' => $time,
                'paid_price' => $price,
                'payment_details' => $details
            ];
            $uid = Auth::user()->id;
            $material = [
                'auth' => 'Y'
            ];
            $status = DB::transaction(function () use ($info, $uid, $material, $order)
            {
                ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                ModelsOrderMaterialModel::where('user_id', $uid)->where('id', $info->material->id)->update($material);
            });
            $status = is_null($status) ? true : false;
            if ($status) {
                return redirect('/user/myOrderMaterialOut');
            } else {
                return redirect('/');
            }
        } else {
            return redirect('/user/myOrderMaterialOut');
        }
    }

    /**
     * 已购买的素材
     */
    public function myMaterialOut(Request $request)
    {
        //获取列表
        $list = ModelsOrderMaterialModel::where('user_id', Auth::user()->id);
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('已购买的素材');
        return $this->theme->scope('user.myOrder.material.myMaterialOut', $view)->render();
    }

    /**
     * 已出售的素材
     */
    public function myMaterialIn(Request $request)
    {
        //获取列表
        $list = ModelsOrderMaterialModel::where('shop_id', Auth::user()->id);
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('已出售的素材');
        return $this->theme->scope('user.myOrder.material.myMaterialIn', $view)->render();
    }

    /**
     * 出售素材-取消订单
     */
    public function materialCancel($id = 0)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误'];
        //是否合法
        if ($id <= 0) {
            $ret['msg'] = '非法操作';
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {
            return response()->json($ret);
        }
        //更新数据
        $status = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->update(['order_status' => 2]);
        if ($status) {
            $ret = ['code' => 'success', 'msg' => ''];
        }
        return response()->json($ret);
    }

    /**
     * 出售素材-下载压缩文件
     */
    public function downloadZip($id = 0)
    {
        // 获取购买的作品并校验合法性
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $contentData = ModelsContentModel::select('price', 'baseData', 'title')->where('id', $id)->first();
        $uid = Auth::user()->id;
        if ($contentData->price != '0.00') {
            $ext = ModelsOrderMaterialModel::where('user_id', $uid)->where('models_id', $id)->first();
            if (! $ext || ($ext && $ext->auth == 'N')) {
                return back()->with(['err' => '没有下载权限']);
            }
            $ext = ModelsContentModel::select('title', 'baseData', 'transaction_mode')->where('id', $ext->models_id)->first();

            if ($ext->transaction_mode != 3) {
                return back()->with(['err' => '该商品已不允许下载']);
            }
            // 下载
            $dir = dirname($ext->baseData);
            $title = $ext->title;
        } else {
            $dir = dirname($contentData->baseData);
            $title = $contentData->title;
        }

        $zip = new ZipArchive();
        $filename = uniqid() . '.zip';

        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true) {
            return back()->with(['err' => '文件创建失败']);
        }
        $fileList = $this->visitFile($dir);
        if (count($fileList) == 0) {
            return back()->with(['err' => '没有可用的文件']);
        }
        // 创建目录
        foreach ($fileList as $folder) {
            if (! file_exists($folder) || ! is_dir($folder)) {
                continue;
            }
            $relative = str_replace($dir . '/', '', $folder);
            $zip->addEmptyDir($relative);
        }
        // 添加文件
        foreach ($fileList as $file) {
            if (! file_exists($file) || ! is_file($file)) {
                continue;
            }
            $relative = str_replace($dir . '/', '', $file);
            $zip->addFile($file, $relative);
        }
        $zip->close();
        if(! file_exists($filename)){
            return back()->with(['err' => '文件不存在']);
        }
        // 头部信息
        @header("Cache-Control: public");
        @header("Content-Description: File Transfer");
        @header('Content-disposition: attachment; filename=' . $title . '_' . basename($filename));
        @header("Content-Type: application/zip");
        @header("Content-Transfer-Encoding: binary");
        @header('Content-Length: ' . filesize($filename));
        // 输出文件
        @readfile($filename);
        // 删除文件
        @unlink($filename);
        ModelsOrderMaterialModel::where('user_id', $uid)->where('models_id', $id)->increment('downloads');
    }

    /* /------------------------------------- 查看付费 -------------------------------------\ */

    /**
     * 查看付费-未购买时的页面
     */
    public function viewPayDenied($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 2))) {//商品不存在 || （存在 && (不是商品 || 不是查看付费)）
            return back()->with(['err' => '参数错误']);
        }
        $view = [
            'id' => $id
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle('您需要进行付费后才能查看');
        return $this->theme->scope('user.myOrder.viewPay.viewPayDenied', $view)->render();
    }

    /**
     * 查看付费-填写订单页面
     */
    public function viewPayBuy($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 2))) {//商品不存在 || （存在 && 不是商品 && 不是查看付费）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        if ($info->uid == Auth::user()->id) {
            return back()->with(['err' => '不允许自购商品']);
        }
        //是否是永久付费
        $ext = ModelsOrderViewModel::where('user_id', $uid)->where('models_id', $info->id)->first();
        if ($ext && $ext->permanent == 'Y') {
            return back()->with(['err' => '您已开启永久查看权限']);
        }
        //获取封面
        if (!empty($info->upload_cover_image) && file_exists($info->upload_cover_image)) {
            $info->img = $info->upload_cover_image;
        } else {
            if (!empty($info->cover_img) && file_exists($info->cover_img)) {
                $info->img = $info->cover_img;
            } else {
                $info->img = 'themes/default/assets/images/folder_no_cover.png';
            }
        }
        $info->img = url($info->img);
        $info->views_price = unserialize($info->views_price);
        $view = [
            'info' => $info
        ];
        $this->setToken();
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->title . '-付费查看-填写订单信息');
        return $this->theme->scope('user.myOrder.viewPay.viewPayBuy', $view)->render();
    }

    /**
     * 查看付费-正式下单
     */
    public function viewPayAdd(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $arr = ['once', 'month', 'permanent'];
        if ($id <= 0 && !in_array($type, $arr)) {//非法操作
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 2))) {//商品不存在 || （存在 && 不是商品 && 不是查看付费）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        if ($info->uid == $uid) {
            return back()->with(['err' => '不允许自购商品']);
        }
        $view = [//查看付费订单信息
            'user_id' => $uid,
            'shop_id' => $info->uid,
            'models_id' => $info->id,
        ];
        $info->views_price = unserialize($info->views_price);
        $order = [//订单信息
            'order_sn' => 'V' . $this->getOrderSn(),
            'user_id' => $uid,
            'from_at' => $this->checkWap() ? 'wap' : 'web',
            'total_price' => $info->price,
            'transaction_mode' => 2,
            'shop_id' => $info->uid,
            'view_id' => 0,
            'type' => $type
        ];
        $orderGoods = [//订单商品信息
            'order_id' => 0,
            'goods_id' => $info->id,
            'goods_name' => $info->title,
            'goods_number' => 1,
            'goods_price' => $info->price
        ];
        $ext = ModelsOrderViewModel::where('user_id', $uid)->where('models_id', $info->id)->first();
        if ($ext) {
            if ($ext->permanent == 'Y') {//已经是永久模式
                return back()->with(['err' => '您已开启永久查看权限']);
            }
            $order['view_id'] = $ext->id;
            $result = DB::transaction(function () use ($order, $orderGoods)
            {
                $result = ModelsOrderModel::create($order);
                $orderGoods['order_id'] = $result['id'];
                ModelsOrderGoodsModel::create($orderGoods);
                return $result;
            });
        } else {
            $result = DB::transaction(function () use ($view, $order, $orderGoods)
            {
                $result = ModelsOrderViewModel::create($view);
                $order['view_id'] = $result['id'];
                $result = ModelsOrderModel::create($order);
                $orderGoods['order_id'] = $result['id'];
                ModelsOrderGoodsModel::create($orderGoods);
                return $result;
            });
        }
        if ($result) {//下单成功
            return redirect('/user/viewPay/makeSure/' . $result->id);
        } else {
            return back()->with(['err' => '下单失败']);
        }
    }

    /**
     * 查看付费-确认订单
     */
    public function viewMakeSure($id = 0)
    {
        //是否合法
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {
            return back()->with(['err' => '参数错误']);
        }
        $view = [
            'info' => $info
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->view->goods->title . '-付费查看-确认订单信息');
        return $this->theme->scope('user.myOrder.viewPay.viewMakeSure', $view)->render();
    }

    /**
     * 查看付费-取消订单
     */
    public function viewCancel($id = 0)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误'];
        //是否合法
        if ($id <= 0) {
            $ret['msg'] = '非法操作';
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {//订单信息不存在
            return response()->json($ret);
        }
        //更新数据
        $status = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->update(['order_status' => 2]);
        if ($status) {
            $ret = ['code' => 'success', 'msg' => ''];
        }
        return response()->json($ret);
    }

    /**
     * 查看付费-模拟支付
     */
    public function viewPay($id = 0)
    {
        $info =  ModelsOrderModel::find($id);
        $price = $info->total_price - $info->paid_price;//要支付的钱
        if ($price > 0) {
            $time = date('Y-m-d H:i:s');
            if ($info->payment_details){
                $details = unserialize($info->payment_details);
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            } else {
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            }
            $order = [
                'pay_status' => 2,
                'pay_at' => $time,
                'paid_price' => $price,
                'payment_details' => $details
            ];
            $uid = Auth::user()->id;
            $status = true;
            if ($info->type == 'once') {//次付
                $status = DB::transaction(function () use ($uid, $info, $order)
                {
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->increment('times');
                });
            } elseif ($info->type == 'month') {//月付
                $days = 30;//$days = $info->goods->count('goods_number') * 30;
                if ($info->view->expiration_date) {// 存在时间
                    $time = date('Y-m-d H:i:s');
                    if ($time <= $info->view->expiration_date) {// 未过期
                        $date = date('Y-m-d H:i:s', strtotime($info->view->expiration_date . ' +' . $days . ' day'));
                    } else {
                        $date = date('Y-m-d H:i:s', strtotime('+' . $days . ' day'));
                    }
                } else {
                    $date = date('Y-m-d H:i:s', strtotime('+' . $days . ' day'));
                }
                $view = [
                    'expiration_date' => $date
                ];
                $status = DB::transaction(function () use ($uid, $info, $order, $view)
                {
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->update($view);
                });
            } elseif ($info->type == 'permanent') {//永久
                $status = DB::transaction(function () use ($uid, $info, $order)
                {
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->update(['permanent' => 'Y']);
                });
            } else {
                return redirect('/');
            }
            $status = is_null($status) ? true : false;
            if ($status) {
                return redirect('/user/myOrderViewOut');
            } else {
                return redirect('/');
            }
        } else {
            return redirect('/user/myOrderViewOut');
        }
    }

    /**
     * 已购买的查看付费
     */
    public function myViewOut(Request $request)
    {
        //获取列表
        $list = ModelsOrderViewModel::where('user_id', Auth::user()->id);
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('已购买的查看付费');
        return $this->theme->scope('user.myOrder.viewPay.myViewOut', $view)->render();
    }

    /**
     * 已出售的查看付费
     */
    public function myViewIn(Request $request)
    {
        //获取列表
        $list = ModelsOrderViewModel::where('shop_id', Auth::user()->id);
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //数据赋值
        $view = [
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('已出售的查看付费');
        return $this->theme->scope('user.myOrder.viewPay.myViewIn', $view)->render();
    }

    /* /------------------------------------- 任务订单 -------------------------------------\ */

    /**
     * 我发布的任务
     */
    public function myTaskOut(Request $request)
    {
        //获取列表
        $list = ModelsOrderServiceModel::where('user_id', Auth::user()->id);
        if ($request->get('task_status'))
            $list = $list->where('task_status', $request->get('task_status'));
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //任务状态
        $status = [
            ['name' => '所有', 'id' => 0],
            ['name' => '待承接', 'id' => 1],
            ['name' => '待验收', 'id' => 2],
            ['name' => '可验收', 'id' => 3],
            ['name' => '待评价', 'id' => 4],
            ['name' => '圆满完成', 'id' => 5]
        ];
        foreach ($status as &$v) {
            if ($v['id'] && $v['id'] != 5) {
                $v['count'] = ModelsOrderServiceModel::where('user_id', Auth::user()->id)
                    ->where('task_status', $v['id'])->count();
                $v['count'] = $v['count'] >= 100 ? 99 : $v['count'];
            } else {
                $v['count'] = 0;
            }
        }
        //数据赋值
        $view = [
            'status' => $status,
            'list' => $list,
            'merge' => $request->all()
        ];
        $this->theme->setTitle('我发布的任务');
        return $this->theme->scope('user.myOrder.task.myTaskOut', $view)->render();
    }

    /**
     * 我发布的任务-订单创建页面
     */
    public function myTaskOutBuy($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 4))) {//商品不存在 || （存在 && 不是商品 && 不是定制服务）
            return back()->with(['err' => '参数错误']);
        }
        if ($info->uid == Auth::user()->id) {
            return back()->with(['err' => '不允许自购商品']);
        }
        //获取封面
        if (!empty($info->upload_cover_image) && file_exists($info->upload_cover_image)) {
            $info->img = $info->upload_cover_image;
        } else {
            if (!empty($info->cover_img) && file_exists($info->cover_img)) {
                $info->img = $info->cover_img;
            } else {
                $info->img = 'themes/default/assets/images/folder_no_cover.png';
            }
        }
        $info->img = url($info->img);
        // 获取分类
        $cate = TaskCateModel::where('pid', 0)->get();
        $info->models_pid = TaskCateModel::where('id', $info->models_id)->value('pid');
        $category = TaskCateModel::where('pid', $info->models_pid)->get();
        // 数据赋值
        $view = [
            'info' => $info,
            'cate' => $cate,
            'category' => $category
        ];
        $this->setToken();
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->title . '-定制服务-填写订单信息');
        return $this->theme->scope('user.myOrder.task.myTaskBuy', $view)->render();
    }

    /**
     * 我发布的任务-订单创建
     */
    public function myTaskOutAdd(Request $request)
    {
        //是否合法
        $id = $request->get('id');
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::find($id);
        if (!$info || ($info && ($info->is_goods != 1 || $info->transaction_mode != 4))) {//商品不存在 || （存在 && 不是商品 && 不是定制服务）
            return back()->with(['err' => '参数错误']);
        }
        $uid = Auth::user()->id;
        //如果为作者本人，则不允许购买
        if ($info->uid == $uid) {
            return back()->with(['err' => '不允许自购商品']);
        }
        //表单验证
        $this->validate($request, [
            'models_pid' => 'required',
            'models_id' => 'required',
            'demand_name' => 'required',
            'design_demand' => 'required',
            'total_price' => [
                'required',
                'regex:/^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/'
            ],
            'finish_time' => 'required',
            'mobile' => [
                'required',
                'digits:11',
                'regex:/^1[34578]\d{9}$/',
            ]
        ]);
        $data = $request->except('_token', 'order_token', 'agree');
        //新增数据
        $order = [//订单
            'order_sn' => 'T' . $this->getOrderSn(),
            'user_id' => $uid,
            'from_at' => $this->checkWap() ? 'wap' : 'web',
            'mobile' => $data['mobile'],
            'total_price' => $data['total_price'],
            'transaction_mode' => 4,
            'shop_id' => $info->uid
        ];
        $orderGoods = [
            'order_id' => 0,
            'goods_id' => $info->id,
            'goods_name' => $info->title,
            'goods_number' => 1,
            'goods_price' => $info->price
        ];
        $service = [
            'order_id' => 0,
            'user_id' => $uid,
            'shop_id' => $info->uid,
            'models_id' => $data['models_id'],
            'demand_name' => $data['demand_name'],
            'design_demand' => $data['design_demand'],
            'finish_time' => $data['finish_time'],
        ];
        // 上传的文件
        $file = $request->file('user_file');
        $file = $this->upload($file, $order['order_sn']);
        if ($file['code']) {
            $service['user_file'] = $file['filePath'];
            $service['user_file_extension'] = $file['extension'];
        }
        //执行
        $result = DB::transaction(function () use ($order, $orderGoods, $service)
        {
            $result = ModelsOrderModel::create($order);
            $orderGoods['order_id'] = $result['id'];
            ModelsOrderGoodsModel::create($orderGoods);
            $service['order_id'] = $result['id'];
            ModelsOrderServiceModel::create($service);
            return $result;
        });
        if($result) {//创建成功
            return redirect('/user/task/makeSure/' . $result->id);
        } else {
            return back()->with(['err' => '下单失败']);
        }
    }

    /**
     * 我发布的任务-订单确认页面
     */
    public function myTaskOutMakeSure($id = 0)
    {
        //是否合法
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {//订单信息不存在
            return back()->with(['err' => '参数错误']);
        }
        $view = [
            'info' => $info
        ];
        $this->initTheme('myOrder.viewDenied');//主题初始化
        $this->theme->setTitle($info->service->goods->goods_name . '-定制服务-确认订单信息');
        return $this->theme->scope('user.myOrder.task.myTaskOutMakeSure', $view)->render();
    }

    /**
     * 我发布的任务-详情
     */
    public function myTaskOutInfo($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => 'error', 'msg' => '参数错误!']);
        }
        $task = ModelsOrderServiceModel::find($id);
        $info = $task->toArray();
        $info['paid_price'] = $task->order->paid_price;
        $number = 3;// 驳回次数
        if (!empty($task->user_reject)){
            $info['user_reject'] = unserialize($task->user_reject);
            $times = $number - count($info['user_reject']);
            $info['reject_times'] = $times < 0 ? 0 : $times;
        } else {
            $info['user_reject'] = '';
            $info['reject_times'] = $number;
        }
        if ($task->order->evaluate) {
            $info['evaluate'] = $task->order->evaluate;
        } else {
            $info['evaluate'] = [];
        }
        return response()->json(['code' => 'success', 'data' => $info]);
    }

    /**
     * 我发布的任务-取消订单
     */
    public function myTaskOutCancel($id = 0)
    {
        $msg = ['code' => 'error', 'msg' => '参数错误!'];
        if ($id <= 0) {
            $msg['msg'] = '非法操作';
            return response()->json($msg);
        }
        $info = ModelsOrderModel::find($id);
        if (!$info) {
            return response()->json($msg);
        }
        if ($info->order_status == 2) {
            $msg['msg'] = '已经取消';
            return response()->json($msg);
        }
        $price = $info->paid_price;
        /* 如果是支付过的订单，发起退款 */
        $time = date('Y-m-d H:i:s');
        if ($price > 0) {
            //TODO: 退款流程...
            //退款成功，修改订单信息
            if ($info->refund_details){
                $details = unserialize($info->refund_details);
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            } else {
                $details[] = ['price' => $price, 'time' => $time];
                $details = serialize($details);
            }
            $update = [
                'order_status' => 2,
                'refund_status' => 2,
                'refund_at' => $time,
                'paid_price' => $info->paid_price - $price,
                'refund_price' => $price,
                'refund_details' => $details
            ];
        } else {
            $update = [
                'order_status' => 2,
            ];
        }
        $uid = Auth::user()->id;
        $status = DB::transaction(function () use ($id, $uid, $update)
        {
            ModelsOrderModel::where('id', $id)->where('user_id', $uid)->update($update);
            ModelsOrderServiceModel::where('order_id', $id)->where('user_id', $uid)->update(['task_status' => 0]);
        });
        if (is_null($status)) {
            $msg = ['code' => 'success'];
        }
        return response()->json($msg);
    }

    /**
     * 我发布的任务-模拟支付
     */
    public function myTaskOutPay($id = 0)
    {
        if ($id <= 0)//非法操作
            return redirect('/user/myOrderTaskOut');
        $info = ModelsOrderModel::find($id);
        if (!$info)//订单信息不存在
            return redirect('/user/myOrderTaskOut');
        $price = $info->total_price - $info->paid_price;//本次应支付金额
        if ($price <= 0)//已支付
            return redirect('/user/myOrderTaskOut');
        //TODO：支付流程...
        /* 支付成功，修改订单信息 */
        $time = date('Y-m-d H:i:s');
        if ($info->payment_details){
            $details = unserialize($info->payment_details);
            $details[] = ['price' => $price, 'time' => $time];
            $details = serialize($details);
        } else {
            $details[] = ['price' => $price, 'time' => $time];
            $details = serialize($details);
        }
        $update = [
            'pay_status' => 2,
            'pay_at' => $time,
            'payment_details' => $details,
            'paid_price' => $info->paid_price + $price
        ];
        $uid = Auth::user()->id;
        $status = DB::transaction(function () use ($id, $uid, $update)
        {
            ModelsOrderModel::where('id', $id)->where('user_id', $uid)->update($update);
            ModelsOrderServiceModel::where('order_id', $id)->where('user_id', $uid)->update(['task_status' => 1]);
        });
        if (is_null($status))//执行成功
            return redirect('/user/myOrderTaskOut');
        else
            return redirect('/user/myOrderTaskOut');
    }

    /**
     * 我发布的任务-获取要修改订单的信息
     */
    public function myTaskOutEdit($id = 0)
    {
        $msg = ['code' => 'error', 'msg' => '参数错误!'];
        if ($id <= 0){
            $msg['msg'] = '非法操作';
            return response()->json($msg);
        }
        $info = ModelsOrderServiceModel::find($id);
        if (!$info) {
            return response()->json($msg);
        }
        $data = $info->toArray();
        $data['models_pid'] = TaskCateModel::where('id', $data['models_id'])->value('pid');
        $data['total_price'] = $info->order->total_price;
        $data['mobile'] = $info->order->mobile;
        $data['goods_number'] = $info->goods->goods_number;
        $msg = ['code' => 'success', 'data' => $data];
        return response()->json($msg);
    }

    /**
     * 我发布的任务-修改订单处理
     */
    public function myTaskOutUpdate(Request $request)
    {
        $id = $request->get('id');
        if ($id <= 0 ) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsOrderServiceModel::find($id);
        if (!$info) {
            return back()->with(['err' => '参数错误']);
        }
        //表单验证
        $this->validate($request, [
            'models_pid' => 'required',
            'models_id' => 'required',
            'demand_name' => 'required',
            'design_demand' => 'required',
            'total_price' => [
                'required',
                'regex:/^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/'
            ],
            'finish_time' => 'required',
            'mobile' => [
                'required',
                'digits:11',
                'regex:/^1[34578]\d{9}$/',
            ]
        ]);
        //数据处理
        $data = $request->all();
        $file = $request->file('user_file');// 上传的文件
        $sms = $data['sms'];// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        $order = [// 订单信息
            'total_price' => $data['total_price'],
            'mobile' => $data['mobile']
        ];
        $orderGoods = [// 订单商品信息
            'goods_number' => $data['goods_number']
        ];
        $orderService = [// 订单服务信息
            'models_id' => $data['models_id'],
            'demand_name' => $data['demand_name'],
            'design_demand' => $data['design_demand'],
            'finish_time' => $data['finish_time'],
        ];
        $file = $this->upload($file, $info->order->order_sn);// 如果存在文件执行文件上传操作
        if ($file['code']) {
            $orderService['user_file'] = $file['filePath'];
            $orderService['user_file_extension'] = $file['extension'];
            if (!empty($info->user_file) && file_exists($info->user_file)) {
                @unlink($info->user_file);
            }
        }
        if ($data['total_price'] <= $info->order->total_price) {
            unset($order['total_price']);
        } else {
            $orderService['task_status'] = 0;
            $order['pay_status'] = 1;
        }
        $uid = Auth::user()->id;
        $order_id = $info->order_id;
        $status = DB::transaction(function () use ($order_id, $uid, $order, $orderGoods, $orderService)
        {
            ModelsOrderModel::where('id', $order_id)->where('user_id', $uid)->update($order);
            ModelsOrderGoodsModel::where('order_id', $order_id)->update($orderGoods);
            ModelsOrderServiceModel::where('order_id', $order_id)->where('user_id', $uid)->update($orderService);
        });
        if (is_null($status)) {
            return redirect('/user/myOrderTaskOut')->with(['msg' => '修改成功！']);
        } else {
            return redirect('/user/myOrderTaskOut')->with(['msg' => '修改失败！']);
        }
    }

    /**
     * 我发布的任务-驳回任务
     */
    public function myTaskOutReject(Request $request)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误!'];
        $data = $request->except('_token');
        if ($data['id'] <= 0) {
            $ret['msg'] = '非法操作';
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderServiceModel::where('id', $data['id'])->where('user_id', $uid)->first();
        if (!$info) {
            return response()->json($ret);
        }
        $number = 3;
        $reject = [];
        if (!empty($info->user_reject)) {
            $reject = unserialize($info->user_reject);
            if (count($reject) >= $number) {
                return response()->json($ret);
            }
        }
        $reject[] = [
            'title' => $data['title'],
            'content' => $data['content'],
            'time' => date('Y-m-d H:i:s')
        ];
        $update = [
            'user_reject' => serialize($reject),
            'task_status' => 2,
            'works_id' => null
        ];
        $status = ModelsOrderServiceModel::where('id', $data['id'])->where('user_id', $uid)->update($update);
        $sms = $data['sms'];// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        if ($status) {
            $ret = ['code' => 'success'];
        }
        return response()->json($ret);
    }

    /**
     * 我发布的任务-验收任务
     */
    public function myTaskOutCheck(Request $request, $id)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误!'];
        if ($id <= 0) {
            $ret['msg'] = '非法操作';
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderServiceModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {
            return response()->json($ret);
        }
        $sms = $request->get('sms');// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        $update = [
            'task_status' => 4
        ];
        $status = ModelsOrderServiceModel::where('id', $id)->where('user_id', $uid)->update($update);
        if ($status) {
            // TODO: 进行打款给创客...
            $ret = ['code' => 'success'];
        }
        return response()->json($ret);
    }

    /**
     * 我发布的任务-评价任务
     */
    public function myTaskOutEvaluation(Request $request, $id)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误!'];
        if ($id <= 0) {
            $ret['msg'] = '非法操作';
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderServiceModel::where('id', $id)->where('user_id', $uid)->first();
        if (!$info) {
            return response()->json($ret);
        }
        $sms = $request->get('sms');// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        $data = $request->except('_token', 'sms');
        if (!$info->order->evaluate) {// 双方均没有评价
            $data['order_id'] = $info->order_id;
            $data['shop_id'] = $info->shop_id;
            $data['user_id'] = $info->user_id;
            $status = DB::transaction(function () use ($id, $uid, $data)
            {
                ModelsOrderEvaluateModel::create($data);
                ModelsOrderServiceModel::where('id', $id)->where('user_id', $uid)->update(['user_evaluate' => 2]);
            });
            $status = is_null($status) ? true : false;
        } else{// 至少有一方做出评价
            if ($info->user_evaluate == 1) {//买家未作出评价，进行评价
                if ($info->shop_evaluate == 2) {//卖家已作出评价
                    $status = DB::transaction(function () use ($info, $uid, $data)
                    {
                        ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('user_id', $uid)->update($data);
                        ModelsOrderServiceModel::where('id', $info->id)->where('user_id', $uid)->update(['user_evaluate' => 2, 'task_status' => 5]);
                        /* 将作品转移至买家的默认文件夹并加密 */
                        ModelsContentModel::where('uid', $info->shop_id)
                            ->where('id', $info->works_id)
                            ->update([
                                'uid' => $info->user_id,
                                'is_private' => 1,
                                'folder_id' => 0,
                                'is_goods' => 0,
                                'price' => 0,
                                'transaction_mode' => 0
                            ]);
                        /* 双方信用值 +1 */
                        UserModel::where('id', $info->shop_id)->increment('credit_value');
                        UserModel::where('id', $info->user_id)->increment('credit_value');
                    });
                } else {//卖家已作出评价
                    $status = DB::transaction(function () use ($info, $uid, $data)
                    {
                        ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('user_id', $uid)->update($data);
                        ModelsOrderServiceModel::where('id', $info->id)->where('user_id', $uid)->update(['user_evaluate' => 2]);
                    });
                }
                $status = is_null($status) ? true : false;
            } else {//买家已作出评价，修改评价
                $status = ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('user_id', $uid)->update($data);
            }
        }
        if ($status) {
            $ret = ['code' => 'success'];
        }
        return response()->json($ret);
    }

    /**
     * 我参与的任务
     */
    public function myTaskIn(Request $request)
    {
        //获取列表
        $list = ModelsOrderServiceModel::where('shop_id', Auth::user()->id);
        if ($request->get('task_status'))
            $list = $list->where('task_status', $request->get('task_status'));
        $perPage = $request->get('perPage') ? $request->get('perPage') : 20;
        $list = $list->latest()->paginate($perPage);
        //任务状态
        $status = [
            ['name' => '所有', 'id' => 0],
            ['name' => '待承接', 'id' => 1],
            ['name' => '待提交', 'id' => 2],
            ['name' => '待验收', 'id' => 3],
            ['name' => '待评价', 'id' => 4],
            ['name' => '圆满完成', 'id' => 5]
        ];
        foreach ($status as &$v) {
            if ($v['id'] && $v['id'] != 5) {
                $v['count'] = ModelsOrderServiceModel::where('shop_id', Auth::user()->id)
                    ->where('task_status', $v['id'])->count();
                $v['count'] = $v['count'] >= 100 ? 99 : $v['count'];
            } else {
                $v['count'] = 0;
            }
        }
        //默认文件夹的作品总数
        $uid = Auth::User()->id;
        $ids = ModelsOrderServiceModel::where('shop_id', $uid)->lists('works_id')->toArray();
        $ids = array_filter(array_unique($ids));
        $defaultFolderCount = ModelsContentModel::where('uid', $uid)
            ->where('enroll_status', 0)
            ->where('folder_id', 0)
            ->where('is_goods', 0)
            ->whereNotIn('id', $ids)
            ->count();
        //数据赋值
        $view = [
            'status' => $status,
            'list' => $list,
            'merge' => $request->all(),
            'defaultFolderCount' => $defaultFolderCount
        ];
        $this->theme->setTitle('我参与的任务');
        return $this->theme->scope('user.myOrder.task.myTaskIn', $view)->render();
    }

    /**
     * 我参与的任务-承接任务
     */
    public function myTaskInAccept(Request $request, $id = 0)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误！'];
        if ($id <= 0) {
            return response()->json($ret);
        }
        $info = ModelsOrderServiceModel::find($id);
        if (!$info) {
            return response()->json($ret);
        }
        $status = ModelsOrderServiceModel::where('id', $id)->where('shop_id', Auth::user()->id)->update(['task_status' => 2]);
        $sms = $request->get('sms');// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        if ($status) {
            $ret = ['code' => 'success', 'msg' => ''];
        }
        return response()->json($ret);
    }

    /**
     * 我参与的任务-提交任务
     */
    public function myTaskInSubmit(Request $request, $id = 0)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误！'];
        $sms = $request->get('sms') ? $request->get('sms') : 'N';// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        $service_id = $request->get('service');
        if ($id <= 0 || $service_id <=0) {
            return response()->json($ret);
        }
        $info = ModelsContentModel::where('id', $id)->where('uid', Auth::user()->id)->first();
        $service = ModelsOrderServiceModel::where('id', $service_id)->where('shop_id', Auth::user()->id)->first();
        if (!$info || !$service) {
            return response()->json($ret);
        }
        $update = [];
        //获取封面
        if (!empty($info->upload_cover_image) && file_exists($info->upload_cover_image)) {
            $img = $info->upload_cover_image;
        } else {
            if (!empty($info->cover_img) && file_exists($info->cover_img)) {
                $img = $info->cover_img;
            } else {
                $img = 'themes/default/assets/images/folder_no_cover.png';
            }
        }
        $img = $this->copy($img, $service->order->order_sn);
        if ($img === false) {
            return response()->json($ret);
        }
        $update['shop_file_cover'] = $img;
        //获取压缩文件
        $zip = $this->getZip($info->baseData, $service->order->order_sn);
        if ($zip === false) {
            return response()->json($ret);
        }
        $update['shop_file'] = $zip;
        $update['shop_file_extension'] = 'zip';
        $update['task_status'] = 3;
        $update['works_id'] = $id;
        //判断封面及压缩文件是否存在
        if (!empty($service->shop_file_cover) && file_exists($service->shop_file_cover)) {
            @unlink($service->shop_file_cover);
        }
        if (!empty($service->shop_file) && file_exists($service->shop_file)) {
            @unlink($service->shop_file);
        }
        //进行数据更新
        $status = ModelsOrderServiceModel::where('id', $service_id)->where('shop_id', Auth::user()->id)->update($update);
        if ($status) {
            $ret = ['code' => 'success'];
        }
        return response()->json($ret);
    }

    /**
     * 我参与的任务-评价任务
     */
    public function myTaskInEvaluation(Request $request, $id)
    {
        $ret = ['code' => 'error', 'msg' => '参数错误!'];
        if ($id <= 0) {
            return response()->json($ret);
        }
        $uid = Auth::user()->id;
        $info = ModelsOrderServiceModel::where('id', $id)->where('shop_id', $uid)->first();
        if (!$info) {
            return response()->json($ret);
        }
        $sms = $request->get('sms');// 是否对本次修改发送短信（暂未启用），【Y-发送短信，N-不发送短信】
        $data = $request->except('_token', 'sms');
        if (!$info->order->evaluate) {// 双方均没有评价
            $data['order_id'] = $info->order_id;
            $data['shop_id'] = $info->shop_id;
            $data['user_id'] = $info->user_id;
            $status = DB::transaction(function () use ($id, $uid, $data)
            {
                ModelsOrderEvaluateModel::create($data);
                ModelsOrderServiceModel::where('id', $id)->where('shop_id', $uid)->update(['shop_evaluate' => 2]);
            });
            $status = is_null($status) ? true : false;
        } else {// 至少有一方做出评价
            if ($info->shop_evaluate == 1) {//卖家未作出评价，进行评价
                if ($info->user_evaluate == 2) {//买家家已作出评价
                    $status = DB::transaction(function () use ($info, $uid, $data)
                    {
                        ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('shop_id', $uid)->update($data);
                        ModelsOrderServiceModel::where('id', $info->id)->where('shop_id', $uid)->update(['shop_evaluate' => 2, 'task_status' => 5]);
                        /* 将作品转移至买家的默认文件夹并加密 */
                        ModelsContentModel::where('uid', $uid)
                            ->where('id', $info->works_id)
                            ->update([
                                'uid' => $info->user_id,
                                'is_private' => 1,
                                'folder_id' => 0,
                                'is_goods' => 0,
                                'price' => 0,
                                'transaction_mode' => 0
                            ]);
                        /* 双方信用值 +1 */
                        UserModel::where('id', $info->shop_id)->increment('credit_value');
                        UserModel::where('id', $info->user_id)->increment('credit_value');
                    });
                } else {//卖家已作出评价
                    $status = DB::transaction(function () use ($info, $uid, $data)
                    {
                        ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('shop_id', $uid)->update($data);
                        ModelsOrderServiceModel::where('id', $info->id)->where('shop_id', $uid)->update(['shop_evaluate' => 2]);
                    });
                }
                $status = is_null($status) ? true : false;
            } else {//卖家已作出评价，修改评价
                $status = ModelsOrderEvaluateModel::where('id', $info->order->evaluate->id)->where('shop_id', $uid)->update($data);
            }
        }
        if ($status) {
            $ret = ['code' => 'success'];
        }
        return response()->json($ret);
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
            $ids = ModelsOrderServiceModel::where('shop_id', $uid)->lists('works_id')->toArray();
            $ids = array_filter(array_unique($ids));
            $list = ModelsFolderModel::select('id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time')
                ->where('uid', $uid)
                ->whereNotIn('id', $ids)
                ->paginate($perPage);
            foreach ($list as &$v) {
                $v['count'] = ModelsContentModel::where('uid', $uid)
                    ->where('enroll_status', 0)
                    ->where('folder_id', $v['id'])
                    ->where('is_goods', 0)
                    ->whereNotIn('id', $ids)
                    ->count();
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
            $ids = ModelsOrderServiceModel::where('shop_id', $uid)->lists('works_id')->toArray();
            $ids = array_filter(array_unique($ids));
            $list = ModelsContentModel::select('id', 'title', 'cover_img', 'upload_cover_image', 'is_private')
                ->where('uid', $uid)
                ->where('folder_id', $id)
                ->where('enroll_status', 0)
                ->where('is_goods', 0)
                ->whereNotIn('id', $ids)
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

    /* /------------------------------------- 以下是文件操作类 -------------------------------------\ */

    /**
     * 雇主-上传文件
     */
    public function upload($file, $orderSn, $path = 'user', $parentPath = 'service')
    {
        if (!$file) {
            return ['code' => false, 'msg' => '未上传文件'];
        }
        if ($file->isValid()) {
            $allowed_extensions = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'pdf', 'rar', 'txt', 'zip'];
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension && in_array($extension, $allowed_extensions)) {
                $filename = uniqid() . '.' . $extension;
                $destinationPath = 'Uploads/Goods/' . $parentPath . '/' . $path . '/' . date('Y-m-d') . '/' . $orderSn . '/';
                if ($file->move($destinationPath, $filename)) {
                    $filePath = $destinationPath . $filename;
                    return ['code' => true, 'filePath' => $filePath, 'extension' => $extension ];
                } else {
                    return ['code' => false, 'msg' => $file->getErrorMessage()];
                }
            } else {
                return ['code' => false, 'msg' => '支持的文件类型为：' . implode(',', $allowed_extensions)];
            }
        } else {
            return ['code' => false, 'msg' => $file->getErrorMessage()];
        }
    }

    /**
     * 雇主-下载文件
     */
    public function myTaskInDownload($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsOrderServiceModel::select('demand_name', 'user_file', 'user_file_extension')
            ->where('id', $id)
            ->where('shop_id', Auth::user()->id)
            ->first();
        if (!$info) {
            return back()->with(['err' => '参数错误']);
        }
        if (!empty($info['user_file']) && file_exists($info['user_file'])) {
            return response()->download($info['user_file'], "{$info['demand_name']}_" . time() . ".{$info['user_file_extension']}");
        } else {
            return back()->with(['err' => '【' . $info['demand_name'] . '】文件不存在，下载失败']);
        }
    }

    /**
     * 创客-下载文件
     */
    public function myTaskOutDownload($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsOrderServiceModel::select('demand_name', 'shop_file', 'shop_file_extension')
            ->where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();
        if (!$info) {
            return back()->with(['err' => '参数错误']);
        }
        if (!empty($info['shop_file']) && file_exists($info['shop_file'])) {
            return response()->download($info['shop_file'], "{$info['demand_name']}_" . time() . ".{$info['shop_file_extension']}");
        } else {
            return back()->with(['err' => '【' . $info['demand_name'] . '】文件不存在，下载失败！']);
        }
    }

    /**
     * 创客-复制文件
     */
    protected function copy($source, $orderSn, $path = 'shop', $parentPath = 'service')
    {
        if (empty($source) || !file_exists($source)) {
            return false;
        }
        $info = pathinfo($source);
        $filename = uniqid() . '.' . $info['extension'];
        $dir = 'Uploads/Goods/' . $parentPath . '/' . $path . '/' . date('Y-m-d') . '/' . $orderSn . '/';
        $target = $this->getTargetFile($dir, $filename);
        if (!@copy($source, $target)) {
            return false;
        }
        @chmod($target, 0666 & ~umask());
        return $target;
    }

    /**
     * 创客-打包压缩文件
     */
    protected function getZip($source, $orderSn, $path = 'shop', $parentPath = 'service')
    {
        if (empty($source) || !file_exists($source)) {
            return false;
        }
        $filename = uniqid() . '.zip';
        $directory = dirname($source);
        $dir = 'Uploads/Goods/' . $parentPath . '/' . $path . '/' . date('Y-m-d') . '/' . $orderSn . '/';
        $target = $this->getTargetFile($dir, $filename);
        $this->zip($directory, $target);
        if (!file_exists($target)) {
            return false;
        }
        @chmod($target, 0666 & ~umask());
        return $target;
    }

    /**
     * 创建文件夹并生成完整文件路径
     */
    protected function getTargetFile($directory, $name)
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        } elseif (!is_writable($directory)) {
            return false;
        }
        $target = rtrim($directory, '/\\') . '/' . $name;
        return $target;
    }

    /* /------------------------------------- 以下是下单类 -------------------------------------\ */
    /**
     * 设置令牌
     */
    public function setToken()
    {
        Session::put('order_token', md5(microtime(true)));
        Session::save();
    }

    /**
     * 校验令牌是否一致
     */
    public function validToken()
    {
        $ret = $_REQUEST['order_token'] === Session::get('order_token', 'session') ? true : false;
        $this->setToken();
        return $ret;
    }

    /**
     * 获取订单号
     */
    public function getOrderSn()
    {
        mt_srand((double) microtime() * 1000000);
        do {
            $orderSn = date('Ymd') . str_pad(mt_rand(1, 99999), 8, '0', STR_PAD_LEFT);
        } while ($this->checkOrderSn($orderSn));
        return $orderSn;
    }

    /**
     * 保证订单号唯一（一定程度上）
     */
    public function checkOrderSn($orderSn = '')
    {
        return ModelsOrderModel::where('order_sn', $orderSn)->value('order_sn');
    }

    /* /------------------------------------- 以下是zip压缩类 -------------------------------------\ */
    private $ctrl_dir = [];
    private $datasec  = [];

    /**
     * 压缩部分
     */
    var $fileList = [];
    public function visitFile($path)
    {
        global $fileList;
        $path = str_replace("\\", "/", $path);
        $fdir = dir($path);
        while (($file = $fdir->read()) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $pathSub = preg_replace("*/{2,}*", "/", $path . "/" . $file);// 替换多个反斜杠
            $fileList[] = is_dir($pathSub) ? $pathSub . "/" : $pathSub;
            if (is_dir($pathSub)) {
                $this->visitFile($pathSub);
            }
        }
        $fdir->close();
        return $fileList;
    }

    private function unix2DosTime($unixtime = 0)
    {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);
        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }
        return (($timearray['year'] - 1980) << 25)
            | ($timearray['mon'] << 21)
            | ($timearray['mday'] << 16)
            | ($timearray['hours'] << 11)
            | ($timearray['minutes'] << 5)
            | ($timearray['seconds'] >> 1);
    }

    var $old_offset = 0;
    private function addFile($data, $filename, $time = 0)
    {
        $filename = str_replace('\\', '/', $filename);
        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
            . '\x' . $dtime[4] . $dtime[5]
            . '\x' . $dtime[2] . $dtime[3]
            . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');
        $fr       = "\x50\x4b\x03\x04";
        $fr      .= "\x14\x00";
        $fr      .= "\x00\x00";
        $fr      .= "\x08\x00";
        $fr      .= $hexdtime;
        $unc_len  = strlen($data);
        $crc      = crc32($data);
        $zdata    = gzcompress($data);
        $c_len    = strlen($zdata);
        $zdata    = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $fr      .= pack('V', $crc);
        $fr      .= pack('V', $c_len);
        $fr      .= pack('V', $unc_len);
        $fr      .= pack('v', strlen($filename));
        $fr      .= pack('v', 0);
        $fr      .= $filename;
        $fr      .= $zdata;
        $fr      .= pack('V', $crc);
        $fr      .= pack('V', $c_len);
        $fr      .= pack('V', $unc_len);
        $this->datasec[] = $fr;
        $new_offset      = strlen(implode('', $this->datasec));
        $cdrec  = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";
        $cdrec .= "\x14\x00";
        $cdrec .= "\x00\x00";
        $cdrec .= "\x08\x00";
        $cdrec .= $hexdtime;
        $cdrec .= pack('V', $crc);
        $cdrec .= pack('V', $c_len);
        $cdrec .= pack('V', $unc_len);
        $cdrec .= pack('v', strlen($filename) );
        $cdrec .= pack('v', 0 );
        $cdrec .= pack('v', 0 );
        $cdrec .= pack('v', 0 );
        $cdrec .= pack('v', 0 );
        $cdrec .= pack('V', 32 );
        $cdrec .= pack('V', $this->old_offset );
        $this->old_offset = $new_offset;
        $cdrec .= $filename;
        $this->ctrl_dir[] = $cdrec;
    }

    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    private function file()
    {
        $data    = implode('', $this->datasec);
        $ctrldir = implode('', $this->ctrl_dir);
        return   $data
            . $ctrldir
            . $this->eof_ctrl_dir
            . pack('v', sizeof($this->ctrl_dir))
            . pack('v', sizeof($this->ctrl_dir))
            . pack('V', strlen($ctrldir))
            . pack('V', strlen($data))
            . "\x00\x00";
    }

    /**
     * 压缩到服务器
     */
    public function zip($dir, $saveName)
    {
        if (@!function_exists('gzcompress')) {
            return false;
        }
        ob_end_clean();
        $filelist = $this->visitFile($dir);
        if (count($filelist) == 0) {
            return false;
        }
        foreach ($filelist as $file) {
            if (!file_exists($file) || !is_file($file))
                continue;
            $fd       = fopen($file, "rb");
            $content  = @fread($fd, filesize($file));
            fclose($fd);
            // 1.删除$dir的字符(./folder/file.txt删除./folder/)
            // 2.如果存在/就删除(/file.txt删除/)
            $file = substr($file, strlen($dir));
            if (substr($file, 0, 1) == "\\" || substr($file, 0, 1) == "/") {
                $file = substr($file, 1);
            }
            $this->addFile($content, $file);
        }
        $out = $this->file();
        $fp = fopen($saveName, "wb");
        fwrite($fp, $out, strlen($out));
        fclose($fp);
        return true;
    }

    /**
     * 压缩并直接下载（暂时废弃）
     */
    public function zipAndDownload($dir)
    {
        if (@!function_exists('gzcompress')) {
            return false;
        }
        ob_end_clean();
        $filelist = $this->visitFile($dir);
        if (count($filelist) == 0) {
            return false;
        }
        foreach ($filelist as $file) {
            if (!file_exists($file) || !is_file($file)) {
                continue;
            }
            $fd       = fopen($file, "rb");
            $content  = @fread($fd, filesize($file));
            fclose($fd);
            // 1.删除$dir的字符(./folder/file.txt删除./folder/)
            // 2.如果存在/就删除(/file.txt删除/)
            $file = substr($file, strlen($dir));
            if (substr($file, 0, 1) == "\\" || substr($file, 0, 1) == "/") {
                $file = substr($file, 1);
            }
            $this->addFile($content, $file);
        }
        $out = $this->file();
        @header('Content-Transfer-Encoding: binary');
        @header('Content-Type: application/zip');
        @header('Content-Disposition: attachment; filename=Farticle' . date("YmdHis", time()) . '.zip');
        @header('Pragma: no-cache');
        @header('Expires: 0');
        print($out);
    }

    /**
     * 解压部分
     */
    private function readCentralDir($zip, $zipfile)
    {
        $size     = filesize($zipfile);
        $max_size = ($size < 277) ? $size : 277;
        @fseek($zip, $size - $max_size);
        $pos   = ftell($zip);
        $bytes = 0x00000000;
        while ($pos < $size) {
            $byte  = @fread($zip, 1);
            $bytes = ($bytes << 8) | Ord($byte);
            $pos++;
            if ($bytes == 0x504b0506) {
                break;
            }
        }
        $data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', fread($zip, 18));
        $centd['comment']      = ($data['comment_size'] != 0) ? fread($zip, $data['comment_size']) : '';  // 注释
        $centd['entries']      = $data['entries'];
        $centd['disk_entries'] = $data['disk_entries'];
        $centd['offset']       = $data['offset'];
        $centd['disk_start']   = $data['disk_start'];
        $centd['size']         = $data['size'];
        $centd['disk']         = $data['disk'];
        return $centd;
    }

    private function readCentralFileHeaders($zip)
    {
        $binary_data = fread($zip, 46);
        $header      = unpack('vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $binary_data);
        $header['filename'] = ($header['filename_len'] != 0) ? fread($zip, $header['filename_len']) : '';
        $header['extra']    = ($header['extra_len']    != 0) ? fread($zip, $header['extra_len'])    : '';
        $header['comment']  = ($header['comment_len']  != 0) ? fread($zip, $header['comment_len'])  : '';
        if ($header['mdate'] && $header['mtime']) {
            $hour    = ($header['mtime']  & 0xF800) >> 11;
            $minute  = ($header['mtime']  & 0x07E0) >> 5;
            $seconde = ($header['mtime']  & 0x001F) * 2;
            $year    = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month   = ($header['mdate']  & 0x01E0) >> 5;
            $day     = $header['mdate']   & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
        } else {
            $header['mtime'] = time();
        }
        $header['stored_filename'] = $header['filename'];
        $header['status'] = 'ok';
        if (substr($header['filename'], -1) == '/') {// 判断是否文件夹
            $header['external'] = 0x41FF0010;
        }
        return $header;
    }

    private function readFileHeader($zip)
    {
        $binary_data = fread($zip, 30);
        $data        = unpack('vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $binary_data);
        $header['filename']        = fread($zip, $data['filename_len']);
        $header['extra']           = ($data['extra_len'] != 0) ? fread($zip, $data['extra_len']) : '';
        $header['compression']     = $data['compression'];
        $header['size']            = $data['size'];
        $header['compressed_size'] = $data['compressed_size'];
        $header['crc']             = $data['crc'];
        $header['flag']            = $data['flag'];
        $header['mdate']           = $data['mdate'];
        $header['mtime']           = $data['mtime'];
        if ($header['mdate'] && $header['mtime']) {
            $hour    = ($header['mtime']  & 0xF800) >> 11;
            $minute  = ($header['mtime']  & 0x07E0) >> 5;
            $seconde = ($header['mtime']  & 0x001F) * 2;
            $year    = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month   = ($header['mdate']  & 0x01E0) >> 5;
            $day     = $header['mdate']   & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
        } else {
            $header['mtime'] = time();
        }
        $header['stored_filename'] = $header['filename'];
        $header['status']          = "ok";
        return $header;
    }

    private function extractFile($header, $to, $zip)
    {
        $header = $this->readfileheader($zip);
        if (substr($to, -1) != "/") {
            $to .= "/";
        }
        if(!@is_dir($to)) {
            @mkdir($to, 0777);
        }
        $pth = explode("/", dirname($header['filename']));
        $pthss = '';
        for ($i=0; isset($pth[$i]); $i++) {
            if (!$pth[$i]) {
                continue;
            }
            $pthss .= $pth[$i] . "/";
            if (!is_dir($to . $pthss)) {
                @mkdir($to . $pthss, 0777);
            }

        }
        if (!($header['external'] == 0x41FF0010) && !($header['external'] == 16)) {
            if ($header['compression'] == 0) {
                $fp = @fopen($to . $header['filename'], 'wb');
                if (!$fp) {
                    return(-1);
                }
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $read_size   = ($size < 2048 ? $size : 2048);
                    $buffer      = fread($zip, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size       -= $read_size;
                }
                fclose($fp);
                touch($to.$header['filename'], $header['mtime']);
            } else {
                $fp = @fopen($to.$header['filename'] . '.gz', 'wb');
                if (!$fp) {
                    return(-1);
                }
                $binary_data = pack('va1a1Va1a1', 0x8b1f, Chr($header['compression']), Chr(0x00), time(), Chr(0x00), Chr(3));
                fwrite($fp, $binary_data, 10);
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $read_size   = ($size < 1024 ? $size : 1024);
                    $buffer      = fread($zip, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size       -= $read_size;
                }
                $binary_data = pack('VV', $header['crc'], $header['size']);
                fwrite($fp, $binary_data, 8);
                fclose($fp);
                $gzp = @gzopen($to . $header['filename'] . '.gz', 'rb') or die("Cette archive est compress!");
                if (!$gzp) {
                    return(-2);
                }
                $fp = @fopen($to . $header['filename'], 'wb');
                if (!$fp) {
                    return(-1);
                }
                $size = $header['size'];
                while ($size != 0) {
                    $read_size   = ($size < 2048 ? $size : 2048);
                    $buffer      = gzread($gzp, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size       -= $read_size;
                }
                fclose($fp);
                gzclose($gzp);
                touch($to . $header['filename'], $header['mtime']);
                @unlink($to . $header['filename'] . '.gz');
            }
        }
        return true;
    }

    /**
     * 解压文件
     */
    public function unZip($zipfile, $to, $index = Array(-1))
    {
        $ok  = 0;
        $zip = @fopen($zipfile, 'rb');
        if (!$zip) {
            return(-1);
        }
        $cdir      = $this->ReadCentralDir($zip, $zipfile);
        $pos_entry = $cdir['offset'];
        if (!is_array($index)) {
            $index = array($index);
        }
        for ($i=0; $index[$i]; $i++) {
            if (intval($index[$i]) != $index[$i] || $index[$i] > $cdir['entries']) {
                return(-1);
            }
        }
        $stat = [];
        for ($i=0; $i<$cdir['entries']; $i++) {
            @fseek($zip, $pos_entry);
            $header          = $this->ReadCentralFileHeaders($zip);
            $header['index'] = $i;
            $pos_entry       = ftell($zip);
            @rewind($zip);
            fseek($zip, $header['offset']);
            if (in_array("-1", $index) || in_array($i, $index)) {
                $stat[$header['filename']] = $this->ExtractFile($header, $to, $zip);
            }
        }
        fclose($zip);
        return $stat;
    }

    /**
     * 其它部分
     */
    public function getZipInnerFilesInfo($zipfile)
    {
        $zip = @fopen($zipfile, 'rb');
        if (!$zip) {
            return(0);
        }
        $centd = $this->ReadCentralDir($zip, $zipfile);
        @rewind($zip);
        @fseek($zip, $centd['offset']);
        $ret = array();
        for ($i=0; $i<$centd['entries']; $i++) {
            $header          = $this->ReadCentralFileHeaders($zip);
            $header['index'] = $i;
            $info = array(
                'filename'        => $header['filename'],                   // 文件名
                'stored_filename' => $header['stored_filename'],            // 压缩后文件名
                'size'            => $header['size'],                       // 大小
                'compressed_size' => $header['compressed_size'],            // 压缩后大小
                'crc'             => strtoupper(dechex($header['crc'])),    // CRC32
                'mtime'           => date("Y-m-d H:i:s",$header['mtime']),  // 文件修改时间
                'comment'         => $header['comment'],                    // 注释
                'folder'          => ($header['external'] == 0x41FF0010 || $header['external'] == 16) ? 1 : 0,  // 是否为文件夹
                'index'           => $header['index'],                      // 文件索引
                'status'          => $header['status']                      // 状态
            );
            $ret[] = $info;
            unset($header);
        }
        fclose($zip);
        return $ret;
    }

    /**
     * 获取压缩文件的注释
     */
    public function getZipComment($zipfile)
    {
        $zip = @fopen($zipfile, 'rb');
        if (!$zip) {
            return(0);
        }
        $centd = $this->ReadCentralDir($zip, $zipfile);
        fclose($zip);
        return $centd['comment'];
    }

    /* /------------------------------------- 以下是终端判断类 -------------------------------------\ */

    /**
     * 判断是电脑端还是移动端 wap-移动端|web-电脑端
     */
    protected function checkWap()
    {
        if (isset($_SERVER['HTTP_VIA'])) return true;
        if (isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])) return true;
        if (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) return true;
        if (strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"VND.WAP.WML") > 0) {
            $br = "wap";
        } else {
            $browser = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
            if (empty($browser)) return true;
            $mobile_os_list = [
                'Google Wireless Transcoder', 'Windows CE', 'WindowsCE', 'Symbian',
                'Android', 'armv6l', 'armv5', 'Mobile', 'CentOS', 'mowser', 'AvantGo',
                'Opera Mobi', 'J2ME/MIDP', 'Smartphone', 'Go.Web', 'Palm', 'iPAQ'
            ];
            $mobile_token_list = [
                'Profile/MIDP', 'Configuration/CLDC-', '160×160', '176×220', '240×240',
                '240×320', '320×240', 'UP.Browser', 'UP.Link', 'SymbianOS', 'PalmOS',
                'PocketPC', 'SonyEricsson', 'Nokia', 'BlackBerry', 'Vodafone', 'BenQ',
                'Novarra-Vision', 'Iris', 'NetFront', 'HTC_', 'Xda_', 'SAMSUNG-SGH',
                'Wapaka', 'DoCoMo', 'iPhone', 'iPod'
            ];
            $found_mobile = $this->checkSubstrs($mobile_os_list, $browser) || $this->checkSubstrs($mobile_token_list, $browser);
            if ($found_mobile) {
                $br = "wap";
            } else {
                $br = "web";
            }
        }
        if ($br == "wap") {
            return true;
        } else {
            return false;
        }
    }

    protected function checkSubstrs($list, $str)
    {
        $flag = false;
        for ($i = 0; $i < count($list); $i++) {
            if (strpos($str,$list[$i]) > 0) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }
}
