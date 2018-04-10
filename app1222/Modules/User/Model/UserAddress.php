<?php
/**
 * 用户-地址表
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id', 'country', 'province', 'city', 'area',
        'address', 'zip_code', 'consignee', 'mobile_prefix_id',
        'mobile', 'tel_prefix_id', 'tel_area_code', 'tel',
        'is_default'
    ];

    /**
     * 获取对应-省份。
     */
    public function provinces()
    {
        return $this->belongsTo('App\Modules\User\Model\DistrictModel', 'province', 'id');
    }

    /**
     * 获取对应-城市。
     */
    public function cities()
    {
        return $this->belongsTo('App\Modules\User\Model\DistrictModel', 'city', 'id');
    }

    /**
     * 获取对应-地区。
     */
    public function areas()
    {
        return $this->belongsTo('App\Modules\User\Model\DistrictModel', 'area', 'id');
    }

    /**
     * 获取对应-固话前缀。
     */
    public function tels()
    {
        return $this->belongsTo('App\Modules\User\Model\CountryMobilePrefix', 'tel_prefix_id', 'id');
    }

    /**
     * 获取对应-手机前缀。
     */
    public function mobiles()
    {
        return $this->belongsTo('App\Modules\User\Model\CountryMobilePrefix', 'mobile_prefix_id', 'id');
    }
}