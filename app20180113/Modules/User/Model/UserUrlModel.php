<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/9
 * Time: 15:22
 */

namespace App\Modules\User\Model;

use Cache;
use Illuminate\Database\Eloquent\Model;

class UserUrlModel extends Model
{
    protected $table = 'user_url';

    public $timestamps = false;

    protected $fillable = [
        'id', 'uid', 'url', 'status', 'store_type_id', 'created_at'
    ];

    public static function getUidForUrl($url){
        $data = UserUrlModel::where(['url' => $url, 'status' => 1])->first();
        return $data;
    }

    /**
     * 获取对应店铺信息。
     */
    public function storeType()
    {
        return $this->belongsTo('App\Modules\User\Model\StoreType');
    }

    /**
     * 获取所有审核通过的URL
     */
    public static function getActiveUrl()
    {
        $key = 'store_active_url_list';
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('status', '1')->get();
            Cache::put($key, $data, 24 * 60);// 缓存一天
        }
        return $data;
    }
}