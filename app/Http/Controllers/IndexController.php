<?php

namespace App\Http\Controllers;

use App\Modules\Manage\Model\ArticleCategoryModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Manage\Model\NavModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\TeamPowerModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class IndexController extends BasicController
{
    public function __construct()
    {
        parent::__construct();

        //网站关闭
        $siteConfig = ConfigModel::getConfigByType('site');
        if ($siteConfig['site_close'] == 2){
            abort('404');
        }

        //前端头部
        if (Auth::check()){
            $user = Auth::User();

            $userDetail = UserDetailModel::select('alternate_tips','avatar')->where('uid', $user->id)->first();
            $this->theme->set('username', $user->name);
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

        }
        $powerData = TeamPowerModel::where(['type' => 1,'is_show' => 1])->orderBy('sort_id')->get();
        $this->theme->set('menuTitle',$powerData);

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
            $articleCate = ArticleCategoryModel::where('pid',$parentCate->id)->orderBy('display_order','ASC')->limit(8)->get()->toArray();
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
    }
}
