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
use App\Modules\Manage\Model\UserLevelModel;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLevelController extends ManageController
{
	//
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('manage');
        $this->theme->setTitle('用户等级管理');
        $this->theme->set('manageType', 'User');
    }


    /**
     * 添加等级列表页面
     *
     * @param Request $request
     * @return mixed
     */
    public function addUserLevelPage(){
    	
    	
    	$data = array(
    			
    	);
    	
    	return $this->theme->scope('manage.adduserlevel', $data)->render();
    }
    
    
    /**
     * 添加等级列表页面
     *
     * @param Request $request
     * @return mixed
     */
    public function addUserLevel(Request $request){
    	 
    	
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
