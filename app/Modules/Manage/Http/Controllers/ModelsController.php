<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Http\Requests;
use App\Modules\Manage\Model\ManagerModel;
use App\Modules\Manage\Model\MenuPermissionModel;
use App\Modules\Manage\Model\ModuleTypeModel;
use App\Modules\Manage\Model\Permission;
use App\Modules\Manage\Model\PermissionRoleModel;
use App\Modules\Manage\Model\Role;
use App\Modules\Manage\Model\RoleUserModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsVrContentModel;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelsController extends ManageController
{
	//
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('模型管理');
        $this->theme->set('manageType', 'User');
    }


    /**
     * 模型列表
     *
     * @param Request $request
     * @return mixed
     */
    public function modelsList(Request $request){
    	
    	
    	$list = ModelsContentModel::select('models_content.id','models_content.title', 'models_content.cover_img', 'models_content.sort', 'models_content.create_time','models_content.status','models_content.uid','users.name as username')
    		->leftJoin('users', 'models_content.uid', '=', 'users.id');
     	if ($request->get('username')){
            $list = $list->where('users.name','like', '%'.$request->get('username').'%');
        }
    	$paginate = $request->get('paginate') ? $request->get('paginate') : 20;   	
    	$order = $request->get('order') ? $request->get('order') : 'desc';	
    	if ($request->get('by')){
    		switch ($request->get('by')){
    			case 'id':
    				$list = $list->orderBy('models_content.id', $order);
    				break;
    			case 'created_at':
    				$list = $list->orderBy('models_content.sort', $order);
    				break;
    		}
    	} else {
    		$list = $list->orderBy('models_content.sort', $order);
    	}
    	$list = $list->paginate($paginate);

    	$search = [
    			'paginate' => $paginate,
    			'order' => $order,
    			'username' => $request->get('username'),
    			'title' => $request->get('title'),
    			'start' => $request->get('start'),
    			'end' => $request->get('end')
    	];
    	
    	
    	$data = array(
    			'list' => $list,
    			'search'=>$search
    	);

    	return $this->theme->scope('manage.modelslist', $data)->render();
    }
    
    
    /**
     * 模型列表
     *
     * @param Request $request
     * @return mixed
     */
    public function indexModelsList(Request $request){
    	 
    	 
    	$list = ModelsContentModel::select('models_content.*','users.name as username')
    	->leftJoin('users', 'models_content.uid', '=', 'users.id');
    	if ($request->get('username')){
    		$list = $list->where('users.name','like', '%'.$request->get('username').'%');
    	}
    	$paginate = $request->get('paginate') ? $request->get('paginate') : 20;
    	$order = $request->get('order') ? $request->get('order') : 'desc';
    	if ($request->get('by')){
    		switch ($request->get('by')){
    			case 'id':
    				$list = $list->orderBy('models_content.id', $order);
    				break;
    			case 'created_at':
    				$list = $list->orderBy('models_content.created_at', $order);
    				break;
    		}
    	} else {
    		$list = $list->orderBy('models_content.sort_index', $order);
    	}
    	$list = $list->paginate($paginate);
    
    	$search = [
    			'paginate' => $paginate,
    			'order' => $order,
    			'username' => $request->get('username'),
    			'title' => $request->get('title'),
    			'start' => $request->get('start'),
    			'end' => $request->get('end')
    	];
    	 
    	 
    	$data = array(
    			'list' => $list,
    			'search'=>$search
    	);
    
    	return $this->theme->scope('manage.indexmodelslist', $data)->render();
    }
    
    
    /**
     * 造景模型列表
     *
     * @param Request $request
     * @return mixed
     */
    public function modelsVrList(Request $request){
    	 
    	 
    	$list = ModelsVrContentModel::select('models_vr_content.id','models_vr_content.title', 'models_vr_content.cover_img', 'models_vr_content.sort', 'models_vr_content.create_time','models_vr_content.status','models_vr_content.uid','users.name as username')
    	->leftJoin('users', 'models_vr_content.uid', '=', 'users.id');
    	if ($request->get('username')){
    		$list = $list->where('users.name','like', '%'.$request->get('username').'%');
    	}
    	$paginate = $request->get('paginate') ? $request->get('paginate') : 10;
    	$order = $request->get('order') ? $request->get('order') : 'desc';
    	$list = $list->orderBy('models_vr_content.sort', $order);
    	$list = $list->paginate($paginate);
    
    	$search = [
    			'paginate' => $paginate,
    			'order' => $order,
    			'username' => $request->get('username'),
    			'title' => $request->get('title'),
    			'start' => $request->get('start'),
    			'end' => $request->get('end')
    	];
    	 
    	 
    	$data = array(
    			'list' => $list,
    			'search'=>$search
    	);
    
    	return $this->theme->scope('manage.modelsvrlist', $data)->render();
    }

    /**
     * 造物首页推荐
     *
     * @param Request $request
     * @return mixed
     */
    
    public function changeModelsSort(Request $request){

    
    	$id = $request->get('id');
    	$sort = $request->get('sort');
    	$type = $request->get('type');
    	if($type == "index"){
    		$res = ModelsContentModel::where('id',$id)->update(array('sort_index' => $sort));
    	}else {
    		$res = ModelsContentModel::where('id',$id)->update(array('sort' => $sort));
    	}
    	if($res){
    		$data = array(
    				'code' => 1,
    				'msg' => 'success'
    		);
    	}else{
    		$data = array(
    				'code' => 0,
    				'msg' => 'failure'
    		);
    	}
    	return response()->json($data);
    }
    
    /**
     * 造景首页推荐
     *
     * @param Request $request
     * @return mixed
     */
    
    public function changeVrModelsSort(Request $request){
    
    
    	$id = $request->get('id');
    	$sort = $request->get('sort');
    	 
    	$res = ModelsVrContentModel::where('id',$id)->update(array('sort' => $sort));
    	 
    	if($res){
    		$data = array(
    				'code' => 1,
    				'msg' => 'success'
    		);
    	}else{
    		$data = array(
    				'code' => 0,
    				'msg' => 'failure'
    		);
    	}
    	return response()->json($data);
    }
    
    
    
    
    /**
     * 首页推荐
     *
     * @param Request $request
     * @return mixed
     */
    
    public function indexModels($id){
    	
     	 if (!$id) {
            return \CommonClass::showMessage('参数错误');
        }
        $id = intval($id);
        
        $status = ModelsContentModel::where('id',$id)->update(array('sort' => 1));
        if ($status)
        	return redirect()->back()->with(['massage' => '修改成功！']);
    }
    
    
    /**
     * 造景首页推荐
     *
     * @param Request $request
     * @return mixed
     */
    
    public function indexModelsVr($id){
    	 
    	if (!$id) {
    		return \CommonClass::showMessage('参数错误');
    	}
    	$id = intval($id);
    
    	$status = ModelsVrContentModel::where('id',$id)->update(array('sort' => 1));
    	if ($status)
    		return redirect()->back()->with(['massage' => '修改成功！']);
    }
    
    /**
     * 更改造景状态
     *
     * @param $uid
     * @param $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleModelsVr($id, $action)
    {
    	switch ($action){
    		case 'enable':
    			$status = 1;
    			break;
    		case 'disable':
    			$status = 0;
    			break;
    	}
    	$status = ModelsVrContentModel::where('id', $id)->update(['status' => $status]);
    	if ($status)
    		return back()->with(['message' => '操作成功']);
    }
    
    
    /**
     * 更改状态
     *
     * @param $uid
     * @param $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleModels($id, $action)
    {
    	switch ($action){
    		case 'enable':
    			$status = 1;
    			break;
    		case 'disable':
    			$status = 0;
    			break;
    	}
    	$status = ModelsContentModel::where('id', $id)->update(['status' => $status]);
    	if ($status)
    		return back()->with(['message' => '操作成功']);
    }
    
    
    /**
     * 添加等级列表页面
     *
     * @param Request $request
     * @return mixed
     */
    public function editModel(Request $request){
    	 
    	
    	$data = [
    			'name' => $request->get('name'),
    			'min' => $request->get('min'),
    			'max' => $request->get('max'),
    			'remark' => $request->get('remark'),
    			'status' => 1,
    		
    	];    
    	$data['created_at'] = date('Y-m-d H:i:s',time());

    	$result = UserLevelModel::create($data);
    

    	if(!$result)
    		return redirect('manage/userLevelList')->with(['error'=>'用户等级添加失败']);
    
    	return redirect('manage/userLevelList')->with(['message'=>'用户等级添加成功']);    }
    
    
    
    /**
     * 用户等级列表
     *
     * @param Request $request
     * @return mixed
     */
    public function userLevelList(Request $request)
    {
    	
   
    	$list = UserLevelModel::where("status",1)->paginate();;
    	
    	$data = array(
    			'list' => $list
    	);
    	
    	return $this->theme->scope('manage.userlevel', $data)->render();
    	 
    	
    }
    
    /**
     * 用户等级编辑页面
     *
     * @param Request $request
     * @return mixed
     */
    
    public function  userLevelEditPage($id){
    	
    	
    	$userLevel = UserLevelModel::where('id',$id)->first()->toArray();
    	
   
    	$view = [
    			'user'=>$userLevel
    	];
    	
    	return $this->theme->scope('manage.userLevelEditPage',$view)->render();
    	
    	
    }
    
    
    /**
     * 用户等级编辑
     *
     * @param Request $request
     * @return mixed
     */
    
    public function  userLevelEdit(Request $request){
    	 
    	 
    	$data = $request->except('_token');
    	if(empty($data['id']))
    	{
    		return redirect()->back()->with(['error'=>'参数错误']);
    	}
    
    	$result = UserLevelModel::where('id',$data['id'])->update($data);
    
    	if(!$result)
    		return redirect()->back()->with(['error'=>'修改失败']);
    
    	return redirect('manage/userLevelList')->with(['message'=>'修改成功！']);
    	 
    	 
    }
    
}
