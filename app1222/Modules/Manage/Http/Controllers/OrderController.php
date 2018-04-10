<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use Illuminate\Http\Request;
use App\Modules\Manage\ManagerModel;
use App\Modules\Manage\Permission;
use App\Modules\Manage\Role;
use Theme;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use App\Modules\User\Model\DistrictModel;

class OrderController extends ManageController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('后台管理');
    }

    /**
     * 订单列表
     */
    public function orderList(Request $request)
    {
        $merge = $request->all();
        $orderList = ModelsOrderModel::whereRaw('1 = 1');
        //收货人
        if ($request->get('consignee')) {
            $orderList = $orderList->where('consignee', 'like', '%' . $request->get('consignee') . '%');
        }
        //手机号
        if ($request->get('mobile')) {
            $orderList = $orderList->where('mobile', 'like', '%' . $request->get('mobile') . '%');
        }
        //订单状态
        if ($request->get('order_status')) {
            $orderList = $orderList->where('order_status', $request->get('order_status'));
        }
        //发货状态
        if ($request->get('post_status')) {
            $orderList = $orderList->where('post_status', $request->get('post_status'));
        }
        //支付状态
        if ($request->get('pay_status')) {
            $orderList = $orderList->where('pay_status', $request->get('pay_status'));
        }
        //时间类型
        if($request->get('time_type')){
            // 时间搜索
            if ($request->get('start') && $request->get('end')) {
                $start = date('Y-m-d H:i:s',strtotime($request->get('start')));
                $end   = date('Y-m-d H:i:s',strtotime($request->get('end')));
                $orderList->whereBetween($request->get('time_type'), [$start, $end]);
            }
        }

        $by       = $request->get('by')       ? $request->get('by')       : 'id';
        $order    = $request->get('order')    ? $request->get('order')    : 'desc';
        $paginate = $request->get('paginate') ? $request->get('paginate') : 10;

        $orderList = $orderList->orderBy($by, $order)->paginate($paginate);

        $view = [
            'merge'        => $merge,
            'orderList' => $orderList
        ];
        $this->breadcrumb->add([
            [
                'label' => '订单列表',
                'url'   => '/manage/orderList'
            ],
            ['label' => '订单列表']
        ]);
        $this->theme->set('manageAction', 'manage.order');
        return $this->theme->scope('manage.orderManage.orderList', $view)->render();
    }

    /**
     * 订单详情页
     */
    public function orderDetail($id = 0)
    {
        $info = ModelsOrderModel::find($id);
        $region = [
            $info['province'],
            $info['city'],
            $info['area']
        ];
        foreach ($region as &$v) {
            $v = DistrictModel::getDistrictName($v);
        }
        $info['address'] = implode('-', $region) . ' ' . $info['address'];
        $view = [
            'info' => $info
        ];
        $this->breadcrumb->add([
            [
                'label' => '订单列表',
                'url'   => '/manage/orderList'
            ],
            ['label' => '订单详情']
        ]);
        $this->theme->set('manageAction', 'manage.orderDetail');
        return $this->theme->scope('manage.orderManage.orderDetail', $view)->render();
    }

    /**
     * 订单信息更新
     */
    public function orderUpdate(Request $request)
    {
        $data = $request->except('_token', 'id', 'post_at', 'pay_at');
        $id = $request->get('id');
        if (empty($request->get('post_at')) && $data['post_status'] == 2
            && $data['order_status'] == 1)
            $data['post_at'] = date('Y-m-d h:i:s');
        if (empty($request->get('pay_at')) && $data['pay_status'] == 2
            && $data['order_status'] == 1)
            $data['pay_at'] = date('Y-m-d h:i:s');
        $msg = ['message' => '操作失败'];
        if (ModelsOrderModel::where('id', $id)->update($data))
            $msg = ['message' => '操作成功'];
        return redirect('/manage/orderList')->with($msg);
    }

    /**
     * 订单删除
     */
    public function orderDel($id = 0)
    {
        if($id <=0)
            return redirect('/manage/orderList')->with(['message' => '非法操作']);
        ModelsOrderGoodsModel::where('order_id', $id)->delete();//删除所有商品
        $msg = ['message' => '操作失败'];
        if (ModelsOrderModel::destroy($id))
            $msg = ['message' => '操作成功'];
        return redirect('/manage/orderList')->with($msg);
    }
}
