<?php
/**
 * 用户登陆时间及ip
 *
 * @author orh
 * @time   2017-08-01
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Cache;

class NationalityModel extends Model
{

    protected $table = 'nationality';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name_chinese', 'name_english'
    ];

    /**
     * 获取国籍列表
     *
     * @param  void
     * @return array
     */
    static function getNationalityList()
    {
        if(Cache::has('nationality_list'))
        {
            $data = Cache::get('nationality_list');
        }else{
            $data = NationalityModel::lists( 'name_chinese', 'nationality_id' )->toArray();
            Cache::put( 'nationality_list', $data, 24 * 60 );
        }
        return $data;
    }

    /**
     * 获取国籍名称
     *
     * @param  $nationality_id
     * @return string
     */
    static function getNationalityName( $nationality_id ){
        return NationalityModel::where( 'nationality_id', $nationality_id )->value('name_chinese');
    }

}