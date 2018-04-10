<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Manage\Model\AgentTrainClassModel;
use App\Modules\Manage\Model\ManagerModel;
use App\Modules\Manage\Model\AgentTrainModel;
use App\Http\Requests;
use App\Modules\Manage\Model\AgentModel;
use App\Modules\Manage\Http\Requests\AgentTrainRequest;
use Illuminate\Http\Request;
use Theme;


class AgentTrainClassController extends ManageController
{
    public function __construct()
    {
	
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('班级管理');
        $this->theme->set('manageType', 'class');
  
    }

    /**
     * 班级列表
     * @param Request $request
     * @return mixed
     */
    public function classList(Request $request)
    {
    	
    	$id = $this->manager->id;

    	$list = AgentTrainClassModel::select('agent_train_class.id','agent_train_class.name','agent_train_class.sort','agent_train_class.note','agent_train_class.status')
    	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id')->where('agent_train.user_id',$id);

    	
    	$paginate = $request->get('paginate') ? $request->get('paginate') : 10;
    	$list = $list->orderBy('agent_train_class.id','asc')->paginate($paginate);

    	

        $data = array(
            'class_list' => $list,
        		'paginate' => $paginate,
        );  

        return $this->theme->scope('manage.classlist', $data)->render();
       

    }

    
 	/**
     * 添加班级页面
     */
    public function addClassPage()
    {
    	
    	$view = [];    	
        return $this->theme->scope('manage.addclass',$view)->render();
    }

    /**
     * 添加班级页面
     */
    public function addClass(Request $request)
    {

    	$id = $this->manager->id;
   	
    	$train = AgentTrainModel::where("user_id",$id)->first();
    	
    	if($train){
    		$data = [
    				'name'=>$request->get('name'),
    				'train_id' =>$train->id,
    				'status' =>0,
    				'note' => $request->get('note'),
    				'sort' => $request->get('sort'),
    				'created_at'=> date('Y-m-d H:i:s',time())
    		];
    		
    		$result = AgentTrainClassModel::create($data);

    		
    		if(!$result)
    			return redirect('manage/classList')->with(['error'=>'班级添加失败']);
    		
    		return redirect('manage/classList')->with(['message'=>'班级添加成功']);
    		
    	}else{
    		
    		return redirect('manage/classList')->with(['error'=>'班级添加失败:只有培训机构管理员才能添加班级!']);
    		
    	}
    	
    	
    	
    	
    }
    
    
    /**
     * 班级编辑页面
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function classEditPage($id)
    {
    
    	//    	$agent = AgentModel::where('id',$id)->first();
    
    	 
    	$user_id = $this->manager->id;
	
    	$list = AgentTrainClassModel::select('agent_train_class.id','agent_train_class.name','agent_train_class.sort','agent_train_class.note','agent_train_class.status')
    	->leftJoin('agent_train','agent_train.id','=','agent_train_class.train_id')
    	->where('agent_train.user_id',$user_id)->where('agent_train_class.id',$id)
    	->first()->toArray();
  
    	if(!$list)
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
 
    	$view = [
    			'model'=>$list,
    	];

    	
    	
    	return $this->theme->scope('manage.classEditPage',$view)->render();
    }
    
    
    
    /**
     * 班级编辑
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function classEdit(Request $request)
    {
    	 
    	$data = $request->except('_token');
    	if(empty($data['id']))
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    
    	$result = AgentTrainClassModel::where('id',$data['id'])->update($data);
    
    	if(!$result)
    		return redirect()->back()->with(['error'=>'修改失败']);
    
    	return redirect('manage/classList')->with(['message'=>'修改成功！']);
    }
    
    
}
