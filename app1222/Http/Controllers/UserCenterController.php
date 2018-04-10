<?php

namespace App\Http\Controllers;

use App\Modules\Bre\Model\UserRandModel;
use App\Modules\Manage\Model\ArticleCategoryModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Manage\Model\NavModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\ActionLogModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\ModelsOrderServiceModel;
use App\Modules\User\Model\TeamPowerModel;
use App\Modules\User\Model\TeamUserModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsCollectModel;
use App\Modules\Manage\Model\UserLevelModel;
use App\Modules\User\Model\UserFocusModel;
use App\Modules\User\Model\AuthRecordModel;
use App\Modules\User\Model\ModelsRemarkModel;
use App\Modules\User\Model\UserTypeModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Modules\User\Model\NewbieTaskModel;

class UserCenterController extends BasicController
{

    public function __construct()
    {
        parent::__construct();
        $this->user = Auth::user ();

     //网站关闭
        $siteConfig = ConfigModel::getConfigByType('site');
        if ($siteConfig['site_close'] == 2){
            abort('404');
        }

        //前端头部
        if (Auth::check()){
        	
            $user = Auth::User();

            $user_data = UserModel::select ('users.user_type', 'user_detail.nickname', 'user_detail.avatar', 'user_detail.sex', 'user_detail.balance', 'user_detail.introduce', 'users.experience', 'users.con_login_day as loginDay','users.member_expire_date' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
            $this->theme->set( 'member_expire_date',date('Y-m-d',strtotime($user_data['member_expire_date'])) );

            //获取容量
            $percentage = UserTypeModel::getCapacityPercentage($user_data['user_type']);
            $capacity = UserTypeModel::getCapacity($user_data['user_type']);
            $this->theme->set('percentage', $percentage);
            $this->theme->set('storage', $capacity);

            //获取菜单栏
            $powerData = TeamPowerModel::where(['type' => 1,'is_show' => 1])->orderBy('sort_id')->get();
            $this->theme->set('menuTitle', $powerData);
            //获取子账号个数
            $teamUserCount = TeamUserModel::where('uid',$user->id)->count();
            $this->theme->set('countUser',$teamUserCount);

            //获取订单消息数
            $shopCount = ModelsOrderServiceModel::where('shop_id', Auth::user()->id)->whereNotIn('task_status', [0, 5])->count();
            $userCount = ModelsOrderServiceModel::where('user_id', Auth::user()->id)->whereNotIn('task_status', [0, 5])->count();
            $this->theme->set('shopCount', $shopCount >= 100 ? 99 : $shopCount);
            $this->theme->set('userCount', $userCount >= 100 ? 99 : $userCount);

            $userDetail = UserDetailModel::select('alternate_tips','avatar','nickname','sex','introduce','balance')->where('uid', $user->id)->first();
            $randNum = UserRandModel::getRandNumForUid($user->id);
            $allRandNum = $randNum['userRand'] + $randNum['testRand'];


            $this->theme->set('username', $user->name);
            $this->theme->set('nickname', $userDetail['nickname']);
            $this->theme->set('experience', $user->experience);
            $this->theme->set('sex', $userDetail->sex);
            $this->theme->set('uid', $user->id);
            $this->theme->set('randNum', $allRandNum);
            $this->theme->set('balance', floatval($userDetail['balance']));
            $this->theme->set('introduce', $userDetail['introduce']);
            $this->theme->set('tips', empty($userDetail)?'':$userDetail->alternate_tips);
            $this->theme->set('avatar',empty($userDetail)?'':$userDetail->avatar);

            //头部未读消息条数
            $messageCount = MessageReceiveModel::where('js_id',$user->id)->where('status',0)->count();
            $this->theme->set('message_count',$messageCount);

            //头部我是雇主
            $myTask = TaskModel::where('uid',$user->id)->where('bounty_status',1)->count();
            $this->theme->set('my_task',$myTask);

            //头部我是威客
            $myFocusTask = WorkModel::where('uid',$user->id)->count();
            $this->theme->set('my_focus_task',$myFocusTask);
            
            
            
            //作品数量
            $modelsNum = ModelsContentModel::where('uid',$user->id)->count();
            $this->theme->set('modelsNum',$modelsNum);
            //收藏数
            $collectNum = ModelsCollectModel::where('uid',$user->id)->count();
            $this->theme->set('collectNum',$collectNum);
            //服务数
            $serviceNum = 0;
            $this->theme->set('serviceNum',$serviceNum);
            //点评作品数
            $replyNum = ModelsRemarkModel::where('uid',$user->id)->count();
            $this->theme->set('replyNum',$replyNum);
            //被点赞次数
            $zanNum = 0;
            $this->theme->set('zanNum',$zanNum);
            // 获取用户级别
            $userLevel = UserLevelModel::select ( 'name', 'min', 'max' )->where ( 'min', '<=',  $user->experience )->where ( 'max', '>=',  $user->experience )->first ()->toArray ();
            
            $this->theme->set('user_level_max',$userLevel['max']);
            $this->theme->set('user_level_min',$userLevel['min']);
            $level_width = $user->experience / $userLevel ['max'] * 100;
            $this->theme->set('level_width',$level_width);
            
            $focus_num = UserFocusModel::where ( 'uid', $user->id )->count ();
            $this->theme->set('focus_num',$focus_num);
            
            $fans_num = UserFocusModel::where ( 'focus_uid', $user->id )->count ();
            $this->theme->set('fans_num',$fans_num);
            
            $focus_data = UserFocusModel::select ( 'user_focus.*', 'ud.avatar', 'us.name as nickname' )->where ( 'user_focus.uid', $user->id )->join ( 'user_detail as ud', 'user_focus.focus_uid', '=', 'ud.uid' )->leftjoin ( 'users as us', 'us.id', '=', 'user_focus.focus_uid' )->get ()->toArray ();
            $this->theme->set('focus_data',$focus_data);
             
            
            
            $userModel = new UserModel ();
            $user_auth = $userModel->isAuth ( $user->id);
            $userAuthOne = AuthRecordModel::where ( 'uid',$user->id )->where ( 'status', 2 )->whereIn ( 'auth_code', [
            		'bank',
            		'alipay'
            ] )->get ()->toArray ();
            $userAuthTwo = AuthRecordModel::where ( 'uid',$user->id )->where ( 'status', 1 )->whereIn ( 'auth_code', [
            		'realname',
            		'enterprise',
                    'organization' /* @author orh @time 2017-08-03 @add */
            ] )->get ()->toArray ();
            $userAuth = array_merge ( $userAuthOne, $userAuthTwo );
            if (! empty ( $userAuth ) && is_array ( $userAuth )) {
            	foreach ( $userAuth as $k => $v ) {
            		$authCode [] = $v ['auth_code'];
            	}
            	if (in_array ( 'realname', $authCode )) {
            		$realName = true;
            	} else {
            		$realName = false;
            	}
            	if (in_array ( 'bank', $authCode )) {
            		$bank = true;
            	} else {
            		$bank = false;
            	}
            	if (in_array ( 'alipay', $authCode )) {
            		$alipay = true;
            	} else {
            		$alipay = false;
            	}
            	if (in_array ( 'enterprise', $authCode )) {
            		$enterprise = true;
            	} else {
            		$enterprise = false;
            	}

                if (in_array ( 'organization', $authCode )) {
                    $organization = true;
                } else {
                    $organization = false;
                }

            } else {
            	$realName = false;
            	$bank = false;
            	$alipay = false;
            	$enterprise = false;
            	$organization = false;
            }
            $authUser = array (
            		'realname' => $realName,
            		'bank' => $bank,
            		'alipay' => $alipay,
            		'enterprise' => $enterprise,
                    'organization' => $organization /* @author orh @time 2017-08-03 @add */
            );
            $this->theme->set('authUser',$authUser);
        }

        //前端头部任务类型
        if(Cache::has('task_cate')){
            $taskCate = Cache::get('task_cate');
        }else{
            $taskCate = TaskCateModel::select('*')->orderBy('pid', 'ASC')->orderBy('sort', 'ASC')->get()->toArray();
            Cache::put('task_cate',$taskCate,60*24);
        }
        $taskCateData = [];
        if (!empty($taskCate)) {
            foreach ($taskCate as $key => $value) {
                if ( 0 == $value['pid']) {
                    $taskCateData[$value['id']] = $value;
                    $taskCateData[$value['id']]['child_task_cate'] = [];
                } else {
                    $taskCateData[$value['pid']]['child_task_cate'][] = $value;
                }
            }
        }
        $taskCateData = array_values($taskCateData);
        $this->theme->set('task_cate', $taskCateData);
       
        //前端底部公共页脚配置
        $parentCate = ArticleCategoryModel::select('id')->where('cate_name','页脚配置')->first();
        if(!empty($parentCate)){
            $articleCate = ArticleCategoryModel::where('pid',$parentCate->id)->orderBy('display_order','ASC')->limit(4)->get()->toArray();
            $this->theme->set('article_cate', $articleCate);
            //头部帮助中心
            $helpCenterCate = ArticleCategoryModel::where('pid',$parentCate->id)->orderBy('display_order','ASC')->where('cate_name','帮助中心')->first();
            if(!empty($helpCenterCate)){
                $helpCenterCateId = $helpCenterCate->id;
            }else{
                $helpCenterCateId = '';
            }
            $this->theme->set('help_center', $helpCenterCateId);
        }

        //获取基本配置（IM css自适应 客服QQ）
        $basisConfig = ConfigModel::getConfigByType('basis');
        if(!empty($basisConfig)){
            $this->theme->set('basis_config',$basisConfig);
        }
        //判断是否开启IM (1=>开启)
        if(!empty($basisConfig) && $basisConfig['open_IM'] == 1){
            $ImPath = app_path('Modules' . DIRECTORY_SEPARATOR . 'Im');
            //判断是否有Im目录
            if(is_dir($ImPath)){
                $contact = 1;
                //查询联系人
                if (Auth::check()){
                    $arrFriendUid = \App\Modules\Im\Model\ImAttentionModel::where('uid', $user->id)->lists('friend_uid')->toArray();
                    $arrAttention = UserModel::select('users.id', 'users.name', 'user_detail.avatar', 'user_detail.autograph')->whereIn('users.id', $arrFriendUid)
                        ->leftJoin('user_detail', 'users.id', '=', 'user_detail.uid')->get()->toArray();
                    $this->theme->set('attention', $arrAttention);
                }
            }else{
                $contact = 2;
            }
        }else{
            $contact = 2;
        }
        $this->theme->set('is_IM_open',$contact);
       

        //查询自定义导航
        $navList = NavModel::orderBy('sort','ASC')->get()->toArray();
        if(!empty($navList) && is_array($navList)){
            $this->theme->set('nav_list',$navList);
        }
        
        
        $status = [
        'status' => [
	        0 => '暂不发布',
	        1 => '已经发布',
	        2 => '赏金托管',
	        3 => '审核通过',
	        4 => '威客交稿',
	        5 => '雇主选稿',
	        6 => '任务公示',
	        7 => '交付验收',
	        8 => '双方互评',
	        9 => '成功完成',
	        10 => '任务失败',
	        11 => '维权中'
        ]
        ];
        //用户中心
        $tasks = TaskModel::select ( 'task.*', 'us.name as nickname', 'tc.name as category_name', 'ud.avatar' )->where ( 'task.status', '>', 2 )->join ( 'user_detail as ud', 'ud.uid', '=', 'task.uid' )->leftjoin ( 'users as us', 'us.id', '=', 'task.uid' )->leftjoin ( 'cate as tc', 'tc.id', '=', 'task.cate_id' )->orderBy ( 'task.created_at', 'desc' )->limit ( 4 )->get ()->toArray ();
        $tasks = \CommonClass::intToString ( $tasks, $status );
        
        $this->theme->set('task',$tasks);

        //新手任务
        $this->theme->set('newbie_task', NewbieTaskModel::getNewbieTaskList());

        /* 当天首次签到状态判断 */
        $sign = ActionLogModel::isFirstSign();
        if( $sign['code'] ){
            $this->theme->set('first_sign', true);
        }else{
            $this->theme->set('first_sign', false);
        }
       
    }

}
