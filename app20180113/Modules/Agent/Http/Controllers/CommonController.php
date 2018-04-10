<?php

namespace App\Modules\Agent\Http\Controllers;

use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Task\Model\ServiceModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskServiceModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use QrCode;
use Omnipay;

class CommonController extends AdminController
{
    // 下载附件
    public function attachmentDownload($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = AttachmentModel::find($id);
        if (! $info) {
            return back()->with(['err' => '附件不存在']);
        }
        return response()->download($info->url, $info->name);
    }

    // 统一支付
    public function payment(Request $request)
    {
        $allow = [
            'cash',// 金额
            'pay_type',// 支付方式
            'buy_type',// 购买类型
            'action_id',// 附加id
            'alternate_password',// 支付密码
        ];
        $data = $request->only($allow);
        $uid = $this->store->id;
        $data['uid'] = $uid;
        $order = $this->unifiedOrder($data);
        if (! $order) {
            return back()->with(['err' => '支付订单创建失败或已完成支付']);
        }
        $siteUrl = HelpsController::getConfigRule('site_url');
        $this->initTheme('agent.login');
        switch ($data['pay_type']) {
            case 'ali':
                $config = ConfigModel::getPayConfig('alipay');
                $gateway = Omnipay::gateway('alipay');
                $gateway->setPartner($config['partner']);
                $gateway->setKey($config['key']);
                $gateway->setSellerEmail($config['sellerEmail']);
                $gateway->setReturnUrl(route('agent.stateless.ali.pay.return'));
                $gateway->setNotifyUrl("{$siteUrl}/order/pay/alipay/notify");
                $purchase = [
                    'out_trade_no' => $order->code,
                    'subject' => $order->title,
                    'total_fee' => $order->cash
                ];
                try {
                    $response = $gateway->purchase($purchase)->send();
                    $initiating_at['alipay'] = date('Y-m-d H:i:s');
                    ksort($initiating_at);
                    OrderModel::where('id', $order->id)
                        ->where('uid', $uid)
                        ->update(['initiating_at' => json_encode($initiating_at)]);
                    return $response->redirect();
                } catch (\Exception $e) {
                    $view = [
                        'order' => $order,
                    ];
                    $this->theme->setTitle('支付宝扫码支付：交易已关闭');
                    return $this->theme->scope($this->module . '.payment.aliPayFail', $view)->render();
                }
                break;
            case 'wechat':
                $config = ConfigModel::getPayConfig('wechatpay');
                $gateway = Omnipay::gateway('wechat');
                $gateway->setAppId($config['appId']);
                $gateway->setMchId($config['mchId']);
                $gateway->setAppKey($config['appKey']);
                $purchase = [
                    'out_trade_no' => $order->code,
                    'notify_url' => "{$siteUrl}/order/pay/wechat/notify?out_trade_no={$order->code}",
                    'body' => $order->title,
                    'total_fee' => $order->cash,
                    'fee_type' => 'CNY'
                ];
                try {
                    $response = $gateway->purchase($purchase)->send();
                    $initiating_at['wechatpay'] = date('Y-m-d H:i:s');
                    ksort($initiating_at);
                    OrderModel::where('id', $order->id)
                        ->where('uid', $uid)
                        ->update(['initiating_at' => json_encode($initiating_at)]);
                    $order->wechatpay = $initiating_at['wechatpay'];
                    $img = QrCode::size('280')->generate($response->getRedirectUrl());
                    $view = [
                        'order' => $order,
                        'img' => $img,
                    ];
                    $this->theme->setTitle($order->title);
                    return $this->theme->scope($this->module . '.payment.weChatPay', $view)->render();
                } catch (\Exception $e) {
                    $view = [
                        'order' => $order,
                    ];
                    $this->theme->setTitle('微信扫码支付：交易已关闭');
                    return $this->theme->scope($this->module . '.payment.weChatPayFail', $view)->render();
                }
                break;
            case 'balance':
                $balance = UserDetailModel::where('uid', $uid)
                    ->where('balance_status', 0)
                    ->value('balance');
                if ($balance < $order->cash) {
                    return back()->with(['err' => '账户余额不足']);
                }
                $alternate_password = UserModel::where('id', $uid)->value('alternate_password');
                $password_confirmation = UserModel::encryptPassword($data['alternate_password'], $this->store->salt);
                if ($alternate_password != $password_confirmation) {
                    return back()->with(['err' => '支付密码不正确']);
                }
                $data['cash'] = $order->cash;
                $data['code'] = $order->code;
                $ret = $this->balanceReturn($data);
                return redirect()->route($ret['route'])->with($ret['msg']);
                break;
            default:
                return back()->with(['err' => '请选择支付方式']);
                break;
        }
    }

