<?php

namespace App\Modules\Agent\Http\Controllers\Material;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\Agent\Model\StoreView;
use App\Modules\Agent\Model\EditSetUpModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'survey');
    }

    // 概况
    public function index()
    {
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
        $goods['all'] = ModelsContentModel::where('uid', $this->store->id)->count();
        $this->store->expire_day = calculate_days($this->store->expire_at);
        $store = $this->store;
        $view_count = StoreView::getViewData($this->store->id);
        $view = [
            'count' => $count,
            'goods' => $goods,
            'store' => $store,
            'view_count' => $view_count,
        ];
        $this->theme->setTitle('概况');
        return $this->theme->scope($this->prefix . '.survey.index', $view)->render();
    }
}
