<?php

namespace  App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AgreementModel extends Model
{
    
    protected $table = 'agreement';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id','name','content','created_at','updated_at','code_name'
    ];

    public $timestamps = false;

    /**
     * 通过标志位获取数据
     */
    public static function getInfoByKey($code = '')
    {
        $key = "agreement_info_one@$code";
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = AgreementModel::where('code_name', $code)->first();
            Cache::put($key, $data, 60 * 24);
        }
        return $data;
    }
}
