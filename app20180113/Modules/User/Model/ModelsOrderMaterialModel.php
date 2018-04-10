<?php
/**
 * 订单服务（出售素材）
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Auth;

class ModelsOrderMaterialModel extends Model
{
    protected $table = 'models_order_material';

    protected $fillable = [
        'user_id', 'shop_id', 'models_id', 'downloads', 'auth'
    ];

    /**
     * 获取所有订单。
     */
    public function orders()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel', 'action_id', 'id');
    }

    /**
     * 获取对应商品。
     */
    public function goods()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsContentModel', 'models_id', 'id');
    }
}