<?php

namespace App\Modules\Agent\Model;

use App\Modules\User\Model\ModelsContentModel;
use Illuminate\Database\Eloquent\Model;

class StoreGood extends Model
{
    protected $fillable = [
        'store_id',
        'models_id',
        'is_goods',
        'goods_name',
        'goods_sn',
        'goods_content',
        'goods_view',
        'goods_cover',
        'goods_price',
        'goods_type_id',
        'goods_number',
        'is_on_sale',
        'goods_cat_id',
    ];

    // 对应的分类
    public function goodsCategory()
    {
        return $this->belongsTo('App\Modules\Agent\Model\GoodsCategory', 'goods_cat_id');
    }

    // 商品入库
    public static function storage($data)
    {
        $uid = $data['uid'];
        $store_id = $data['store_id'];
        $ids = $data['ids'];
        $ids = array_unique($ids);
        $has = StoreGood::where('store_id', $store_id)
            ->whereIn('models_id', $ids)
            ->lists('models_id')
            ->toArray();
        $new = array_diff($ids, $has);
        $models = ModelsContentModel::where('uid', $uid)
            ->whereIn('id', $new)
            ->get();
        $insert = [];
        $time = date('Y-m-d H:i:s');
        foreach ($models as $model) {
            $insert[] = [
                'store_id' => $store_id,
                'models_id' => $model->id,
                'is_goods' => 0,
                'goods_name' => $model->title,
                'goods_content' => $model->content,
                'goods_cover' => get_models_cover($model),
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        if (count($insert)) {
            return self::insert($insert);
        } else {
            return false;
        }
    }
}
