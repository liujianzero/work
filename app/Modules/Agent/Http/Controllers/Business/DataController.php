<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\StoreView;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'data');
    }

    // 数据
    public function index(Request $request)
    {
        $view_count = StoreView::getViewData($this->store->id);
        $mysql_prefix = config('database.connections.mysql.prefix');
        $days = 7;
        $raw = [];
        $date = [];
        for ($i = $days; $i > 0; $i--) {
            $time = strtotime("-{$i} day");
            $start = date('Y-m-d 00:00:00', $time);
            $end = date('Y-m-d 23:59:59', $time);
            $d = date('m-d', $time);
            $date[] = $d;
            $raw[] = DB::raw("count(if(`{$mysql_prefix}mo`.`created_at` >= '{$start}' and `{$mysql_prefix}mo`.`created_at` <= '{$end}', true, null)) as '{$d}_order'");
        }
        $time = strtotime('-1 day');
        $start = date('Y-m-d 00:00:00', $time);
        $end = date('Y-m-d 23:59:59', $time);
        $raw[] = DB::raw("count(if(`{$mysql_prefix}mo`.`post_status` = 1, true, null)) as wait");
        $raw[] = DB::raw("count(if(`{$mysql_prefix}mo`.`refund_status` = 2, true, null)) as refund");
        $raw[] = DB::raw("count(if(`{$mysql_prefix}mo`.`created_at` >= '{$start}' and `{$mysql_prefix}mo`.`created_at` <= '{$end}', true, null)) as yesterday");
        $raw[] = DB::raw("sum(if(`{$mysql_prefix}mo`.`created_at` >= '{$start}' and `{$mysql_prefix}mo`.`created_at` <= '{$end}' and `{$mysql_prefix}mo`.`order_status` = 1 and `{$mysql_prefix}mo`.`pay_status` = 2, `{$mysql_prefix}mo`.`paid_price`, 0)) as yesterday_money");
        $raw[] = 'ud.balance_status';
        $raw[] = 'ud.balance as withdrawals_money';
        $raw[] = DB::raw("count(`{$mysql_prefix}mo`.`id`) as total_order");
        $raw[] = DB::raw("sum(if(`{$mysql_prefix}mo`.`order_status` = 1 and `{$mysql_prefix}mo`.`pay_status` = 2, `{$mysql_prefix}mo`.`paid_price`, 0)) as total_money");
        $raw[] = DB::raw("group_concat(`{$mysql_prefix}mo`.`id`) as ids");
        $order_count = ModelsOrderModel::from('models_order as mo')
            ->select($raw)
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'mo.shop_id')
            ->where('mo.shop_id', $this->store->id)
            ->first();
        $order_count->withdrawals_money = UserDetailModel::where('balance_status', 0)->where('uid', $this->store->id)->value('balance');
        $goods['all'] = ModelsContentModel::where('uid', $this->store->id)->count();
        $json['view'] = json_encode(StoreView::getDaysViewData($this->store->id));
        $data = [];
        foreach ($date as $key => $value) {
            $data[] = $order_count["{$value}_order"];
        }
        $json['order'] = json_encode([$data, $date, $days]);
        $data = ModelsOrderGoodsModel::select([
                'goods_name as name',
                'goods_price as value',
            ])
            ->whereIn('order_id', explode(',', $order_count->ids))
            ->groupBy('goods_id')
            ->get()
            ->toArray();
        $items = array_pluck($data, 'name');
        $json['goods'] = json_encode([$data, $items]);
        $view = [
            'view_count' => $view_count,
            'order_count' => $order_count,
            'goods' => $goods,
            'json' => $json
        ];
        $this->theme->setTitle('数据');
        return $this->theme->scope($this->prefix . '.data.index', $view)->render();
    }
}
