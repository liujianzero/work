<?php
/**
 * 系统行为模型
 *
 * @author orh
 * @time   2017-08-08
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Modules\User\Model\NewbieTaskModel;
use App\Modules\User\Model\TaskModel;

class ActionModel extends Model
{

    protected $table = 'action';

    public $timestamps = true;

    protected $fillable = [
        'name', 'title', 'remark', 'rule', 'log', 'type', 'status'
    ];

    /**
     * 执行新手任务
     *
     * @param  integer $newbieTaskId
     * @param  string  $ip
     * @param  integer $admin
     * @return array
     */
    public function newbieTaskIE( $newbieTaskId = 0, $ip = '127.0.0.1', $admin = 0 ){
        $data = [ 'code' => false, 'msg' => '' ];
        if( $admin > 0 ){ // 管理员操作
            $info = NewbieTaskModel::getNewbieTaskInfo( $newbieTaskId );
            $param = [
                'action' => 'newbie_task',
                'model'  => 'users',
                'record_id' => $info['id'],
                'user_id' => $admin
            ];
            $data = $this->action_log( $param, $ip, $info );
        }else{ // 用户操作
            if( Auth::check() ){
                $user = Auth::User();
                $info = NewbieTaskModel::getNewbieTaskInfo( $newbieTaskId );
                $param = [
                    'action' => 'newbie_task',
                    'model'  => 'users',
                    'record_id' => $info['id'],
                    'user_id' => $user->id
                ];
                $data = $this->action_log( $param, $ip, $info );
            }
        }

        return $data;
    }

    /**
     * 检查一个新手任务是否已经完成
     *
     * @param integer $newbieTaskId
     * @param integer $admin
     * @return array
     */
    public function checkNewbieTask($newbieTaskId = 0){
        $data = [ 'code' => false, 'msg' => '' ];
        if(Auth::check()){
            $user = Auth::User();
            $info = NewbieTaskModel::getNewbieTaskInfo( $newbieTaskId );
            $param = [
                'action' => 'newbie_task',
                'model'  => 'users',
                'record_id' => $info['id'],
                'user_id' => $user->id
            ];
            $action_info = DB::table('action')->where('name', $param['action'])->first();
            $parse = $this->parse_action( $action_info, [ $param['user_id'], $info['experience'] ] );
            $exec_count = DB::table('action_log')->where('action_id', $action_info->id)->where('user_id', $param['user_id']);
            $exec_count = $exec_count->where('record_id', $param['record_id'])->count();
            if( $exec_count >= $parse['max'] ){
                return [ 'code' => false, 'msg' => '您已完成该新手任务：' . $info['title'] ];
            }else{
                return [ 'code' => true, 'msg' => '您还未完成该新手任务：' . $info['title'] ];
            }
        }
        return $data;
    }

    /**
     * 执行日常任务（有限次）
     *
     * @param  string $ip
     * @param  string $action
     * @return array
     */
    public function dailyIE( $ip = '127.0.0.1', $action = 'user_login' ){
        $data = [ 'code' => false, 'msg' => '' ];
        if( Auth::check() ){
            $user = Auth::User();
            $param = [
                'action' => $action,
                'model'  => 'users',
                'record_id' => $user->id,
                'user_id' => $user->id
            ];
            $data = $this->action_log( $param, $ip );
        }
        return $data;
    }

    /**
     * 执行日常任务（无限次）
     *
     * @param  string $action
     * @param  string $ip
     * @param  array  $info
     * @return array
     */
    public function infiniteIE( $action = '', $ip = '127.0.0.1', $info = [] ){
        $data = [ 'code' => false, 'msg' => '' ];
        if( Auth::check() ){
            $user = Auth::User();
            $param = [
                'action' => $action,
                'model'  => 'users',
                'record_id' => $info['id'],
                'user_id' => $user->id
            ];
            $data = $this->action_log( $param, $ip, $info );
        }
        return $data;
    }

    /**
     * 执行最新任务（有限次|无限次）
     *
     * @param  integer $taskId
     * @param  string  $ip
     * @return array
     */
    public function taskIE( $taskId = 0, $ip = '127.0.0.1' ){
        $data = [ 'code' => false, 'msg' => '' ];
        if( Auth::check() ){
            $user = Auth::User();
            $info = TaskModel::getTaskInfo( $taskId );
            $param = [
                'action' => 'newbie_task',
                'model'  => 'users',
                'record_id' => $info['id'],
                'user_id' => $user->id
            ];
            $data = $this->action_log( $param, $ip, $info );
        }
        return $data;
    }

    /**
     * 记录行为日志，并执行该行为的规则
     * @param  array $param [ 'action', 'model', 'record_id', 'user_id' ]
     * @param  string $ip
     * @param  array  $info
     * @return boolean | array
     */
    public function action_log($param = [], $ip = '127.0.0.1', $info = []){

        //参数检查
        if(empty($param['action']) || empty($param['model']) || empty($param['record_id']) || empty($param['user_id']))
            return [ 'code' => false, 'msg' => '参数不能为空' ];

        //查询行为,判断是否执行
        $action_info = DB::table('action')->where('name', $param['action'])->first();
        if($action_info->status != 2){
            return [ 'code' => false, 'msg' => '该行为被禁用或删除' ];
        }
        $exec_count = DB::table('action_log')->where('action_id', $action_info->id)->where('user_id', $param['user_id']);
        if( $action_info->type == 1 ){// @每天有限次任务
            $parse = $this->parse_action( $action_info, [ $param['user_id'] ] );
            $time  = time();
            $start = date( 'Y-m-d 00:00:00', $time );
            $end   = date( 'Y-m-d 23:59:59', $time );
            $exec_count = $exec_count->whereBetween('created_at', [$start, $end])->count();
            if( $exec_count >= $parse['max'] ){
                return [ 'code' => false, 'msg' => '您今天已完成：' . $action_info->title ];
            }
            $data['remark'] = sprintf( $action_info->log, date('Y-m-d H:i:s') );
        }elseif( $action_info->type == 2 ){// @每天无限次任务
            $parse = $this->parse_action( $action_info, [ $param['user_id'] ] );
            $data['remark'] = sprintf( $action_info->log, date('Y-m-d H:i:s'), $info['title'] );
        }elseif( $action_info->type == 3 ){// @新手任务
            $parse = $this->parse_action( $action_info, [ $param['user_id'], $info['experience'] ] );
            $exec_count = $exec_count->where('record_id', $param['record_id'])->count();
            if( $exec_count >= $parse['max'] ){
                return [ 'code' => false, 'msg' => '您已完成该新手任务：' . $info['title'] ];
            }
            $data['remark'] = sprintf( $action_info->log, date('Y-m-d H:i:s'), $info['title'], $info['experience'] );
        }elseif( $action_info->type == 4 ){// @常规任务
            $parse = $this->parse_action( $action_info, [ $param['user_id'], $info['experience'] ] );
            if( isset( $parse['cycle'] ) && isset( $parse['max'] ) ){ // 一天有次数限制
                $time  = time();
                $start = date( 'Y-m-d 00:00:00', $time );
                $end   = date( 'Y-m-d 23:59:59', $time );
                $exec_count = $exec_count->whereBetween('created_at', [$start, $end])->count();
                if( $exec_count >= $parse['max'] ){
                    return [ 'code' => false, 'msg' => '已到达当天最大' . $info['title'] . '次数' ];
                }
            }
            $data['remark'] = sprintf( $action_info->log, date('Y-m-d H:i:s'), $info['title'], $info['experience'] );
        }else{
            return [ 'code' => false, 'msg' => '未知参数' ];
        }

        //插入行为日志
        $data['action_id'] = $action_info->id;
        $data['user_id']   = $param['user_id'];
        $data['action_ip'] = $ip;
        $data['model']     = $param['model'];
        $data['record_id'] = $param['record_id'];
        $data['created_at'] = $data['updated_at'] = date( 'Y-m-d H:i:s' );

        DB::table('action_log')->insert( $data );
        return $this->execute_action($parse);

    }

    /**
     * 解析行为规则
     * @param  object $info
     * @param  array  $replace
     * @return boolean | array
     */
    public function parse_action($info, $replace){
        if(empty($info)){
            return false;
        }

        $info = [
            'id'         => $info->id,
            'name'       => $info->name,
            'title'      => $info->title,
            'remark'     => $info->remark,
            'rule'       => $info->rule,
            'log'        => $info->log,
            'type'       => $info->type,
            'status'     => $info->status,
            'created_at' => $info->created_at,
            'updated_at' => $info->updated_at
        ];

        //查询行为信息
        if(!$info || $info['status'] != 2){
            return false;
        }

        //解析规则
        $rule = $info['rule'];
        if( count( $replace ) == 2 ){
            $search = [ '{$self}', '{$experience}' ];
        }else{
            $search = [ '{$self}' ];
        }
        $rule = str_replace($search, $replace, $rule);
        $return = [];
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields){
            $field = empty($fields) ? array() : explode(':', $fields);
            if(!empty($field)){
                $return[$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if(!array_key_exists('cycle', $return) || !array_key_exists('max', $return)){
            unset($return['cycle'],$return['max']);
        }

        return $return;
    }

    /**
     * 执行行为
     * @param  array   $rule
     * @return array
     */
    function execute_action($rule = []){
        if(!$rule){
            return ['code' => false, 'msg' => '参数错误'];
        }

        $table = config( 'database.connections.mysql.prefix' ) . strtolower($rule['table']);
        //执行数据库操作
        DB::update( "UPDATE `" . $table . "` SET `{$rule['field']}` = {$rule['rule']} WHERE {$rule['condition']};" );
        return ['code' => true, 'msg' => ''];
    }

}