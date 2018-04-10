<?php
/**
 * 商品订单
 */
namespace App\Modules\User\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Auth;

class ModelsOrderModel extends Model
{

    protected $table = 'models_order';

    protected $fillable = [
        'order_sn', 'user_id', 'order_status', 'post_status', 'pay_status', 'from_at',
        'consignee', 'mobile', 'province', 'city', 'area', 'address', 'user_desc',
        'total_price', 'post_number', 'post_at', 'pay_at', 'refund_status', 'refund_at',
        'paid_price', 'refund_price', 'payment_details', 'refund_details', 'transaction_mode',
        'shop_id', 'view_id', 'type', 'action_id', 'country', 'zip_code', 'tel', 'user_evaluate',
        'shop_evaluate', 'express_id'
    ];

    /**
     * 获取所有订单商品。
     */
    public function goods()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderGoodsModel', 'order_id', 'id');
    }

    /**
     * 获取订单服务（购买服务）。
     */
    public function service()
    {
        return $this->hasOne('App\Modules\User\Model\ModelsOrderServiceModel', 'order_id', 'id');
    }

    /**
     * 获取订单服务（查看付费）。
     */
    public function view()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderViewModel', 'view_id', 'id');
    }

    /**
     * 获取订单服务（出售素材）。
     */
    public function material()
    {
        return $this->belongsTo('App\Modules\User\Model\ModelsOrderMaterialModel', 'action_id', 'id');
    }

    /**
     * 获取订单评价。
     */
    public function evaluate()
    {
        return $this->hasOne('App\Modules\User\Model\ModelsOrderEvaluateModel', 'order_id', 'id');
    }

    /**
     * 获取对应店家。
     */
    public function shop()
    {
        return $this->belongsTo('App\Modules\User\Model\UserModel', 'shop_id', 'id');
    }

    /**
     * 获取对应买家。
     */
    public function user()
    {
        return $this->belongsTo('App\Modules\User\Model\UserModel', 'user_id', 'id');
    }

    /**
     * 获取快递公司。
     */
    public function express()
    {
        return $this->belongsTo('App\Modules\User\Model\Express');
    }

    /**
     * Use: 序列化支付数据
     * @param $info
     * @param $price
     * @param $time
     * @return array|mixed|string
     */
    static function serializePaymentDetails($info,$price,$time){
        if ($info->payment_details){
            $details = unserialize($info->payment_details);
            $details[] = ['price' => $price, 'time' => $time];
            $details = serialize($details);
        } else {
            $details[] = ['price' => $price, 'time' => $time];
            $details = serialize($details);
        }
        return $details;
    }

    /**
     * Use:或者商品中查看付费月付类型时间
     * @param $info
     * @param $time
     * @return string
     */
    static function getGoodsMouthTime($info,$time){
        if ($info->view->expiration_date && $time <= $info->view->expiration_date) {// 未过期
            $date = Carbon::parse($info->view->expiration_date)->addMonth()->toDateTimeString();
        } else {
            $date = Carbon::now()->addMonth()->toDateTimeString();
        }
        return $date;
    }
}