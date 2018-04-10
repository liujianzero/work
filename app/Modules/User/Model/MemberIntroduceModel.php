<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/30
 * Time: 22:50
 */

namespace App\Modules\User\Model;



use Illuminate\Database\Eloquent\Model;

class MemberIntroduceModel extends Model
{
    protected $table = 'member_introduce';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id','m_img','m_title','m_content','m_type',
    ];

    public $timestamps = false;
}