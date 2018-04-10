<?php
/**
 * 系统行为日志
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

use Auth;
use App\Modules\User\Model\ActionModel;

class ActionLogModel extends Model
{

    protected $table = 'action_log';

    public $timestamps = false;

    protected $fillable = [
        'action_id', 'user_id', 'action_ip', 'model', 'record_id', 'remark'
    ];

    /**
     * 检测当天是否首次签到
     *
     * @param  void
     * @return array
     */
    static function isFirstSign(){
        $data = [ 'code' => false, 'msg' => '' ];
        if( Auth::check() ){
            $action_info = ActionModel::where('name', 'user_sign')->first();
            if($action_info->status != 2) return [ 'code' => false, 'msg' => '该行为被禁用或删除' ];
            $user = Auth::User();
            $action = new ActionModel();
            $parse = $action->parse_action( $action_info, [ $user->id ] );
            $time  = time();
            $start = date( 'Y-m-d 00:00:00', $time );
            $end   = date( 'Y-m-d 23:59:59', $time );
            $count = ActionLogModel::where( 'user_id', $user->id )
                                   ->where( 'action_id', $action_info->id )
                                   ->whereBetween('created_at', [$start, $end])
                                   ->count();
            if( $count >= $parse['max'] ){
                $data['msg'] = '您今天已完成：' . $action_info->title;
            }else{
                $data['code'] = true;
            }
        }
        return $data;
    }

    /**
     * 获取当前用户做过的新手任务ID
     *
     * @param  void
     * @return array
     */
    static public function getCompletedNewbieTaskId(){
        $data = [];
        if( Auth::check() ){
            $user = Auth::User();
            $action_info = ActionModel::where( 'name', 'newbie_task' )->first();
            $data = ActionLogModel::where( 'action_id', $action_info->id )
                                  ->where( 'user_id', $user->id )
                                  ->lists( 'record_id' )
                                  ->toArray();
        }
        return $data;
    }

}