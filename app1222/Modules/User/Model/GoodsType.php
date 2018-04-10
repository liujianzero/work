<?php
/**
 * 商品-属性类型
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class GoodsType extends Model
{
    protected $fillable = [
        'user_id', 'name'
    ];

    /**
     * 获取所有商品。
     */
    public function goods()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsContentModel');
    }

    /**
     * 获取所有属性。
     */
    public function attributes()
    {
        return $this->hasMany('App\Modules\User\Model\Attribute');
    }
}