<?php
/**
 * 商品-快递公司
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class Express extends Model
{
    protected $fillable = [
        'express_name', 'express_code', 'express_logo', 'status',
        'express_letter', 'express_tel', 'express_number'
    ];
    /**
     * 获取所有订单。
     */
    public function orders()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderModel');
    }

}