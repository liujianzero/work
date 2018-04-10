<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class StoreView extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'view_type',
        'client_ip',
        'url',
        'route',
        'request_id',
    ];

    // 计算pv/uv
    public static function view($request, $store_id = 0)
    {
        if ($store_id <= 0) {
            return [];
        }
        if ($request->id) {
            $id = $request->id;
        } elseif ($request->input('id')) {
            $id = $request->input('id');
        } else {
            $id = 0;
        }
        $prefix = config('database.connections.mysql.prefix');
        $ip = $request->getClientIp();
        $time = date('Y-m-d H:i:s');
        if (Auth::check()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = 0;
        }
        $url = url();
        $route = Route::currentRouteName();
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $count = self::from('store_views as sv')
            ->select([
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_pv' and `{$prefix}sv`.`route` = '{$route}' and `{$prefix}sv`.`request_id` = '{$id}', true, null)) as store_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_uv', true, null)) as store_uv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_pv' and `{$prefix}sv`.`route` = '{$route}' and `{$prefix}sv`.`request_id` = '{$id}', true, null)) as goods_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_uv', true, null)) as goods_uv"),
            ])
            ->where('client_ip', $ip)
            ->where('url', $url)
            ->where('store_id', $store_id)
            ->whereBetween('created_at', [$start, $end])
            ->first();
        $insert = [];
        if (! $count->store_pv) {
            $insert[] = [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'view_type' => 'store_pv',
                'client_ip' => $ip,
                'url' => $url,
                'route' => $route,
                'request_id' => $id,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        if (! $count->store_uv) {
            $insert[] = [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'view_type' => 'store_uv',
                'client_ip' => $ip,
                'url' => $url,
                'route' => null,
                'request_id' => 0,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        $reg = '/(.*)?goods(.*)?/i';
        if (! $count->goods_pv && preg_match($reg, $route)) {
            $insert[] = [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'view_type' => 'goods_pv',
                'client_ip' => $ip,
                'url' => $url,
                'route' => $route,
                'request_id' => $id,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        if (! $count->goods_uv && preg_match($reg, $route)) {
            $insert[] = [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'view_type' => 'goods_uv',
                'client_ip' => $ip,
                'url' => $url,
                'route' => null,
                'request_id' => 0,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        if (count($insert)) {
            self::insert($insert);
        }
        return $insert;
    }

    // 获取浏览数据
    public static function getViewData($store_id = 0)
    {
        $prefix = config('database.connections.mysql.prefix');
        $tody_time = time();
        $tody_start = date('Y-m-d 00:00:00', $tody_time);
        $tody_end = date('Y-m-d 23:59:59', $tody_time);
        $yestody_time = strtotime('-1 day');
        $yestody_start = date('Y-m-d 00:00:00', $yestody_time);
        $yestody_end = date('Y-m-d 23:59:59', $yestody_time);
        $count = self::from('store_views as sv')
            ->select([
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_pv', true, null)) as total_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_uv', true, null)) as total_uv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_pv' and `{$prefix}sv`.`created_at` >= '{$tody_start}' and `{$prefix}sv`.`created_at` <= '{$tody_end}', true, null)) as tody_store_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_uv' and `{$prefix}sv`.`created_at` >= '{$tody_start}' and `{$prefix}sv`.`created_at` <= '{$tody_end}', true, null)) as tody_store_uv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_pv' and `{$prefix}sv`.`created_at` >= '{$tody_start}' and `{$prefix}sv`.`created_at` <= '{$tody_end}', true, null)) as tody_goods_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_uv' and `{$prefix}sv`.`created_at` >= '{$tody_start}' and `{$prefix}sv`.`created_at` <= '{$tody_end}', true, null)) as tody_goods_uv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_pv' and `{$prefix}sv`.`created_at` >= '{$yestody_start}' and `{$prefix}sv`.`created_at` <= '{$yestody_end}', true, null)) as yestody_store_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_uv' and `{$prefix}sv`.`created_at` >= '{$yestody_start}' and `{$prefix}sv`.`created_at` <= '{$yestody_end}', true, null)) as yestody_store_uv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_pv' and `{$prefix}sv`.`created_at` >= '{$yestody_start}' and `{$prefix}sv`.`created_at` <= '{$yestody_end}', true, null)) as yestody_goods_pv"),
                DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_uv' and `{$prefix}sv`.`created_at` >= '{$yestody_start}' and `{$prefix}sv`.`created_at` <= '{$yestody_end}', true, null)) as yestody_goods_uv"),
            ])
            ->where('store_id', $store_id)
            ->first();

        return $count;
    }

    // 获取最近几天数据（基于echarts）
    public static function getDaysViewData($store_id = 0, $days = 7)
    {
        $prefix = config('database.connections.mysql.prefix');
        $raw = [];
        $date = [];
        for ($i = $days; $i > 0; $i--) {
            $time = strtotime("-{$i} day");
            $start = date('Y-m-d 00:00:00', $time);
            $end = date('Y-m-d 23:59:59', $time);
            $d = date('m-d', $time);
            $date[] = $d;
            $raw[] = DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_pv' and `{$prefix}sv`.`created_at` >= '{$start}' and `{$prefix}sv`.`created_at` <= '{$end}', true, null)) as '{$d}_store_pv'");
            $raw[] = DB::raw("count(if(`{$prefix}sv`.`view_type` = 'store_uv' and `{$prefix}sv`.`created_at` >= '{$start}' and `{$prefix}sv`.`created_at` <= '{$end}', true, null)) as '{$d}_store_uv'");
            $raw[] = DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_pv' and `{$prefix}sv`.`created_at` >= '{$start}' and `{$prefix}sv`.`created_at` <= '{$end}', true, null)) as '{$d}_goods_pv'");
            $raw[] = DB::raw("count(if(`{$prefix}sv`.`view_type` = 'goods_uv' and `{$prefix}sv`.`created_at` >= '{$start}' and `{$prefix}sv`.`created_at` <= '{$end}', true, null)) as '{$d}_goods_uv'");
        }
        $count = StoreView::from('store_views as sv')
            ->select($raw)
            ->where('store_id', $store_id)
            ->first();
        $data = [];
        foreach ($date as $key => $value) {
            $data['store_pv'][] = $count["{$value}_store_pv"];
            $data['store_uv'][] = $count["{$value}_store_uv"];
            $data['goods_pv'][] = $count["{$value}_goods_pv"];
            $data['goods_uv'][] = $count["{$value}_goods_uv"];
        }

        return [$data, $date, $days];
    }
}
