<?php

namespace App\Modules\Agent\Model;

use Illuminate\Database\Eloquent\Model;

class RetailIndexModel extends Model
{
    protected $table = 'agent_retail_index';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id','goods_id','profit','is_sell','created_at','updated_at'
    ];

    public $timestamps = false;

    public function getRetailGoods(){
        return $this->belongsTo('App\Modules\User\Model\ModelsContentModel','goods_id','id');
    }


    static function RetailHasGoodsData( $is_sell = 1, $is_goods = 1, $transaction_mode = 1 ){
        $data = RetailIndexModel::select('agent_retail_index.*','models_content.cover_img')
            ->where(['is_goods'=> $is_goods,'transaction_mode' => $transaction_mode ])
            ->join('models_content','agent_retail_index.goods_id','=','models_content.id')
            ->where('agent_retail_index.is_sell',$is_sell)
            ->get();
        return $data;
    }


}
