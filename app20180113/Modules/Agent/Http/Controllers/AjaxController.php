<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\User\Model\DistrictModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Omnipay;

class AjaxController extends AdminController
{
    /**
     * 获取地区数据
     */
    public function getRegion($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $data = DistrictModel::getRegionList($id);
        return response()->json(['code' => '1000', 'data' => $data]);
    }

    /**
     * 任务最大/最小金额
     */
    public function getTaskBountyLimit()
    {
        $data['max_bounty'] = price_format(HelpsController::getConfigRule('task_bounty_max_limit'));
        $data['min_bounty'] = price_format(HelpsController::getConfigRule('task_bounty_min_limit'));
        return response()->json(['code' => '1000', 'data' => $data]);
    }

    /**
     * 查询微信扫码支付状态
     */
    public function weChatPayStatus(Request $request)
    {
        $out_trade_no = trim($request->get('code'));
        if (!$out_trade_no) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $config = ConfigModel::getPayConfig('wechatpay');
        $gateway = Omnipay::gateway('wechat');
        $gateway->setAppId($config['appId']);
        $gateway->setMchId($config['mchId']);
        $gateway->setAppKey($config['appKey']);
        $options = [
            'out_trade_no' => $out_trade_no
        ];
        $response = $gateway->completePurchase($options)->send();
        if ($response->isSuccessful() && $response->isTradeStatusOk()) {
            $all = $response->getData();
            $data = [
                'pay_account' => $all['openid'],
                'code' => $all['out_trade_no'],
                'pay_code' => $all['transaction_id'],
                'money' => $all['cash_fee'] / 100
            ];

            return response()->json([
                'code' => '1000',
                'url' => $this->wechatPayReturn($data),
                'msg' => '支付成功',
            ]);
        } else {
            return response()->json(['code' => '1100', 'msg' => '支付失败']);
        }
    }

    // 微信支付成功业务逻辑
    public function wechatPayReturn($data)
    {
        $pay_type = 3;
        $type = OrderModel::where('code', $data['code'])->value('buy_type');
        switch ($type) {
            case 'recharge':
                $route = "{$this->prefix}.property.index";
                OrderModel::thirdPayRecharge($pay_type, $data);
                Session::flash('suc', '充值成功');
                break;
            case 'task_merge':
            case 'task_bounty':
            case 'task_server':
                $route = "{$this->prefix}.goods.task.list";
                OrderModel::thirdPayTaskBounty($pay_type, $data);
                Session::flash('suc', '支付成功');
                break;
            default:
                $route = 'agent.admin.index';
                Session::flash('err', '您的订单存在异常');
                break;
        }

        return route($route);
    }
}
