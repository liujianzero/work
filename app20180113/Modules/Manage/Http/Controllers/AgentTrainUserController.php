<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Manage\Model\AgentTrainClassModel;
use App\Modules\Manage\Model\ManagerModel;
use App\Modules\Manage\Model\AgentTrainModel;
use App\Modules\User\Model\UserModel;
use App\Modules\Manage\Model\ClassUserModel;
use App\Http\Requests;
use App\Modules\User\Model\DistrictModel;
use App\Modules\Manage\Model\AgentModel;
use App\Modules\Manage\Http\Requests\AgentTrainRequest;
use Illuminate\Http\Request;
use Theme;


class AgentTrainUserController extends ManageController
{
    public function __construct()
    {
	
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('学员管理');
        $this->theme->set('manageType', 'class');
  
    }

     /**
     * 学员列表
     *
     * @param Request $request
     * @return mixed
     */
    public function trainUserList(Request $request)
    {
    	
    	//培训机构管理员ID
    	$id = $this->manager->id;
    	
    	//培训机构所有的班级
    	$classList = AgentTrainClassModel::select('agent_train_class.id','agent_train_class.name','agent_train.user_id')
    	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id');
    	$classList = $classList->where('agent_train.user_id',$id)->paginate();

    	
    	
    	
    	
        $list = UserModel::select('users.name', 'user_detail.created_at', 'user_detail.balance', 'users.id', 'users.last_login_time', 'users.status')
            ->leftJoin('user_detail', 'users.id', '=', 'user_detail.uid')
        	->leftJoin('class_user','class_user.uid','=','users.id');
		
      
        
        if ($request->get('uid')){
        	$list = $list->where('users.id', $request->get('uid'));
        }     
        
        if ($request->get('class_id')){
            $list = $list->where('class_user.class_id', $request->get('class_id'));
        }else{
        	$classId = AgentTrainClassModel::select('agent_train_class.id')
        	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id');
        	$classId = $classId->where('agent_train.user_id',$id)->get()->toArray();;
        	
       		$list = $list->whereIn('class_user.class_id', $classId);
        	
        }
        
        
        if ($request->get('username')){
            $list = $list->where('users.name','like', '%'.$request->get('username').'%');
        }
        if ($request->get('email')){
            $list = $list->where('users.email', $request->get('email'));
        }
        if ($request->get('mobile')){
            $list = $list->where('user_detail.mobile', $request->get('mobile'));
        }
        if (intval($request->get('status'))){
            switch(intval($request->get('status'))){
                case 1:
                    $status = 0;
                    break;
                case 2:
                    $status = 2;
                    break;
                case -1;
                    $status = [0,1,2];
                    break;
            }
            if(is_array($status)){
                $list = $list->whereIn('users.status', $status);
            }else{
                $list = $list->where('users.status', $status);
            }
        }
        $order = $request->get('order') ? $request->get('order') : 'desc';
        if ($request->get('by')){
            switch ($request->get('by')){
                case 'id':
                    $list = $list->orderBy('users.id', $order);
                    break;
                case 'created_at':
                    $list = $list->orderBy('users.created_at', $order);
                    break;
            }
        } else {
            $list = $list->orderBy('users.created_at', $order);
        }
        $paginate = $request->get('paginate') ? $request->get('paginate') : 10;
        //时间筛选
        $timeType = 'users.created_at';
        if($request->get('start')){
            $start = date('Y-m-d H:i:s',strtotime($request->get('start')));
            $list = $list->where($timeType,'>',$start);

        }
        if($request->get('end')){
            $end = date('Y-m-d H:i:s',strtotime($request->get('end')));
            $list = $list->where($timeType,'<',$end);
        }
        $list = $list->paginate($paginate);

        $data = [
            'status'=>$request->get('status'),
            'class_id'=>$request->get('class_id'),
            'list' => $list,
        	'classList'=>$classList,	
            'paginate' => $paginate,
            'order' => $order,
            'by' => $request->get('by'),
            'uid' => $request->get('uid'),
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'mobile' => $request->get('mobile')
        ];
        $search = [
            'status'=>$request->get('status'),
            'class_id'=>$request->get('class_id'),
            'paginate' => $paginate,
            'order' => $order,
            'by' => $request->get('by'),
            'uid' => $request->get('uid'),
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'mobile' => $request->get('mobile'),
            'start' => $request->get('start'),
            'end' => $request->get('end')
        ];
        $data['search'] = $search;

   //     $classList = AgentTrainClassModel::where();        
        
 		return $this->theme->scope('manage.trainUserList', $data)->render();
    }
    
