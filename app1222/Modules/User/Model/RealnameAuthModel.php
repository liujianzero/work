<?php

namespace App\Modules\User\Model;

use App\Http\Requests\Request;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;

use App\Modules\User\Model\ActionModel;

class RealnameAuthModel extends Model
{
    protected $table = 'realname_auth';
    
    protected $fillable = [
        'uid', 'username', 'card_front_side', 'card_back_dside', 'validation_img', 'status', 'auth_time','card_type','type','realname','card_number'
    ];

    
    static function getRealnameAuthStatus($uid)
    {
        $realnameInfo = RealnameAuthModel::where('uid', $uid)->first();
        if ($realnameInfo) {
            return $realnameInfo->status;
        }
        return null;
    }

    public $transactionData;

    
    public function createRealnameAuth($realnameInfo, $authRecordInfo)
    {
        $status = DB::transaction(function () use ($realnameInfo, $authRecordInfo) {
            $authRecordInfo['auth_id'] = DB::table('realname_auth')->insertGetId($realnameInfo);
            DB::table('auth_record')->insert($authRecordInfo);
        });
        return is_null($status) ? true : $status;
    }

    
    public function removeRealnameAuth()
    {
        $status = DB::transaction(function () {
            $user = Auth::User();
            RealnameAuthModel::where('uid', $user->id)->delete();
            AuthRecordModel::where('auth_code', 'realname')->where('uid', $user->id)->delete();
        });
        return is_null($status) ? true : $status;
    }

    
    static function realnameAuthPass($id, $ip)
    {
        $status = DB::transaction(function () use ($id) {
            RealnameAuthModel::where('id', $id)->update(array('status' => 1, 'auth_time' => date('Y-m-d H:i:s')));
            AuthRecordModel::where('auth_id', $id)
                ->where('auth_code', 'realname')
                ->update(array('status' => 1, 'auth_time' => date('Y-m-d H:i:s')));
        });

        // 新手任务【身份认证】
        $user_id = RealnameAuthModel::where('id', $id)->value( 'uid' );
        if( $user_id > 0 ){
            $action = new ActionModel();
            $action->newbieTaskIE( 4, $ip, $user_id );
        }

        return is_null($status) ? true : $status;
    }

    
    static function realnameAuthDeny($id)
    {
        $status = DB::transaction(function () use ($id) {
            RealnameAuthModel::where('id', $id)->update(array('status' => 2));
            AuthRecordModel::where('auth_id', $id)
                ->where('auth_code', 'realname')
                ->update(array('status' => 2));
        });

        return is_null($status) ? true : $status;
    }

}
