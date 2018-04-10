<?php
namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use Illuminate\Http\Request;
use Auth;
use Crypt;
use Session;
use App\Modules\User\Model\ActionLogModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;

class OrderController extends UserCenterController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('orderCenter');//主题初始化
    }

    /**
     * 订单信息（微信端）
     */
    public function orderInfo($id = 0)
    {

        $this->initTheme('myShop.order.orderInfo');
        $this->theme->setTitle('填写订单信息');
        $info = ModelsContentModel::find($id);
        if ($info == null && $info['is_goods'] != 1)
            return redirect('/');
        if (!empty($info['upload_cover_image']) && file_exists($info['upload_cover_image'])) {
            $info['image'] = url($info['upload_cover_image']);
        } else {
            if (!empty($info['cover_img']) && file_exists($info['cover_img'])) {
                $info['image'] = url($info['cover_img']);
            } else {
                $info['image'] = '/themes/default/assets/images/folder_no_cover.png';
            }
        }
        // 查询省信息
        $province = DistrictModel::findTree ( 0 );
        $view = [
            'info' => $info,
            'province' => $province
        ];
        $this->setToken();
        return $this->theme->scope('user.myShop.order.orderInfo', $view)->render();
    }

    /**
     * 订单信息处理（微信端）
     */
    public function orderAdd(Request $request)
    {
        $this->initTheme('myShop.order.orderInfo');
        //防止重复提交订单
        if (!$this->validToken())
            return redirect('/');
        //表单校验
        $this->validate($request, [
            'consignee' => 'required',
            'mobile' => 'required',
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'address' => 'required',
            'number' => 'required',
            'goods_id' => 'required'
        ]);
        //处理数据
        $data = $request->except('order_token', '_token', 'total_price');
        $data['order_sn'] = $this->getOrderSn();
        $info = ModelsContentModel::find($data['goods_id']);
        if ($info == null && $info['is_goods'] != 1)
            return redirect('/');
        $data['total_price'] = $data['number'] * $info['price'];
        $data['user_id'] = empty(Auth::user()->id) ? 0 : Auth::user()->id;
        //生成订单
        $result = ModelsOrderModel::create($data);
        // 判断是否成功生成订单
        if ($result['id'] <= 0)
            return redirect('/');
        //插入商品订单表
        $goods = [
            'order_id' => $result['id'],
            'goods_id' => $info['id'],
            'goods_name' => $info['title'],
            'goods_number' => $data['number'],
            'goods_price' => $info['price']
        ];
        ModelsOrderGoodsModel::create($goods);
        return redirect('/wePay/getOpenId/'.$result['id'])->with('success', '订单创建成功！');
    }

    public function orderConfirm($id){
        $this->initTheme('myShop.order.orderInfo');
        $data = ModelsOrderModel::find($id);
        $orderData = ModelsOrderGoodsModel::where('order_id',$id)->first();
        $province = DistrictModel::where('upid','=',0)->where('id',$data['province'])->value('name');
        $address = $province.'****'.$data['area'];
        $orderGoods = '购买'.$orderData['goods_number'].'件'.$orderData['goods_name'];
        $view = [
            'consignee'     => $data['consignee'],
            'mobile'        => $data['mobile'],
            'address'       => $address,
            'user_desc'     => $data['user_desc'],
            'total_price'   => $data['total_price'],
            'orderGoods'    => $orderGoods,
        ];
        return $this->theme->scope('user.myShop.order.orderConfirm',$view)->render();
    }
    /**
     * 设置令牌
     */
    public function setToken()
    {
        Session::put(['order_token' => md5(microtime(true))]);
    }

    /**
     * 校验令牌是否一致
     */
    function validToken()
    {
        $ret = $_REQUEST['order_token'] === Session::get('order_token') ? true : false;
        $this->setToken();
        return $ret;
    }

    /**
     * 获取订单号
     */
    function getOrderSn()
    {
        mt_srand((double) microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * ajax获取城市、地区数据
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxCity(Request $request) {
        $id = intval ( $request->get ( 'id' ) );
        if (! $id) {
            return response ()->json ( [
                'errMsg' => '参数错误！'
            ] );
        }
        $province = DistrictModel::findTree ( $id );
        // 查询第一个市的数据
        $area = DistrictModel::findTree ( $province [0] ['id'] );
        $data = [
            'province' => $province,
            'area' => $area
        ];
        return response ()->json ( $data );
    }

    /**
     * ajax获取地区的数据
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxArea(Request $request) {
        $id = intval ( $request->get ( 'id' ) );
        if (! $id) {
            return response ()->json ( [
                'errMsg' => '参数错误！'
            ] );
        }
        $area = DistrictModel::findTree ( $id );
        return response ()->json ( $area );
    }
}
