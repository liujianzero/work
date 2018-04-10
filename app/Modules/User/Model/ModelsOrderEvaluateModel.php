<?php
/**
 * 订单评价
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

use Auth;

class ModelsOrderEvaluateModel extends Model
{
    protected $table = 'models_order_evaluate';

    protected $fillable = [
        'order_id', 'user_id', 'user_evaluate', 'task_quality_star', 'making_speed_star',
        'working_attitude_star', 'user_comment', 'shop_id', 'shop_evaluate',
        'shop_comment'
    ];

    /**
     * 获取拥有此服务对应的订单。
     */
    public function order()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderModel', 'order_id', 'id');
    }
}