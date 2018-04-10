<?php

namespace App\Http\Middleware;

use App\Modules\User\Model\TeamUserModel;
use App\Modules\User\Model\UserModel;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserExpires
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::check()){
            $user = Auth::User();
            if($user['user_type'] > 0){
                $userExpires = strtotime( $user->member_expire_date );
                $nowTime = strtotime(date('Y-m-d H:i:s',strtotime('+1 hour')));
                if( $nowTime >=  $userExpires){
                    UserModel::where('id',$user['id'])->update(['user_type' => 0]);
                    $teamData = TeamUserModel::where('uid',$user['id'])->count();
                    if($teamData > 0){
                        TeamUserModel::where('uid',$user['id'])->update(['status' => 0 ]);
                        $children = Session::get('children');
                        if(isset($children)){
                            Auth::logout();
                        }
                    }
                    return redirect($request->path());
                }
            }
        }
        return $next($request);
    }
}
