<?php

namespace App\Modules\Agent\Http\Controllers\View;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Finance\Model\CashoutModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\AlipayAuthModel;
use App\Modules\User\Model\AuthRecordModel;
use App\Modules\User\Model\BankAuthModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'property');
    }

    protected $ali_times = 3;
    protected $bank_times = 9;
    protected $bank_list = [
        '中国光大银行' => 'gdyh',
        '中国工商银行' => 'gsyh',
        '华夏银行' => 'hxyh',
        '中国建设银行' => 'jsyh',
        '交通银行' => 'jtyh',
        '中国民生银行' => 'msyh',
        '中国农村信用社' => 'ncxys',
        '中国农业银行' => 'nyyh',
        '平安银行' => 'payh',
        '浦发银行' => 'pfyh',
        '兴业银行' => 'xyyh',
        '中国邮政储蓄银行' => 'yzcx',
        '中国银行' => 'zgyh',
        '招商银行' => 'zsyh',
    ];

    // 资产
    public function index(Request $request)
    {
        $info = UserModel::from('users as u')
            ->select([
                'u.id',
                'ud.balance',
                'ud.balance_status',
                'sc.store_name',
                'sc.store_thumb_logo as store_logo',
                'sc.auth_status',
                'st.name as store_type_name',
            ])
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->leftJoin('cate as c', 'c.id', '=', 'sc.major_business')
            ->where('u.id', $this->store->id)
            ->first();

        $list = FinancialModel::from('financial as f')
            ->select([
                'f.*',
                'c.status',
            ])
            ->where('f.uid', $this->store->id)
            ->leftJoin('cashout as c', 'c.financial_id', '=', 'f.id');
        if ($action = $request->input('action')) {
            $list->where('f.action', $action);
        }
        $perPage = $request->input('perPage', 10);
        $list = $list->orderBy('f.created_at', 'desc')->paginate($perPage);

        $allow = ['action'];
        $merge = $request->only($allow);

        $addition = [2, 3, 7, 8, 11];
        $subtract = [1, 4, 5, 6];

        $view = [
            'info' => $info,
            'list' => $list,
            'merge' => $merge,
            'addition' => $addition,
            'subtract' => $subtract,
        ];
        $this->theme->setTitle('资产');
        return $this->theme->scope($this->prefix . '.property.index', $view)->render();
    }

    // 资产-充值@ajax
    public function recharge()
    {
        $balance = UserDetailModel::where('uid', $this->store->id)->where('balance_status', 0)->value('balance');
        $view = [
            'balance' => $balance,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.recharge', $view)->render()
        ]);
    }

    // 资产-提现@ajax
    public function withdrawals()
    {
        $uid = $this->store->id;
        $cash_rule = json_decode(ConfigModel::getConfigByAlias('cash')->rule, true);
        $withdraw_max = price_format($cash_rule['withdraw_max']);
        $withdraw_min = price_format($cash_rule['withdraw_min']);
        $balance = UserDetailModel::where('uid', $uid)->where('balance_status', 0)->value('balance');
        if ($balance < $withdraw_min) {
            return response()->json(['code' => 1100, 'msg' => "余额不足 {$withdraw_min} 元，无法提现"]);
        }
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $sum = CashoutModel::where('uid', $uid)
            ->whereIn('status', [0, 1])
            ->whereBetween('created_at', [$start, $end])
            ->sum('cash');
        $sum = price_format($sum);
        if ($sum >= $withdraw_max) {
            return response()->json(['code' => 1100, 'msg' => "当日提现最大金额已达到 {$withdraw_max} 元"]);
        }
        if (($balance + $sum) > $withdraw_max) {
            $max_cash = $withdraw_max - $sum;
        } else {
            $max_cash = $balance;
        }
        $max_cash = price_format($max_cash);
        $bank_list = BankAuthModel::where('uid', $uid)->where('status', 2)->latest()->get();
        $ali_list = AlipayAuthModel::where('uid', $uid)->where('status', 2)->latest()->get();
        $count = count($bank_list) + count($ali_list);
        $view = [
            'bank_list' => $bank_list,
            'ali_list' => $ali_list,
            'cash_rule' => $cash_rule,
            'count' => $count,
            'balance' => $balance,
            'bank' => $this->bank_list,
            'sum' => $sum,
            'max_cash' => $max_cash,
        ];
        $extra = [
            'min_cash' => $withdraw_min,
            'max_cash' => $max_cash,
        ];
        return response()->json([
            'code' => '1000',
            'extra' => $extra,
            'data' => view($this->prefix . '.property.withdrawals', $view)->render()
        ]);
    }

    // 资产-提现处理@ajax
    public function cash(Request $request)
    {
        $allow = [
            'cash',
            'account',
            'type',
            'action',
            'alternate_password',
        ];
        $data = $request->only($allow);
        $uid = $this->store->id;
        $cash_rule = json_decode(ConfigModel::getConfigByAlias('cash')->rule, true);
        $withdraw_max = price_format($cash_rule['withdraw_max']);
        $withdraw_min = price_format($cash_rule['withdraw_min']);
        $balance = UserDetailModel::where('uid', $uid)->where('balance_status', 0)->value('balance');
        if ($balance < $withdraw_min) {
            return response()->json(['code' => 1100, 'msg' => "余额不足 {$withdraw_min} 元，无法提现"]);
        }
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $sum = CashoutModel::where('uid', $uid)
            ->whereIn('status', [0, 1])
            ->whereBetween('created_at', [$start, $end])
            ->sum('cash');
        $sum = price_format($sum);
        if ($sum >= $withdraw_max) {
            return response()->json(['code' => 1100, 'msg' => "当日提现最大金额已达到 {$withdraw_max} 元"]);
        }
        if (($balance + $sum) > $withdraw_max) {
            $max_cash = $withdraw_max - $sum;
        } else {
            $max_cash = $balance;
        }
        $min_cash = $withdraw_min;
        $max_cash = price_format($max_cash);
        $cash = price_format($data['cash']);
        if ($cash < $min_cash) {
            return response()->json(['code' => 1100, 'msg' => "提现金额最低为 {$min_cash} 元"]);
        }
        if ($cash > $max_cash) {
            return response()->json(['code' => 1100, 'msg' => "提现金额最高为 {$max_cash} 元"]);
        }
        switch ($data['type']) {
            case 'ali':
                $account = AlipayAuthModel::where('id', $data['account'])
                    ->where('uid', $uid)
                    ->where('status', 2)
                    ->first();
                $cashout_type = 1;
                $cashout_account = $account->alipay_account;
                break;
            case 'bank':
                $account = BankAuthModel::where('id', $data['account'])
                    ->where('uid', $uid)
                    ->where('status', 2)
                    ->first();
                $cashout_type = 2;
                $cashout_account = $account->bank_account;
                break;
            default:
                $account = $cashout_type = $cashout_account = null;

                break;
        }
        if (! $account) {
            return response()->json(['code' => 1100, 'msg' => '请选择提现账号']);
        }
        $fees = price_format(FinancialModel::getFees($cash));
        switch ($data['action']) {
            case 'apply':
                $view = [
                    'account' => $account,
                    'cash_out_type' => $data['type'],
                    'cash' => $cash,
                    'bank' => $this->bank_list,
                    'cash_rule' => $cash_rule,
                    'fees' => $fees,
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view($this->prefix . '.property.cashConfirm', $view)->render()
                ]);
                break;
            case 'confirm':
                $alternate_password = UserModel::where('id', $uid)->value('alternate_password');
                $password_confirmation = UserModel::encryptPassword($data['alternate_password'], $this->store->salt);
                if ($alternate_password != $password_confirmation) {
                    return response()->json(['code' => 1100, 'msg' => '支付密码不正确']);
                }
                $data = [
                    'uid' => $uid,
                    'cash' => $cash,
                    'fees' => $fees,
                    'real_cash' => price_format($cash - $fees),
                    'cashout_type' => $cashout_type,
                    'cashout_account' => $cashout_account,
                ];
                $username = $this->store->name;
                $status = DB::transaction(function () use ($data, $username) {
                    $finance = [
                        'action' => 4,
                        'pay_type' => 1,
                        'pay_account' => $username,
                        'cash' => $data['cash'],
                        'uid' => $data['uid'],
                        'title' => "提现_{$data['cash']}元",
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $new = FinancialModel::create($finance);
                    $data['financial_id'] = $new->id;
                    CashoutModel::create($data);
                    UserDetailModel::where('uid', $data['uid'])->decrement('balance', $data['cash']);
                });
                $status = is_null($status) ? true : false;
                if ($status) {
                    return response()->json(['code' => 1000, 'msg' => '申请成功']);
                } else {
                    return response()->json(['code' => 1100, 'msg' => '申请失败']);
                }
                break;
            default:
                return response()->json(['code' => 1100, 'msg' => '非法操作']);
                break;
        }
    }

    // 资产-支付认证@ajax
    public function authentication()
    {
        $uid = $this->store->id;
        $bankAuth = BankAuthModel::where('uid', $uid)->count();
        $aliAuth = AlipayAuthModel::where('uid', $uid)->count();
        $view = [
            'bankAuth' => $bankAuth,
            'aliAuth' => $aliAuth,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.authentication', $view)->render()
        ]);
    }

    // 资产-绑定支付宝@ajax
    public function bindAli($id = 0)
    {
        $uid = $this->store->id;
        $times = $this->ali_times;
        if ($id > 0) {
            $info = AlipayAuthModel::where('id', $id)->where('uid', $uid)->first();
        } else {
            $count = AlipayAuthModel::where('uid', $uid)->count();
            if ($count >= $times) {
                return response()->json(['code' => 1100, 'msg' => "最多绑定 {$times} 个支付宝账号"]);
            }
            $info = (object)[];
            $info->status = -1;
        }
        $view = [
            'info' => $info,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.bindAli', $view)->render()
        ]);
    }

    // 资产-绑定支付宝相关操作@ajax
    public function aliAction(Request $request)
    {
        $uid = $this->store->id;
        $type = $request->input('type');
        switch ($type) {
            case 'auth':
                $times = $this->ali_times;
                $count = AlipayAuthModel::where('uid', $uid)->count();
                if ($count >= $times) {
                    return response()->json(['code' => 1100, 'msg' => "最多绑定 {$times} 个支付宝账号"]);
                }
                $allow = [
                    'realname',
                    'alipay_name',
                    'alipay_account',
                    'alipay_account_confirmation',
                ];
                $data = $request->only($allow);
                if ($data['alipay_account'] != $data['alipay_account_confirmation']) {
                    return response()->json(['code' => 1100, 'msg' => "两次账号不一致"]);
                }
                $user = UserModel::from('users as u')
                    ->select([
                        'u.id',
                        'u.name',
                        'ud.realname',
                    ])
                    ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
                    ->where('u.id', $uid)
                    ->first();
                $time = date('Y-m-d H:i:s');
                $auth = [
                    'uid' => $user->id,
                    'username' => $user->name,
                    'realname' => $data['realname'],
                    'alipay_name' => $data['alipay_name'],
                    'alipay_account' => $data['alipay_account'],
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                $record = [
                    'uid' => $user->id,
                    'username' => $user->name,
                    'auth_code' => 'alipay',
                ];
                $status = AlipayAuthModel::createAlipayAuth($auth, $record);
                if ($status) {
                    return response()->json(['code' => 1000, 'msg' => '申请成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '申请失败']);
                }
                break;
            case 'cash':
                $allow = [
                    'user_get_cash',
                    'id',
                ];
                $data = $request->only($allow);
                $info = AlipayAuthModel::where('id', $data['id'])->where('uid', $uid)->first();
                if ($info->pay_to_user_cash == $data['user_get_cash']) {
                    $res = AlipayAuthModel::alipayAuthPass($data['id']);
                    if ($res) {
                        return response()->json(['code' => 1000, 'msg' => '认证成功']);
                    } else {
                        return response()->json(['code' => 1004, 'msg' => '好像出了点错，请稍后再试']);
                    }
                } else {
                    $res = AlipayAuthModel::alipayAuthDeny($data['id']);
                    if ($res) {
                        return response()->json(['code' => 1000, 'msg' => '打款金额错误，认证失败']);
                    } else {
                        return response()->json(['code' => 1004, 'msg' => '好像出了点错，请稍后再试']);
                    }
                }
                break;
            default:
                return response()->json(['code' => 1100, 'msg' => '非法操作']);
                break;
        }
    }

    // 资产-绑定支付宝列表@ajax
    public function aliList()
    {
        $times = $this->ali_times;
        $list = AlipayAuthModel::where('uid', $this->store->id)->latest()->get();
        $count = count($list);
        $view = [
            'list' => $list,
            'count' => $count,
            'times' => $times,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.aliList', $view)->render()
        ]);
    }

    // 资产-删除支付宝账号@ajax
    public function aliDel($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => 1001, 'msg' => '非法操作']);
        }
        $uid = $this->store->id;
        $res = DB::transaction(function () use ($id, $uid) {
            AlipayAuthModel::where('id', $id)->where('uid', $uid)->delete();
            AuthRecordModel::where('auth_id', $id)->where('uid', $uid)->delete();
        });
        if (! $res) {
            return response()->json(['code' => 1000, 'msg' => '删除成功']);
        } else {
            return response()->json(['code' => 1004, 'msg' => '删除失败']);
        }
    }

    // 资产-绑定银行卡@ajax
    public function bindBank($id = 0)
    {
        $uid = $this->store->id;
        $times = $this->bank_times;
        if ($id > 0) {
            $info = BankAuthModel::where('id', $id)->where('uid', $uid)->first();
            $area = explode(',', $info->deposit_area);
            $area = DistrictModel::select([
                    DB::raw("GROUP_CONCAT(`name` SEPARATOR '-') AS `area`"),
                ])
                ->whereIn('id', $area)
                ->first();
            $info->deposit_area = $area['area'];
        } else {
            $count = BankAuthModel::where('uid', $uid)->count();
            if ($count >= $times) {
                return response()->json(['code' => 1100, 'msg' => "最多绑定 {$times} 张银行卡"]);
            }
            $info = (object)[];
            $info->status = -1;
        }
        $province = DistrictModel::getRegionList();
        $view = [
            'info' => $info,
            'bank' => $this->bank_list,
            'province' => $province,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.bindBank', $view)->render()
        ]);
    }

    // 资产-绑定银行卡相关操作@ajax
    public function bankAction(Request $request)
    {
        $uid = $this->store->id;
        $type = $request->input('type');
        switch ($type) {
            case 'auth':
                $times = $this->bank_times;
                $count = BankAuthModel::where('uid', $uid)->count();
                if ($count >= $times) {
                    return response()->json(['code' => 1100, 'msg' => "最多绑定 {$times} 张银行卡"]);
                }
                $allow = [
                    'bank_name',
                    'realname',
                    'deposit_name',
                    'province',
                    'city',
                    'area',
                    'bank_account',
                    'bank_account_confirmation',
                ];
                $data = $request->only($allow);
                if ($data['bank_account'] != $data['bank_account_confirmation']) {
                    return response()->json(['code' => 1100, 'msg' => "两次卡号不一致"]);
                }
                $user = UserModel::from('users as u')
                    ->select([
                        'u.id',
                        'u.name',
                        'ud.realname',
                    ])
                    ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
                    ->where('u.id', $uid)
                    ->first();
                $time = date('Y-m-d H:i:s');
                $area = "{$data['province']},{$data['city']},{$data['area']}";
                $auth = [
                    'uid' => $user->id,
                    'username' => $user->name,
                    'realname' => $data['realname'],
                    'bank_name' => $data['bank_name'],
                    'bank_account' => $data['bank_account'],
                    'deposit_name' => $data['deposit_name'],
                    'deposit_area' => $area,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                $record = [
                    'uid' => $user->id,
                    'username' => $user->name,
                    'auth_code' => 'bank',
                ];
                $status = BankAuthModel::createBankAuth($auth, $record);
                if ($status) {
                    return response()->json(['code' => 1000, 'msg' => '申请成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '申请失败']);
                }
                break;
            case 'cash':
                $allow = [
                    'user_get_cash',
                    'id',
                ];
                $data = $request->only($allow);
                $info = BankAuthModel::where('id', $data['id'])->where('uid', $uid)->first();
                if ($info->pay_to_user_cash == $data['user_get_cash']) {
                    $res = BankAuthModel::bankAuthPass($data['id']);
                    if ($res) {
                        return response()->json(['code' => 1000, 'msg' => '认证成功']);
                    } else {
                        return response()->json(['code' => 1004, 'msg' => '好像出了点错，请稍后再试']);
                    }
                } else {
                    $res = BankAuthModel::bankAuthDeny($data['id']);
                    if ($res) {
                        return response()->json(['code' => 1000, 'msg' => '打款金额错误，认证失败']);
                    } else {
                        return response()->json(['code' => 1004, 'msg' => '好像出了点错，请稍后再试']);
                    }
                }
                break;
            default:
                return response()->json(['code' => 1100, 'msg' => '非法操作']);
                break;
        }
    }

    // 资产-绑定银行卡列表@ajax
    public function bankList()
    {
        $times = $this->bank_times;
        $list = BankAuthModel::where('uid', $this->store->id)->latest()->get();
        $count = count($list);
        $view = [
            'list' => $list,
            'count' => $count,
            'times' => $times,
            'bank' => $this->bank_list,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.property.bankList', $view)->render()
        ]);
    }

    // 资产-删除银行卡@ajax
    public function bankDel($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => 1001, 'msg' => '非法操作']);
        }
        $uid = $this->store->id;
        $res = DB::transaction(function () use ($id, $uid) {
            BankAuthModel::where('id', $id)->where('uid', $this->store->id)->delete();
            AuthRecordModel::where('auth_id', $id)->where('uid', $uid)->delete();
        });
        if (! $res) {
            return response()->json(['code' => 1000, 'msg' => '删除成功']);
        } else {
            return response()->json(['code' => 1004, 'msg' => '删除失败']);
        }
    }
}