    // 统一下单
    public function unifiedOrder($data)
    {
        switch ($data['buy_type']) {
            case 'recharge':
                $create = [
                    'title' => "充值金额_{$data['cash']}元",
                    'cash' => $data['cash'],
                    'buy_type' => $data['buy_type'],
                ];
                $order = OrderModel::createOne($create, $data['uid']);
                break;
            case 'task_merge':
                $info = TaskModel::where('id', $data['action_id'])
                    ->where('uid', $data['uid'])
                    ->where('verified_status', 3)
                    ->first();
                if ($info->bounty_status == 2 || $info->server_status == 2) {
                    return null;
                }
                $ids = TaskServiceModel::where('task_id', $info->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $info->service = price_format($service);
                $total = $info->service + $info->bounty;
                $create = [
                    'uid' => $info->uid,
                    'total' => $total,
                    'task_id' => $info->id,
                    'task_title' => cut_str($info->title, 20),
                    'bounty' => $info->bounty,
                    'ids' => $ids,
                ];
                $order = OrderModel::taskMergeOrder($create);
                break;
            case 'task_bounty':
                $info = TaskModel::where('id', $data['action_id'])
                    ->where('uid', $data['uid'])
                    ->where('verified_status', 3)
                    ->first();
                if ($info->bounty_status == 2) {
                    return null;
                }
                $total = $info->bounty;
                $create = [
                    'uid' => $info->uid,
                    'total' => $total,
                    'task_id' => $info->id,
                    'task_title' => cut_str($info->title, 20),
                    'bounty' => $info->bounty,
                ];
                $order = OrderModel::taskBountyOrder($create);
                break;
            case 'task_server':
                $info = TaskModel::where('id', $data['action_id'])
                    ->where('uid', $data['uid'])
                    ->where('verified_status', 3)
                    ->first();
                if ($info->server_status == 2) {
                    return null;
                }
                $ids = TaskServiceModel::where('task_id', $info->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $info->service = price_format($service);
                $total = $info->service;
                $create = [
                    'uid' => $info->uid,
                    'total' => $total,
                    'task_id' => $info->id,
                    'task_title' => cut_str($info->title, 20),
                    'ids' => $ids,
                ];
                $order = OrderModel::taskServerOrder($create);
                break;
            default:
                $order = null;
                break;
        }

        return $order;
    }

    // 余额支付成功业务逻辑
    public function balanceReturn($data)
    {
        $pay_type = 1;
        switch ($data['buy_type']) {
            case 'task_merge':
            case 'task_bounty':
            case 'task_server':
                $status = DB::transaction(function () use ($pay_type, $data) {
                    UserDetailModel::where('uid', $data['uid'])->decrement('balance', $data['cash']);
                    $data = [
                        'pay_account' => $this->store->name,
                        'code' => $data['code'],
                        'pay_code' => $data['code'],
                        'money' => $data['cash'],
                    ];
                    $status = OrderModel::thirdPayTaskBounty($pay_type, $data);
                    return $status;
                });
                if ($status) {
                    $ret = [
                        'route' => "{$this->prefix}.goods.task.list",
                        'msg' => ['suc' => '支付成功'],
                    ];
                } else {
                    $ret = [
                        'route' => "{$this->prefix}.goods.task.list",
                        'msg' => ['err' => '支付失败'],
                    ];
                }
                break;
            default:
                $ret = [
                    'route' => 'agent.admin.index',
                    'msg' => ['err' => '您的订单存在异常'],
                ];
                break;
        }

        return $ret;
    }
}
