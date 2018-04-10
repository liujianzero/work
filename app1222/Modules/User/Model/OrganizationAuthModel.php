<?php
/**
 * 机构认证模型
 *
 * @author orh
 * @time   2017-08-03
 */

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;

class OrganizationAuthModel extends Model
{
    protected $table = 'organization_auth';
    
    protected $fillable = [
        'uid', 'username', 'status', 'auth_time', 'nationality_id', 'company_name', 'registration_number',
        'legal_representative', 'registration_time', 'registration_address', 'business_license'
    ];

    
    static function getOrganizationAuthStatus($uid)
    {
        $organizationInfo = OrganizationAuthModel::where('uid', $uid)->first();
        if ($organizationInfo) {
            return $organizationInfo->status;
        }
        return null;
    }

    public $transactionData;

    
    public function createOrganizationAuth($organizationInfo, $authRecordInfo)
    {
        $status = DB::transaction(function () use ($organizationInfo, $authRecordInfo) {
            $authRecordInfo['auth_id'] = DB::table('organization_auth')->insertGetId($organizationInfo);
            DB::table('auth_record')->insert($authRecordInfo);
        });
        return is_null($status) ? true : $status;
    }

    
    public function removeOrganizationAuth()
    {
        $status = DB::transaction(function () {
            $user = Auth::User();
            OrganizationAuthModel::where('uid', $user->id)->delete();
            AuthRecordModel::where('auth_code', 'organization')->where('uid', $user->id)->delete();
        });
        return is_null($status) ? true : $status;
    }

    
    static function organizationAuthPass($id)
    {
        $status = DB::transaction(function () use ($id) {
            OrganizationAuthModel::where('id', $id)->update(array('status' => 1, 'auth_time' => date('Y-m-d H:i:s')));
            AuthRecordModel::where('auth_id', $id)
                ->where('auth_code', 'organization')
                ->update(array('status' => 1, 'auth_time' => date('Y-m-d H:i:s')));
        });

        return is_null($status) ? true : $status;
    }

    
    static function organizationAuthDeny($id,$content='')
    {
        $status = DB::transaction(function () use ($id,$content) {
            OrganizationAuthModel::where('id', $id)->update(array('status' => 2));
            AuthRecordModel::where('auth_id', $id)
                ->where('auth_code', 'organization')
                ->update(array('status' => 2));
            LoseModel::create([
                'lose_id'    => $id,
                'lose_type'  => '机构认证',
                'lose_cause' => $content,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        });

        return is_null($status) ? true : $status;
    }

}
