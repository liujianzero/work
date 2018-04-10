<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Modules\Employ\Models\EmployModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Order\Model\ShopOrderModel;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\Shop\Models\GoodsServiceModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\Vipshop\Models\PackagePrivilegesModel;
use App\Modules\Vipshop\Models\ShopPackageModel;
use App\Modules\Vipshop\Models\VipshopOrderModel;
use App\Modules\User\Model\UserMemberModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Omnipay;
use Log;

class CallBackController extends Controller
{
    /**
     * 支付宝同步回调处理
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function alipayReturn(Request $request)
    {
        $gateway = Omnipay::gateway('alipay');
        $config = ConfigModel::getPayConfig('alipay');
        $gateway->setPartner($config['partner']);
        $gateway->setKey($config['key']);
        $gateway->setSellerEmail($config['sellerEmail']);
        $gateway->setReturnUrl(env('ALIPAY_RETURN_URL', url('/order/pay/alipay/return')));
        $gateway->setNotifyUrl(env('ALIPAY_NOTIFY_URL', url('/order/pay/alipay/notify')));
        $options = [
            'request_params' => $_REQUEST,
        ];
        $response = $gateway->completePurchase($options)->send();
        if ($response->isSuccessful() && $response->isTradeStatusOk()) {
            $data = array(
                'pay_account' => $request->get('buyer_email'),//支付账号
                'code' => $request->get('out_trade_no'),//订单编号
                'pay_code' => $request->get('trade_no'),//支付宝订单号
                'money' => $request->get('total_fee'),//支付金额
            );
            $type = ShopOrderModel::handleOrderCode($data['code']);
            return $this->alipayReturnHandle($type, $data);

        } else {
            //支付失败通知.
            exit('支付失败');
        }
    }

    /**
     * 支付宝异步回调
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function alipayNotify(Request $request)
    {
        $gateway = Omnipay::gateway('alipay');
        $config = ConfigModel::getPayConfig('alipay');
        $gateway->setPartner($config['partner']);
        $gateway->setKey($config['key']);
        $gateway->setSellerEmail($config['sellerEmail']);
        $gateway->setReturnUrl(env('ALIPAY_RETURN_URL', url('/order/pay/alipay/return')));
        $gateway->setNotifyUrl(env('ALIPAY_NOTIFY_URL', url('/order/pay/alipay/notify')));
        $options = [
            'request_params' => $_REQUEST,
        ];
        Log::info('alipayNotify==='.$request->get('out_trade_no'));
        $response = $gateway->completePurchase($options)->send();
        if ($response->isSuccessful() && $response->isTradeStatusOk()) {
            $data = array(
                'pay_account' => $request->get('buyer_email'),//支付账号
                'code' => $request->get('out_trade_no'),//订单编号
                'pay_code' => $request->get('trade_no'),//支付宝订单号
                'money' => $request->get('total_fee'),//支付金额
            );
            $type = ShopOrderModel::handleOrderCode($data['code']);
            return $this->alipayNotifyHandle($type, $data);
        } else {
            //支付失败通知.
            exit('支付失败');
        }
    }

    /**
     * 微信支付异步回调
     *
     * @return mixed
     */
    public function wechatNotify()
    {
        //获取微信回调参数
        $arrNotify = \CommonClass::xmlToArray($GLOBALS['HTTP_RAW_POST_DATA']);
        if ($arrNotify['result_code'] == 'SUCCESS' && $arrNotify['return_code'] == 'SUCCESS') {
            $data = array(
                'pay_account' => $arrNotify['openid'],
                'code' => $arrNotify['out_trade_no'],
                'pay_code' => $arrNotify['transaction_id'],
                'money' => $arrNotify['total_fee'] / 100,
            );
            $type = ShopOrderModel::handleOrderCode($data['code']);
            return $this->wechatNotifyHandle($type, $data);
        }
    }

