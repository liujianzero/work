<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Order\Model\ShopOrderModel;
use Illuminate\Http\Request;
use Omnipay;

class StatelessController extends Controller
{

    // 支付宝同步回调
    public function aliPayReturn(Request $request)
    {
        $gateway = Omnipay::gateway('alipay');
        $config = ConfigModel::getPayConfig('alipay');
        $gateway->setPartner($config['partner']);
        $gateway->setKey($config['key']);
        $gateway->setSellerEmail($config['sellerEmail']);
        $options = [
            'request_params' => array_merge($_POST, $_GET),
        ];
        $response = $gateway->completePurchase($options)->send();
        if ($response->isSuccessful() && $response->isTradeStatusOk()) {
            $data = [
                'pay_account' => $request->input('buyer_email'),
                'code' => $request->input('out_trade_no'),
                'pay_code' => $request->input('trade_no'),
                'money' => $request->input('total_fee'),
            ];
            $type = ShopOrderModel::handleOrderCode($data['code']);
            return $this->alipayReturnHandle($type, $data);
        } else {
            return redirect()->route('agent.admin.index')->with(['err' => '您的订单尚未支付成功']);
        }
    }

    // 支付宝支付成功业务逻辑
    public function alipayReturnHandle($type, $data)
    {
        try {
            $common = new CommonController();
            $prefix = $common->prefix;
        } catch (\Exception $e) {
            $prefix = null;
        }
        $route = 'agent.admin.index';
        $pay_type = 2;
        switch ($type) {
            case 'recharge':
                OrderModel::thirdPayRecharge($pay_type, $data);
                if ($prefix) {
                    $route = "{$prefix}.property.index";
                }
                $msg = ['suc' => '充值成功'];
                break;
            case 'task_merge':
            case 'task_bounty':
            case 'task_server':
                OrderModel::thirdPayTaskBounty($pay_type, $data);
                if ($prefix) {
                    $route = "{$prefix}.goods.task.list";
                }
                $msg = ['suc' => '支付成功'];
                break;
            default:
                $msg = ['err' => '您的订单存在异常'];
                break;
        }

        return redirect()->route($route)->with($msg);
    }
}