 	/**
     * 添加班级页面
     */
    public function addTrainUserPage()
    {
    	
    	//培训机构管理员ID
    	$id = $this->manager->id;
    	
    	//培训机构所有的班级
    	$classList = AgentTrainClassModel::select('agent_train_class.id','agent_train_class.name','agent_train.user_id')
    	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id');
    	$classList = $classList->where('agent_train.user_id',$id)->get();

    	if(!$classList){
    	
    		return redirect('manage/trainUserList')->with(['message' => '请先添加班级!']);
    	
    	}
    	
    	$province = DistrictModel::findTree(0);
        $data = [
            'province' => $province,
        	'classList'=>$classList			
        ];
        return $this->theme->scope('manage.trainUserAdd',$data)->render();
    }

    /**
     * 添加班级页面
     */
    public function addTrainUser(Request $request)
    {
	
    	$salt = \CommonClass::random(4);
    	$validationCode = \CommonClass::random(6);
        $date = date('Y-m-d H:i:s');
        $now = time();
    	$data = [
    			'name' => $request->get('name'),
    			'class_id' => $request->get('class_id'),
    			'realname' => $request->get('realname'),
    			'mobile' => $request->get('mobile'),
    			'qq' => $request->get('qq'),
    			'email' => $request->get('email'),
    			'province' => $request->get('province'),
    			'city' => $request->get('city'),
    			'area' => $request->get('area'),
    			'last_login_time' => $date,
    			'overdue_date' => date('Y-m-d H:i:s', $now + 60*60*3),
    			'validation_code' => $validationCode,
    			'password' => UserModel::encryptPassword($request->get('password'), $salt),
    			'salt' => $salt
    	];   	
    	print_r($data);
    	
    	$status = UserModel::addTrainUser($data);

    	
    	if ($status)
    		return redirect('manage/trainUserList')->with(['message' => '添加成功']);
    	return redirect('manage/trainUserList')->with(['message' => '添加失败']);
    }
    
    
    /**
     * 班级编辑页面
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function trainUserEditPage($uid)
    {
    
    	
    	//培训机构管理员ID
    	$id = $this->manager->id;
    	 
    	//培训机构所有的班级
    	$classList = AgentTrainClassModel::select('agent_train_class.id','agent_train_class.name','agent_train.user_id')
    	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id');
    	$classList = $classList->where('agent_train.user_id',$id)->paginate();;
    	//    	$agent = AgentModel::where('id',$id)->first();

     	$info = UserModel::select('users.name', 'user_detail.realname', 'user_detail.mobile', 'user_detail.qq', 'users.email', 'class_user.class_id','user_detail.province'
            , 'user_detail.city', 'user_detail.area', 'users.id')
            ->where('users.id', $uid)
            ->leftJoin('class_user', 'users.id', '=', 'class_user.uid')
            ->leftJoin('user_detail', 'users.id', '=', 'user_detail.uid')->first()->toArray();
	
        $province = DistrictModel::findTree(0);
        $data = [
            'info' => $info,
            'province' => $province,
            'city' => DistrictModel::getDistrictName($info['city']),
            'area' => DistrictModel::getDistrictName($info['area']),
        	'classList'=>$classList	
        ];
        
 		return $this->theme->scope('manage.trainUserDetail', $data)->render();
    }
    
    
    
    /**
     * 班级编辑
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editTrainUser(Request $request)
    {
    	 
    	$salt = \CommonClass::random(4);
        $data = [
            'uid' => $request->get('uid'),
            'class_id' => $request->get('class_id'),
            'realname' => $request->get('realname'),
            'mobile' => $request->get('mobile'),
            'qq' => $request->get('qq'),
            'email' => $request->get('email'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'area' => $request->get('area'),
        //    'password' => UserModel::encryptPassword($request->get('password'), $salt),
        //    'salt' => $salt
        ];
        $status = UserModel::editTrainUser($data);
        if ($status)
            return redirect('manage/trainUserList')->with(['message' => '操作成功']);
    }
    
    
}
