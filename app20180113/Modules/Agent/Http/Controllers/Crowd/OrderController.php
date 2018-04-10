<?php

namespace App\Modules\Agent\Http\Controllers\Crowd;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\User\Model\Express;
use App\Modules\User\Model\ModelsOrderEvaluateModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class OrderController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'order');
    }

    // 订单
    public function index(Request $request)
    {
        $allow = [
            'pay_status',
            'post_status',
            'order_status',
            'refund_status',
        ];
        $merge = $request->only($allow);

        $list = ModelsOrderModel::from('models_order as o')
            ->select([
                'o.*',
                'dp.name as province_name',
                'dc.name as city_name',
                'da.name as area_name',
            ])
            ->leftJoin('district as dp', 'dp.id', '=', 'o.province')
            ->leftJoin('district as dc', 'dc.id', '=', 'o.city')
            ->leftJoin('district as da', 'da.id', '=', 'o.area')
            ->where('o.shop_id', $this->store->id);
        $tab_active = '?';
        if ($pay_status = $request->input('pay_status')) {
            $list->where('o.pay_status', $pay_status)
                ->where('o.order_status', 1);
            $tab_active = "?pay_status=$pay_status";
        } elseif ($post_status = $request->input('post_status')) {
            $list->where('o.post_status', $post_status)
                ->where('o.order_status', 1)
                ->where('o.pay_status', 2);
            $tab_active = "?post_status=$post_status";
        } elseif ($order_status = $request->input('order_status')) {
            $list->where('o.order_status', $order_status);
            $tab_active = "?order_status=$order_status";
        } elseif ($refund_status = $request->input('refund_status')) {
            $list->where('o.refund_status', $refund_status);
            $tab_active = "?refund_status=$refund_status";
        }
        $perPage = $request->input('perPage', 10);
        $list = $list->with('goods')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        $time = strtotime('-1 day');
        $start = date('Y-m-d 00:00:00', $time);
        $end = date('Y-m-d 23:59:59', $time);
        $mysql_prefix = config('database.connections.mysql.prefix');
        $count = ModelsOrderModel::from('models_order as mo')
            ->select([
                DB::raw("count(if(`{$mysql_prefix}mo`.`post_status` = 1, true, null)) as wait"),
                DB::raw("count(if(`{$mysql_prefix}mo`.`refund_status` = 2, true, null)) as refund"),
                DB::raw("count(if(`{$mysql_prefix}mo`.`created_at` >= '{$start}' and `{$mysql_prefix}mo`.`created_at` <= '{$end}', true, null)) as yesterday"),
                DB::raw("sum(if(`{$mysql_prefix}mo`.`created_at` >= '{$start}' and `{$mysql_prefix}mo`.`created_at` <= '{$end}' and `{$mysql_prefix}mo`.`order_status` = 1 and `{$mysql_prefix}mo`.`pay_status` = 2, `{$mysql_prefix}mo`.`paid_price`, 0)) as yesterday_money"),
            ])
            ->where('mo.shop_id', $this->store->id)
            ->first();
        $count->withdrawals_money = UserDetailModel::where('balance_status', 0)->where('uid', $this->store->id)->value('balance');
        $tab = [
            [
                'name' => '全部',
                'value' => '?',
            ],
            [
                'name' => '待付款',
                'value' => '?pay_status=1',
            ],
            [
                'name' => '待发货',
                'value' => '?post_status=1',
                'active' => 'post_status@1',
            ],
            [
                'name' => '已发货',
                'value' => '?post_status=2',
            ],
            [
                'name' => '已完成',
                'value' => '?post_status=3',
            ],
            [
                'name' => '已关闭',
                'value' => '?order_status=2',
            ],
            [
                'name' => '退款中',
                'value' => '?refund_status=2',
            ],
        ];
        $view = [
            'list' => $list,
            'tab' => $tab,
            'tab_active' => $tab_active,
            'count' => $count,
            'merge' => $merge,
        ];
        $this->theme->setTitle('订单');
        return $this->theme->scope($this->prefix . '.order.index', $view)->render();
    }

    // 订单-发货页面@ajax
    public function deliveryPage($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $uid = $this->store->id;
        $has = ModelsOrderModel::where('id', $id)
            ->where('shop_id', $uid)
            ->first();
        if (! $has) {
            return response()->json(['code' => '1004', 'msg' => '获取订单信息失败']);
        }
        if ($has->order_status != 1
            || $has->post_status != 1
            || $has->pay_status != 2) {
            return response()->json(['code' => '1004', 'msg' => '当前不符合发货状态']);
        }
        $express = Express::getList();
        $view = [
            'express' => $express,
            'info' => $has
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.order.delivery', $view)->render()
        ]);
    }

    // 订单-发货处理@ajax
    public function delivery(Request $request)
    {
        $allow = [
            'express_id',
            'post_number',
            'post_number_confirm',
            'id',
        ];
        $data = $request->only($allow);
        $data['post_number'] = trim($data['post_number']);
        if ($data['id'] <= 0) {
            return response()->json(['code' => '1001','msg' => '非法操作']);
        }
        if ($data['express_id'] <= 0) {
            return response()->json(['code' => '1110','msg' => '请选择物流公司']);
        }
        if (! trim($data['post_number'])) {
            return response()->json(['code' => '1110','msg' => '请输入物流单号']);
        }
        if ($data['post_number'] != trim($data['post_number_confirm'])) {
            return response()->json(['code' => '1110','msg' => '两次物流单号不一致']);
        }
        $uid = $this->store->id;
        $info = ModelsOrderModel::where('id', $data['id'])
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1001','msg' => '参数错误']);
        }
        if ($info->order_status != 1
            || $info->post_status != 1
            || $info->pay_status != 2) {
            return response()->json(['code' => '1004', 'msg' => '当前不符合发货状态']);
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
    }

    // 订单-评价页面@ajax
    public function evaluatePage($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $uid = $this->store->id;
        $has = ModelsOrderModel::where('id', $id)
            ->where('shop_id', $uid)
            ->first();
        if (! $has) {
            return response()->json(['code' => '1004', 'msg' => '获取订单信息失败']);
        }
        if ($has->order_status != 1
            || $has->post_status != 3
            || $has->pay_status != 2) {
            return response()->json(['code' => '1004', 'msg' => '当前不符合评价状态']);
        }
        if ($has->shop_evaluate == 'Y') {
            return response()->json(['code' => '1004', 'msg' => '您已评论过此订单']);
        }
        $view = [
            'info' => $has
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.order.evaluate', $view)->render()
        ]);
    }

    // 订单-评价处理@ajax
    public function evaluate(Request $request)
    {
        $allow = [
            'shop_evaluate',
            'shop_comment',
            'id',
        ];
        $data = $request->only($allow);
        $data['shop_comment'] = e(trim($data['shop_comment']));
        if ($data['id'] <= 0) {
            return response()->json(['code' => '1001','msg' => '非法操作']);
        }
        if (! in_array($data['shop_evaluate'], [1, 2, 3])) {
            return response()->json(['code' => '1110','msg' => '请选择总体评价']);
        }
        $uid = $this->store->id;
        $info = ModelsOrderModel::where('id', $data['id'])
            ->where('shop_id', $uid)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1001','msg' => '参数错误']);
        }
        if ($info->order_status != 1
            || $info->post_status != 3
            || $info->pay_status != 2) {
            return response()->json(['code' => '1004', 'msg' => '当前不符合评价状态']);
        }
        if ($info->shop_evaluate == 'Y') {
            return response()->json(['code' => '1004', 'msg' => '您已评论过此订单']);
        }
        $result = DB::transaction(function () use ($data, $info, $uid) {
            $update = [
                'shop_evaluate' => 'Y',
            ];
            ModelsOrderModel::where('id', $info->id)->update($update);
            if ($evaluate = $info->evaluate) {
                $update = [
                    'shop_evaluate' => $data['shop_evaluate'],
                    'shop_comment' => $data['shop_comment'],
                ];
                ModelsOrderEvaluateModel::where('id', $evaluate->id)->update($update);
            } else {
                $create = [
                    'shop_evaluate' => $data['shop_evaluate'],
                    'shop_comment' => $data['shop_comment'],
                    'user_id' => $info->user_id,
                    'shop_id' => $uid,
                    'order_id' => $info->id,
                ];
                ModelsOrderEvaluateModel::create($create);
            }
        });
        $result = is_null($result) ? true : false;
        if ($result) {
            return response()->json(['code' => '1000','msg' => '评价成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '评价失败']);
        }
    }

    // 订单详情
    public function detail($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $uid = $this->store->id;
        $info = ModelsOrderModel::from('models_order as o')
            ->select([
                'o.*',
                'dp.name as province_name',
                'dc.name as city_name',
                'da.name as area_name',
                'e.express_name',
                'e.express_tel',
                'e.express_code',
                'oe.user_evaluate as u_evaluate',
                'oe.task_quality_star',
                'oe.making_speed_star',
                'oe.working_attitude_star',
                'oe.user_comment as u_comment',
                'oe.shop_evaluate as s_evaluate',
                'oe.shop_comment as s_comment',
            ])
            ->leftJoin('district as dp', 'dp.id', '=', 'o.province')
            ->leftJoin('district as dc', 'dc.id', '=', 'o.city')
            ->leftJoin('district as da', 'da.id', '=', 'o.area')
            ->leftJoin('expresses as e', 'e.id', '=', 'o.express_id')
            ->leftJoin('models_order_evaluate as oe', 'oe.order_id', '=', 'o.id')
            ->where('o.id', $id)
            ->where('o.shop_id', $uid)
            ->first();
        if (! $info) {
            return back()->with(['err' => '获取订单信息失败']);
        }
        $goods = ModelsOrderGoodsModel::from('models_order_goods as og')
            ->select([
                'og.*',
                'g.upload_cover_image',
                'g.cover_img',
            ])
            ->leftJoin('models_content as g', 'g.id', '=', 'og.goods_id')
            ->where('og.order_id', $id)
            ->get();
        $mysql_prefix = config('database.connections.mysql.prefix');
        $action = ModelsOrderModel::from('models_order as mo')
            ->select([
                DB::raw("max(if(`{$mysql_prefix}mo`.`id` < {$id}, `{$mysql_prefix}mo`.`id`, null)) as prev"),
                DB::raw("min(if(`{$mysql_prefix}mo`.`id` > {$id}, `{$mysql_prefix}mo`.`id`, null)) as next"),
            ])
            ->where('shop_id', $uid)
            ->first();
        $prev = $action->prev;
        $next = $action->next;
        if ($info->post_number && $info->express_id) {
            $post = express_query($info->post_number, $info->express_code);
        } else {
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
        }
        $view = [
            'info' => $info,
            'prev' => $prev,
            'next' => $next,
            'goods' => $goods,
            'post' => $post,
        ];
        $this->theme->setTitle("订单详情：$info->order_sn");
        return $this->theme->scope($this->prefix . '.order.detail', $view)->render();
    }
}
