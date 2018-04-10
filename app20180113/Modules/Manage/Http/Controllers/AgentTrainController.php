<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Manage\Model\AgentTrainModel;
use App\Modules\Manage\Model\AgentModel;
use App\Modules\Manage\Model\RoleUserModel;
use App\Modules\Manage\Model\ManagerModel;
use App\Http\Requests;
use App\Modules\Manage\Http\Requests\AgentTrainRequest;
use Illuminate\Http\Request;
use Theme;


class AgentTrainController extends ManageController
{
    public function __construct()
    {
	
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('培训机构管理');
        $this->theme->set('manageType', 'agent');
  
    }

    /**
     * 培训机构列表
     * @param Request $request
     * @return mixed
     */
    public function trainList()
    {

    	$id = $this->manager->id;

    	$list = AgentTrainModel::select('agent_train.id','agent_train.name','agent_train.sort','agent_train.note','agent_train.status','agent.name as agent_name','manager.username')
    	->leftJoin('agent','agent.id','=','agent_train.agent_id')
    	->leftJoin('manager','manager.id','=','agent_train.user_id')
    	->where('agent.user_id',$id)->orderBy('agent_train.id','asc')->paginate(10);
    	
        $data = array(
            'train_list' => $list
        );       
        return $this->theme->scope('manage.trainlist', $data)->render();
    }

    
 	/**
     * 添加培训机构页面
     */
    public function addTrainPage()
    {

    	$view = [];
    	
        return $this->theme->scope('manage.addTrain',$view)->render();
    }

    
    /**
     * 添加培训机构
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trainCreate(Request $request)
    {

    	$user_id = $this->manager->id; 	
    	
    	$agent = AgentModel::where('user_id',$user_id)->first();  	
    	if(!$agent){
    		
    		return redirect('manage/trainList')->with(['error'=>'培训机构添加失败:该用户没有匹配的代理商']);
   		
    	}else{
    		
    		$user = ManagerModel::where('username',$request->get('username'))->first();
    		if($user){
    			return redirect('manage/trainList')->with(['error'=>'培训机构添加失败:用户名已经存在!']); 
    		}
    		
    		
    		$salt = \CommonClass::random(4);
    		
    		
    		$data = [
    				'username' => $request->get('username'),
    				'email' => $request->get('email'),
    				'password' => ManagerModel::encryptPassword($request->get('password'), $salt),
    				'salt' => $salt,
    				'created_at' => date('Y-m-d H:i:s', time()),
    				'updated_at' => date('Y-m-d H:i:s', time())
    		];
    		
    		$user_id =  ManagerModel::insertGetId($data);
    		
    		$user = ManagerModel::where('id',$user_id)->first();
    		
    		
    		if($user_id > 0){
    			
    			$user->attachRole(3);
				
    			$data = array(
    					'name' => $request->get('name'),
    					'sort' => $request->get('sort'),
    					'note' => $request->get('note'),
    					'user_id'=>$user_id,
    					'agent_id' => $agent->id,
    					'status' => 2,
    					'created_at' => date('Y-m-d H:i:s', time()),
    					'updated_at' => date('Y-m-d H:i:s', time())
    			);
    			$result = AgentTrainModel::create($data);
    			if(!$result)
    				return redirect('manage/trainList')->with(['error'=>'培训机构添加失败']);
    			
    			return redirect('manage/trainList')->with(['message'=>'培训机构添加成功']);
    		}else{
    			
    			return redirect('manage/trainList')->with(['error'=>'培训机构添加失败']);
    			
    		}
    		
    		
    		
    		
    	}  	
    }
    
    
    /**
     * 培训机构详情页面
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function trainDetail($id)
    {
    
    
    	 
    	$list = AgentTrainModel::select('agent_train.id','agent_train.name','agent_train.user_id','agent_train.address','agent_train.business_license','agent_train.electronic_contract','agent_train.sort','agent_train.note','agent.name as agent_name','manager.username')
    	->leftJoin('agent','agent.id','=','agent_train.agent_id')
    	->leftJoin('manager','manager.id','=','agent_train.user_id')
    	->where('agent_train.id',$id)->first()->toArray();
    	 
    	if(!$list)
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    	$view = [
    			'train'=>$list,
    	];
    
    	return $this->theme->scope('manage.trainDetail',$view)->render();
    }
    
    
    
    /**
     * 培训机构编辑页面
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function trainEditPage($id)
    {
    	  	

  	
    	$list = AgentTrainModel::select('agent_train.id','agent_train.name','agent_train.user_id','agent_train.address','agent_train.business_license','agent_train.electronic_contract','agent_train.sort','agent_train.note','agent.name as agent_name','manager.username')
    	->leftJoin('agent','agent.id','=','agent_train.agent_id')
    	->leftJoin('manager','manager.id','=','agent_train.user_id')
    	->where('agent_train.id',$id)->first()->toArray();
    	

    	
    	
    	if(!$list)
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    	$view = [
    			'train'=>$list,
    	];
    
    	return $this->theme->scope('manage.trainEditPage',$view)->render();
    }
    
    
    /**
     * 代理编辑
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trainEdit(Request $request)
    {
    	

    	$train_business_license = $request->file('train_business_license');
        $train_electronic_contract = $request->file('train_electronic_contract');

        $train = array();
        $error = array();
        
        $allowExtension = array('jpg', 'gif', 'jpeg', 'bmp', 'png');
        if ($train_business_license) {
        	
            $uploadMsg = json_decode(\FileClass::uploadFile($train_business_license, 'user', $allowExtension));
            if ($uploadMsg->code != 200) {
                $error['train_business_license'] = $uploadMsg->message;
            } else {
                $train['business_license'] = $uploadMsg->data->url;
            }
        }    	
        if ($train_electronic_contract) {
        	$uploadMsg = json_decode(\FileClass::uploadFile($train_electronic_contract, 'user', $allowExtension));
        	if ($uploadMsg->code != 200) {
        		$error['train_business_license'] = $uploadMsg->message;
        	} else {
        		$train['electronic_contract'] = $uploadMsg->data->url;
        	}
        } 
        $train['name'] = $request->get("name");
        $train['address'] = $request->get("address");
        $train['updated_at'] =  date('Y-m-d H:i:s', time());
        
        $id = $request->get("id");



        
    	$result = AgentTrainModel::where('id',$id)->update($train);

    	
    	if(!$result)
    		return redirect()->back()->with(['error'=>'修改失败']);
    
    	return redirect('manage/trainList')->with(['message'=>'修改成功！']);
    }
    
    
    
    /**
     * 培训机构审核
     *
     * @param $id
     * @param $action
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function trainCheck($id)
    {
    	
    	$id = intval($id);
 
    	//审核失败和成功 发送系统消息
    	$train = AgentTrainModel::where('id', $id)->first();
    	
    	
    	
    	return redirect()->back()->with(['message' => '操作成功！']);
    }
    
    
}
