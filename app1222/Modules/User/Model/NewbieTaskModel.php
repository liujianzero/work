<?php
/**
 * 新手任务
 */

namespace App\Modules\User\Model;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Modules\User\Model\ActionLogModel;

class NewbieTaskModel extends Model
{
    protected $table = 'newbie_task';

    public $timestamps = false;

    protected $fillable = [
        'title', 'desc', 'experience', 'url'
    ];

    /**
     * 获取新手任务列表
     *
     * @param  void
     * @return array
     */
    static function getNewbieTaskList()
    {
        $data = [];
        if(Auth::check()){
            $id = ActionLogModel::getCompletedNewbieTaskId();
            if( $id ){
                $data = NewbieTaskModel::whereNotIn( 'id', $id )->where( 'status', 1 )->get()->toArray();
            }else{
                $data = NewbieTaskModel::get()->toArray();
            }
        }
        return $data;
    }

    /**
     * 获取某条任务的信息
     *
     * @param  integer $id
     * @return array
     */
    static function getNewbieTaskInfo( $id ){
        return NewbieTaskModel::where( 'id', $id )->where( 'status', 1 )->first()->toArray();
    }
}