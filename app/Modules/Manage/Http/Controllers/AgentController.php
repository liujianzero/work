<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Manage\Model\AgentModel;
use App\Modules\Manage\Model\ManagerModel;
use App\Http\Requests;
use App\Modules\Manage\Http\Requests\AgentRequest;
use Illuminate\Http\Request;
use Theme;


class AgentController extends ManageController
{
    public function __construct()
    {
	
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('代理管理');
        $this->theme->set('manageType', 'agent');

    }

    /**
     * 代理列表
     * @param Request $request
     * @return mixed
     */
    public function agentList(Request $request)
    {

		
    	
//     	$agent = AgentModel::orderBy('id','DESC')->paginate(10);  	
    	$list = AgentModel::select('agent.id','agent.name','agent.sort','agent.note','manager.username')
    	->leftJoin('manager','manager.id','=','agent.user_id')->paginate(10);	
        $data = array(
            'agent_list' => $list
        );
 	
        return $this->theme->scope('manage.agentlist', $data)->render();
       
    //    return $this->theme->scope('manage.articlelist', $data)->render();

    }

 	/**
     * 添加代理页面
     */
    public function addAgentPage()
    {
    	
    	$users = ManagerModel::select('manager.id','manager.username')
    	->where('manager.username','!=','admin')->paginate();
    	$data = array(
            'users'=>$users
        );
    	
        return $this->theme->scope('manage.addagent',$data)->render();
    }

    /**
     * 添加代理
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function agentCreate(Request $request)
    {
    	
    	$data = $request->except('_token');
    	$data['created_at'] = date('Y-m-d H:i:s',time());

    	$result = AgentModel::create($data);
    

    	if(!$result)
    		return redirect('manage/agentList')->with(['error'=>'代理添加失败']);
    
    	return redirect('manage/agentList')->with(['message'=>'代理添加成功']);
    }
    
    /**
     * 代理编辑页面
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function agentEditPage($id)
    {
    	  	
//    	$agent = AgentModel::where('id',$id)->first();
		
    	
    	
    	$agent = AgentModel::select('agent.id','agent.name','agent.user_id','agent.sort','agent.note','manager.username')
    						->leftJoin('manager','agent.user_id','=','manager.id')
							->where('agent.id',$id)->first()->toArray();
  
    	
    	
    	if(!$agent)
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    	
    	$users = ManagerModel::select('manager.id','manager.username')
    	->where('manager.username','!=','admin')->paginate();
    	
    	$view = [
    			'agent'=>$agent,
    			'users'=>$users
    	];
    
    	return $this->theme->scope('manage.agentEditPage',$view)->render();
    }
    
    
    /**
     * 代理编辑
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function agentEdit(Request $request)
    {
    	
    	$data = $request->except('_token');
    	if(empty($data['id']))
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    
    	$result = AgentModel::where('id',$data['id'])->update($data);
    
    	if(!$result)
    		return redirect()->back()->with(['error'=>'修改失败']);
    
    	return redirect('manage/agentList')->with(['message'=>'修改成功！']);
    }
    
}