    /**
     * 根据订单类型处理同步回调逻辑
     *
     * @param $type
     * @param $data
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function alipayReturnHandle($type, $data)
    {
        switch ($type){
            case 'buy member':
                $res = OrderModel::thirdPayBuyMember(2,$data);
                if($res){
                    return redirect()->route('indexPage')->with(['message' => '支付成功!']);
                }
                return redirect()->back()->with(['message' => '支付失败！']);
                break;
            case 'buy study':
                $res = OrderModel::thirdPayBuyStudy(2,$data);
                if($res){
                    $info = OrderModel::where('code', $data['code'])->first();
                    return redirect()->to('bre/study/video/'.$info->member_id)->with(['message' => '购买成功!']);
                }
                return redirect()->back()->with(['message' => '购买失败！']);
                break;
            case 'buy capacity':
                $res = OrderModel::thirdPayBuyCapacity(2,$data);
                if($res){
                    return redirect()->route('indexPage')->with(['message' => '支付成功!']);
                }
                return redirect()->back()->with(['message' => '支付失败！']);
                break;
            case 'recharge':
                $res = OrderModel::thirdPayRecharge(2, $data);
                if($res){
                    return redirect()->to('finance/cash')->with(['message' => '充值成功!']);
                }
                return redirect()->to('finance/cash')->with(['message' => '充值失败！']);
                break;
            case 'buy goods':
                $res = OrderModel::thirdPayBuyGoods(2,$data);
                if($res){
                    return OrderModel::getPayReturnPage($data);
                }
                return redirect()->to('finance/cash')->with(['message' => '支付失败！']);
                break;
//            case 'buy other':
//                break;

//            case 'pub task':
//                //给用户充值
//                $result = UserDetailModel::recharge(Auth::user()['id'], 2, $data);
//                //修改订单状态，产生财务记录，修改任务状态
//                $task_id = OrderModel::where('code', $data['code'])->first();
//                if (!$result) {
//                    echo '支付失败！';
//                    return redirect()->to('/task/bounty',['id'=>$task_id['task_id']])->withErrors(['errMsg' => '支付失败！']);
//                }
//                TaskModel::bounty($data['money'], $task_id['task_id'], Auth::user()['id'], $data['code'], 2);
//                echo '支付成功';
//                return redirect()->to('task/' . $task_id['task_id']);
//                break;
//
//            case 'cash':
//                $res = OrderModel::where('code', $data['code'])->first();
//                if (!empty($res) && $res->status == 0) {
//                    $orderModel = new OrderModel();
//                    $status = $orderModel->recharge('alipay', $data);
//                    if ($status) {
//                        echo '支付成功';
//                        return redirect()->to('finance/cash');
//                    }
//                }else{
//                    return redirect()->to('finance/cash');
//                }
//                break;
//
//            case 'pub goods':
//                $data['pay_type'] = 2;
//                $shopOrder = ShopOrderModel::where(['code' => $data['code']])->first();
//                if (!empty($shopOrder)){
//                    $status = ShopOrderModel::thirdBuyShopService($shopOrder->code, $data);
//                    if ($status){
//                        //支付成功后操作
//                        echo('支付成功');
//                        $goodsService = GoodsServiceModel::where('id',$shopOrder->object_id)->first();
//                        if($goodsService){
//                            $goods = GoodsModel::where('id',$goodsService->goods_id)->first();
//                            if($goods && $goods->status == 0){
//                                return redirect()->to('user/waitGoodsHandle/'.$goods->id);
//                            }else{
//                                return redirect()->to('user/goodsShop');
//                            }
//                        }else{
//                            return redirect()->to('user/goodsShop');
//                        }
//                    }else{
//                        return redirect()->to('user/goodsShop');
//                    }
//                }
//                break;
//            case 'employ':
//                //给用户充值
//                $result = UserDetailModel::recharge(Auth::user()['id'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                $employ = EmployModel::where('id',$order['object_id'])->first();
//                $result2 = EmployModel::employBounty($data['money'], $order['object_id'], Auth::user()['id'], $data['code']);
//                if($result2)
//                {
//                    echo('支付成功');
//                    return Redirect::route('success',['id' => $order['object_id'],'uid'=>$employ['employee_uid']]);
//                }
//                break;
//            case 'pub service':
//                //给用户充值
//                $result = UserDetailModel::recharge(Auth::user()['id'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                $result = GoodsModel::servicePay($data['money'],Auth::user()['id'],$order['object_id'],$order['id']);
//                $service = GoodsModel::where('id',$order['object_id'])->first();
//                //跳转到置顶页面
//                if(!$result)
//                    echo '支付失败！';
//
//                return redirect()->to('user/serviceList')->with(['message'=>'您的服务成功被置顶到商城,'.date('Y-m-d',strtotime($service['recommend_end'])).'到期']);
//                break;
//            case 'buy goods':
//                $data['pay_type'] = 2;
//                $res = ShopOrderModel::where(['code'=>$data['code']])->first();
//                if (!empty($res)){
//                    $status = ShopOrderModel::thirdBuyGoods($res->code, $data);
//                    if ($status) {
//                        //查询商品数据
//                        $goodsInfo = GoodsModel::where('id',$res->object_id)->first();
//                        //修改商品销量
//                        $salesNum = intval($goodsInfo->sales_num + 1);
//                        GoodsModel::where('id',$goodsInfo->id)->update(['sales_num' => $salesNum]);
//                        echo '支付成功';
//                        return redirect('shop/confirm/'.$res->id);
//                    }
//                }
//                break;
//            case 'buy service':
//
//                break;
//            case 'buy shop service':
//                break;
//            case 'vipshop':
//                $waitHandle = VipshopOrderModel::where('code', $data['code'])->first();
//                if (!empty($waitHandle)){
//                    switch ($waitHandle->status){
//                        case 0:
//                            $status = DB::transaction(function () use ($data) {
//                                $orderInfo = VipshopOrderModel::where('code', $data['code'])->first();
//                                UserDetailModel::where('uid', Auth::id())->decrement('balance', $orderInfo->cash);
//                                FinancialModel::create([
//                                    'action' => 15,
//                                    'pay_type' => 2,
//                                    'cash' => $orderInfo->cash,
//                                    'uid' => Auth::id(),
//                                    'pay_account' => $data['pay_account'],
//                                    'pay_code' => $data['pay_code']
//                                ]);
//                                VipshopOrderModel::where('code', $orderInfo->code)->update(['status' => 1]);
//                                $arrPrivilegeId = PackagePrivilegesModel::where('package_id', $orderInfo->package_id)->get(['privileges_id'])
//                                    ->map(function ($v, $k) {
//                                        return $v['privileges_id'];
//                                    });
//                                ShopPackageModel::create([
//                                    'shop_id' => $orderInfo->shop_id,
//                                    'package_id' => $orderInfo->package_id,
//                                    'privileges_package' => json_encode($arrPrivilegeId),
//                                    'uid' => Auth::id(),
//                                    'username' => Auth::User()->name,
//                                    'duration' => $orderInfo->time_period,
//                                    'price' => $orderInfo->cash,
//                                    'start_time' => date('Y-m-d H:i:s', time()),
//                                    'end_time' => date('Y-m-d H:i:s', strtotime('+' . $orderInfo->time_period . ' month')),
//                                    'status' => 0
//                                ]);
//                            });
//                            $result = is_null($status) ? true : false;
//                            break;
//                        case 1:
//                            $result = true;
//                            break;
//                    }
//                    if ($result){
//                        return redirect('vipshop/vipsucceed');
//                    }
//                    return redirect('vipshop/vipfailure');
//                }
//                break;
        }
    }

    /**
     * 根据订单类型处理支付宝异步回调逻辑
     *
     * @param $type
     * @param $data
     */
    public function alipayNotifyHandle($type, $data)
    {
    	Log::info('alipay Notify handel.');
    	Log::info('type==='.$type);
    	Log::info('code==='.$data['code']);
        switch ($type){
            case 'buy member':
                $res = OrderModel::thirdPayBuyMember(2,$data);
                if ($res) {
                    exit('支付成功');
                }
                break;
            case 'buy study':
                $res = OrderModel::thirdPayBuyStudy(2,$data);
                if ($res) {
                    exit('支付成功');
                }
                break;
            case 'buy capacity':
                $res = OrderModel::thirdPayBuyCapacity(2,$data);
                if ($res) {
                    exit('支付成功');
                }
                break;
            case 'recharge':
                $res = OrderModel::thirdPayRecharge(2, $data);
                if ($res) {
                    exit('支付成功');
                }
                break;
            case 'buy goods':
                $res = OrderModel::thirdPayBuyGoods(2, $data);
                if ($res) {
                    exit('支付成功');
                }
                break;
//            case 'cash':
//                $res = OrderModel::where('code', $data['code'])->first();
//                if (!empty($res) && $res->status == 0) {
//                    $orderModel = new OrderModel();
//                    $staus = $orderModel->recharge('alipay', $data);
//                    if ($staus) {
//                        exit('支付成功');
//                    }
//                }
//                break;
//            case 'pub task':
//                //修改订单状态，产生财务记录，修改任务状态
//                $task_id = OrderModel::where('code', $data['code'])->first();
//                //给用户充值
//                $result = UserDetailModel::recharge($task_id['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                    return redirect()->to('/task/bounty',['id'=>$task_id['task_id']])->withErrors(['errMsg' => '支付失败！']);
//                }
//                TaskModel::bounty($data['money'], $task_id['task_id'], $task_id['uid'], $data['code'], 2);
//                echo '支付成功';
//                return redirect()->to('task/' . $task_id['task_id']);
//                break;
//            case 'pub goods':
//                $data['pay_type'] = 2;
//                $shopOrder = ShopOrderModel::where(['code' => $data['code'], 'status' => 0, 'object_type' => 3])->first();
//                if (!empty($shopOrder)){
//                    $status = ShopOrderModel::thirdBuyShopService($shopOrder->code, $data);
//                    if ($status){
//                        //支付成功后操作
//                        exit('支付成功');
//                    }
//                }
//                break;
//            case 'employ':
//                //给用户充值
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                if (!$order) {
//                    echo '支付失败！';
//                }
//                $result = UserDetailModel::recharge($order['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $employ = EmployModel::where('id',$order['object_id'])->first();
//                $result2 = EmployModel::employBounty($data['money'], $order['object_id'], Auth::user()['id'], $data['code']);
//                if($result2)
//                {
//                    echo('支付成功');
//                }
//                break;
//            case 'pub service':
//                //给用户充值
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                if (!$order) {
//                    echo '支付失败！';
//                }
//                $result = UserDetailModel::recharge($order['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $result = GoodsModel::servicePay($data['money'],Auth::user()['id'],$order['object_id'],$order['id']);
//                $service = GoodsModel::where('id',$order['object_id'])->first();
//                //跳转到置顶页面
//                if(!$result)
//                    echo '支付失败！';
//
//                echo('支付成功');
//                break;
//            case 'buy goods':
//                $data['pay_type'] = 2;
//                $res = ShopOrderModel::where(['code'=>$data['code'],'status'=>0,'object_type' => 2])->first();
//                if (!empty($res)){
//                    $status = ShopOrderModel::thirdBuyGoods($res->code, $data);
//                    if ($status) {
//                        //查询商品数据
//                        $goodsInfo = GoodsModel::where('id',$res->object_id)->first();
//                        //修改商品销量
//                        $salesNum = intval($goodsInfo->sales_num + 1);
//                        GoodsModel::where('id',$goodsInfo->id)->update(['sales_num' => $salesNum]);
//                        echo '支付成功';
//                    }
//                }
//                break;
//            case 'buy service':
//                break;
//            case 'buy shop service':
//                break;
//            case 'buy member':
//
//            	$orderInfo = OrderModel::where('code', $data['code'])->first();
//				 if (!empty($orderInfo) && $orderInfo->status == 0) {
//					$memberPackeg = UserMemberModel::where('id', $orderInfo->member_id)->first();
//					$result = UserMemberModel::memberBounty($memberPackeg['price'],$memberPackeg['month_number'],$orderInfo->uid, $orderInfo->code,8);
//					if (!$result) {
//						echo '支付失败！';
//					}
//
//				 }
//            	break;
//            case 'vipshop':
//                $waitHandle = VipshopOrderModel::where(['status' => 0, 'code' => $data['code']])->first();
//                if (empty($waitHandle)){
//                    break;
//                }
//                $status = DB::transaction(function () use ($data) {
//                    $orderInfo = VipshopOrderModel::where('code', $data['code'])->first();
//                    UserDetailModel::where('uid', Auth::id())->decrement('balance', $orderInfo->cash);
//                    FinancialModel::create([
//                        'action' => 15,
//                        'pay_type' => 2,
//                        'cash' => $orderInfo->cash,
//                        'uid' => Auth::id(),
//                        'pay_account' => $data['pay_account'],
//                        'pay_code' => $data['pay_code']
//                    ]);
//                    VipshopOrderModel::where('code', $orderInfo->code)->update(['status' => 1]);
//                    $arrPrivilegeId = PackagePrivilegesModel::where('package_id', $orderInfo->package_id)->get(['privileges_id'])
//                        ->map(function ($v, $k) {
//                            return $v['privileges_id'];
//                        });
//                    ShopPackageModel::create([
//                        'shop_id' => $orderInfo->shop_id,
//                        'package_id' => $orderInfo->package_id,
//                        'privileges_package' => json_encode($arrPrivilegeId),
//                        'uid' => Auth::id(),
//                        'username' => Auth::User()->name,
//                        'duration' => $orderInfo->time_period,
//                        'price' => $orderInfo->cash,
//                        'start_time' => date('Y-m-d H:i:s', time()),
//                        'end_time' => date('Y-m-d H:i:s', strtotime('+' . $orderInfo->time_period . ' month')),
//                        'status' => 0
//                    ]);
//                });
//                if (is_null($status)){
//                    echo '支付成功';
//                }
//                break;
        }
    }

    /**
     * 根据订单类型处理微信异步回调逻辑
     *
     * @param $type
     * @param $data
     */
    public function wechatNotifyHandle($type, $data)
    {
        $content = '<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[OK]]></return_msg>
                    </xml>';

        switch ($type){
            case 'buy member':
                $status = OrderModel::thirdPayBuyMember(3,$data);
                break;
            case 'buy study':
                $status = OrderModel::thirdPayBuyStudy(3,$data);
                break;
            case 'buy capacity':
                $status = OrderModel::thirdPayBuyCapacity(3,$data);
                break;
            case 'recharge':
                $status = OrderModel::thirdPayRecharge(3, $data);
                break;
            case 'buy goods':
                $status = OrderModel::thirdPayBuyGoods(3, $data);
                break;
//            case 'buy other':
//                break;
//            case 'cash':
//                $res = OrderModel::where('code', $data['code'])->first();
//                if (!empty($res) && $res->status == 0) {
//                    $orderModel = new OrderModel();
//                    $status = $orderModel->recharge('wechat', $data);
//                }
//                break;
//            case 'pub task':
//                //修改订单状态，产生财务记录，修改任务状态
//                $task_id = OrderModel::where('code', $data['code'])->first();
//                //给用户充值
//                $result = UserDetailModel::recharge($task_id['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                    return redirect()->to('/task/bounty',['id'=>$task_id['task_id']])->withErrors(['errMsg' => '支付失败！']);
//                }
//                TaskModel::bounty($data['money'], $task_id['task_id'], $task_id['uid'], $data['code'], 2);
//                echo '支付成功';
//                return redirect()->to('task/' . $task_id['task_id']);
//                break;
//            case 'pub goods':
//                $data['pay_type'] = 3;
//                $shopOrder = ShopOrderModel::where(['code' => $data['code'], 'status' => 0, 'object_type' => 3])->first();
//                if (!empty($shopOrder)){
//                    $status = ShopOrderModel::thirdBuyShopService($shopOrder->code, $data);
//                }
//                break;
//            case 'employ':
//                //给用户充值
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                if (!$order) {
//                    echo '支付失败！';
//                }
//                $result = UserDetailModel::recharge($order['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $employ = EmployModel::where('id',$order['object_id'])->first();
//                $result2 = EmployModel::employBounty($data['money'], $order['object_id'], Auth::user()['id'], $data['code']);
//                if($result2)
//                {
//                    echo('支付成功');
//                }
//                break;
//            case 'pub service':
//                //给用户充值
//                $order = ShopOrderModel::where('code',$data['code'])->first();
//                if (!$order) {
//                    echo '支付失败！';
//                }
//                $result = UserDetailModel::recharge($order['uid'], 2, $data);
//                if (!$result) {
//                    echo '支付失败！';
//                }
//                $result = GoodsModel::servicePay($data['money'],Auth::user()['id'],$order['object_id'],$order['id']);
//                $service = GoodsModel::where('id',$order['object_id'])->first();
//                //跳转到置顶页面
//                if(!$result)
//                    echo '支付失败！';
//
//                echo('支付成功');
//                break;
//            case 'buy goods':
//                $data['pay_type'] = 3;
//                $res = ShopOrderModel::where(['code'=>$data['code'],'status'=>0,'object_type' => 2])->first();
//                if (!empty($res)){
//                    $status = ShopOrderModel::thirdBuyGoods($res->code, $data);
//                    if ($status) {
//                        //查询商品数据
//                        $goodsInfo = GoodsModel::where('id',$res->object_id)->first();
//                        //修改商品销量
//                        $salesNum = intval($goodsInfo->sales_num + 1);
//                        GoodsModel::where('id',$goodsInfo->id)->update(['sales_num' => $salesNum]);
//                        echo '支付成功';
//                    }
//                }
//                break;
//            case 'buy service':
//                break;
//            case 'buy shop service':
//                break;
//            case 'vipshop':
//                $waitHandle = VipshopOrderModel::where(['status' => 0, 'code' => $data['code']])->first();
//
//                if (empty($waitHandle)){
//                    break;
//                }
//                $status = DB::transaction(function () use ($data) {
//                    $orderInfo = VipshopOrderModel::where('code', $data['code'])->first();
//                    UserDetailModel::where('uid', Auth::id())->decrement('balance', $orderInfo->cash);
//                    FinancialModel::create([
//                        'action' => 15,
//                        'pay_type' => 3,
//                        'cash' => $orderInfo->cash,
//                        'uid' => Auth::id(),
//                        'pay_account' => $data['pay_account'],
//                        'pay_code' => $data['pay_code']
//                    ]);
//                    VipshopOrderModel::where('code', $orderInfo->code)->update(['status' => 1]);
//                    $arrPrivilegeId = PackagePrivilegesModel::where('package_id', $orderInfo->package_id)->get(['privileges_id'])
//                        ->map(function ($v, $k) {
//                            return $v['privileges_id'];
//                        });
//                    ShopPackageModel::create([
//                        'shop_id' => $orderInfo->shop_id,
//                        'package_id' => $orderInfo->package_id,
//                        'privileges_package' => json_encode($arrPrivilegeId),
//                        'uid' => Auth::id(),
//                        'username' => Auth::User()->name,
//                        'duration' => $orderInfo->time_period,
//                        'price' => $orderInfo->cash,
//                        'start_time' => date('Y-m-d H:i:s', time()),
//                        'end_time' => date('Y-m-d H:i:s', strtotime('+' . $orderInfo->time_period . ' month')),
//                        'status' => 0
//                    ]);
//                });
//                if (is_null($status)){
//                    $status = true;
//                }
//                break;
        }

        if ($status)
            //回复微信端请求成功
            return response($content)->header('Content-Type', 'text/xml');
    }


}
