<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/30
 * Time: 22:45
 */

namespace App\Modules\User\Model;


use Illuminate\Database\Eloquent\Model;

class MemberAbstractModel extends Model
{
    protected $table = 'member_abstract';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id','title','content',
    ];

    public $timestamps = false;

    /**
     *
     * @return array
     */
    static function findContent(){
        $data = MemberAbstractModel::all()->toArray();
        foreach($data as $k => $v){
            $data[$k]['children'] = MemberIntroduceModel::where('m_type',$v['id'])->get()->toArray();
        }
        return $data;
    }

}