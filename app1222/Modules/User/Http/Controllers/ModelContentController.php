<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController as BasicUserCenterController;
use App\Http\Controllers\IndexController;
use App\Modules\User\Model\MatchEnrollModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Http\Request;
use App\Modules\User\Model\ModelsModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsVrContentModel;
use App\Modules\User\Model\ModelsFavoriteModel;
use App\Modules\User\Model\ModelsCollectModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\ModelsRemarkModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\ModelsFolderModel;
use Auth;
use DB;
use Illuminate\Support\Facades\URL;

class ModelContentController extends IndexController {
	public function __construct() {
		parent::__construct ();
		$this->user = Auth::user ();
		parent::__construct ();
		$this->initTheme ( 'main' );
	}
	private $APPID = 'wx67940d188491da47';
//	private $APPID = 'wx90ab76d30613c45f';
	private $APPSECRET = 'f73c6bbcd4b4964ee54d2722968eb356';
//	private $APPSECRET = '4828aaafe92af4cee60eaa0c60c953ee';

	public function deleteDir($dir)
	{
		//if (is_dir($dir)) {
			if ($dp = opendir($dir)) {
				while (($file=readdir($dp)) != false) {
					if ($file!='.' && $file!='..') {
						
						unlink($dir."/".$file);
					}
				}
				closedir($dp);
				rmdir($dir);
			} else {
				exit('Not permission');
			}
		//}
	}
	/**
	 * 造物列表展示
	 *
	 * @return mixed
	 */
	public function modelsList(Request $request) {
		
		// 接收筛选条件
		$data = $request->all ();
		$this->initTheme ( 'models' );
		$this->theme->setTitle ( 'VR造物' );
		// 根据任务类型更新任务类型
		if (isset ( $data ['category'] )) {
			$category = TaskCateModel::findByPid ( [ 
					$data ['category'] 
			] );
			$pid = $data ['category'];
			if (empty ( $category )) {
				$category_data = TaskCateModel::findById ( $data ['category'] );
				$category = TaskCateModel::findByPid ( [ 
						$category_data ['pid'] 
				] );
				$pid = $category_data ['pid'];
			}
		} else {
			// 查询一级的分类,默认的是一级分类
			$category = TaskCateModel::findByPid ( [ 
					0 
			] );
			$pid = 0;
		}
		
		// 查询造物模型列表
		$query = ModelsContentModel::select ( 'models_content.*','nickname' ,'avatar','mf.count as fcount','mc.count as ccount' )->where('is_private',0);

//		$timeQuery = ModelsContentModel::select('models_content.create_time')
//		->where('models_content.create_time','?',);
//		var_dump($timeQuery);exit;
		// 类别筛选
		if (isset ( $data ['category'] ) && $data ['category'] > 0) {
			// 查询所有的底层id
			$category_ids = TaskCateModel::findCateIds ( $data ['category'] );
			$query->whereIn ( 'models_content.models_id', $category_ids );
		}
		if (isset ( $data ['searche'] )) {
			// 搜索
			$query->where ( 'models_content.title', 'like', '%' . e ( $data ['searche'] ) . '%' );
		}

		$paginate = ($this->themeName = 'black') ? 180000 : 12;
		$list1 = $query->leftjoin ( 'user_detail as ud', 'ud.uid', '=', 'models_content.uid' )
			->leftjoin('countzan as mf','mf.models_id','=','models_content.id')
			->leftjoin('countcomment as mc','mc.models_id','=','models_content.id');
		// 排序
		if (isset ( $data ['desc'] )) {
			$list1->orderBy ( $data ['desc'], 'desc' );
		}else{
			$query->orderBy ( 'sort', 'desc' );
		}
		$list=$list1->paginate($paginate);
		// $list['data'] = \CommonClass::intToString($list['data'],$status);
		$domain = \CommonClass::getDomain ();
		// //成功案例底部广告
		// $ad = AdTargetModel::getAdInfo('CASELIST_BOTTOM');
		$view = [ 
				'list' => $list,
				'merge' => $data,
				'category' => $category,
				'pid' => $pid,
				'domain' => $domain 
		];
		// 'ad'=>$ad,
		
		$this->theme->set ( 'now_menu', '/task/successCase' );
		return $this->theme->scope ( 'models.list', $view )->render ();
	}
	
	/**
	 * 造物页面
	 *
	 * @return mixed
	 */
	public function addv2() {
		$this->initTheme ( 'editor' ); // 主题初始化
		$this->theme->setTitle ( 'WebVR造物引擎' );
		$uid= $this->user['id'];
		$folder = ModelsFolderModel::select ( 'id','name','team_id' )->where ( 'uid', $uid )->get();
		$user_data = UserModel::where('id',$uid)->first();
		$query = TaskCateModel::where ( 'pid', '==', 0 );
		$cate = $query->paginate ( 10 );
		
		if ($cate != null) {
			$pid = $cate [0] ["id"];
			$category = TaskCateModel::findByPid ( [ 
					$pid 
			] );
		}
		$view = [ 
				'tid' => 1,
				'uid' => $uid,
				'list' => $cate,
				"list1" => $category,
				"userType"=>$user_data['user_type'],
				'folder'=>$folder
		];
		
		// print_r($view);
		
		return $this->theme->scope ( 'models.addv2', $view )->render ();
	}
	
	/**
	 * 造景页面
	 *
	 * @return mixed
	 */
	public function addvr() {
		$this->initTheme ( 'editorvr' ); // 主题初始化
		$this->theme->setTitle ( 'WebVR全景引擎' );
		$uid= $this->user['id'];
		$folder = ModelsFolderModel::select ( 'id','name','team_id' )->where ( 'uid', $uid )->get();
		$user_data = UserModel::where('id',$uid)->first();
		$query = TaskCateModel::where ( 'pid', '==', 0 );
		$cate = $query->paginate ( 10 );
		
		if ($cate != null) {
			$pid = $cate [0] ["id"];
			$category = TaskCateModel::findByPid ( [ 
					$pid 
			] );
		}
		$view = [ 
				'tid' => 1,
				'uid' => $uid,
				'list' => $cate,
				"list1" => $category,
				"userType"=>$user_data['user_type'],
				"folder"=>$folder
		];
		
		return $this->theme->scope ( 'models.addvr', $view )->render ();
	}
	
	/**
	 * 保存造景
	 *
	 * @return mixed
	 */
	public function addvrModel(Request $request) {
		
		// 获取当前登录用户的信息
		$userdata = UserModel::select ( 'users.name as nickname', 'user_detail.avatar', 'user_detail.balance' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		
		$uid = $this->user ['id'];
		
		// print_r($userdata);exit;
		return $this->saveVrModel ( $this->user ['id'], $request );
	}
	
	/**
	 * 造物保存
	 *
	 * @return mixed
	 */
	public function addModel(Request $request) {
		
		// 获取当前登录用户的信息
		$userdata = UserModel::select ( 'user_detail.*' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$uid = $this->user ['id'];
		
		return $this->saveModel ( $this->user ['id'], $request );
	}
	
	/**
	 * Use:作品浏览
	 * @param int $id
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function modelsView( $id = 0 ) {
		ModelsContentModel::ModelsIncrement($id);
		$ModelsContentModel = ModelsContentModel::getDataForId($id);

		if(empty($ModelsContentModel)) return redirect ( '/' );

		if ($ModelsContentModel->is_goods == 1 && $ModelsContentModel->transaction_mode == 2){
			if ( Auth::check() ){
				$status = ModelsContentModel::ifModelsIsGoods($ModelsContentModel);
				if($status)
					return response()->redirectToRoute('myOrder.viewPayDenied', ['id' => $ModelsContentModel->id]);
			}else{
				return redirect()->to('/login');
		}
									}

		$view = [ 
			'content'  => $ModelsContentModel,
			'uid'      => $ModelsContentModel ['uid'],
			'url'      => str_replace("01.".$ModelsContentModel['baseData'],"",$ModelsContentModel['cover_img']),
			'modelsId' => $id,
			'userData' => ModelsContentModel::getModelsUserType( $ModelsContentModel['uid'] ),
		];

		switch($ModelsContentModel["models_type"]){
			case 1:
				$this->initTheme( 'editor_view' );
				$this->theme->setTitle( $ModelsContentModel['title'] );
				return $this->theme->scope( 'models.modelsView', $view )->render();
				break;
			case 2:
				$this->initTheme( 'editorvr_view' );
				$this->theme->setTitle( $ModelsContentModel['title'] );
				return $this->theme->scope( 'models.vrView', $view )->render();
				break;
			case 3:
				$this->initTheme( '360_view' );
				$this->theme->setTitle( $ModelsContentModel['title'] );
				return $this->theme->scope( 'models.360View', $view )->render();
				break;
								}
							}

	/**
	 * 获取作品作者的相关信息
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getModelsUserInfo(Request $request) {
		$this->initTheme ( 'ajaxpage' ); // 主题初始化
		$id = $_POST['mid'];
		$ModelsContentModel = ModelsContentModel::getDataForId($id);

		if ($ModelsContentModel->is_goods == 1) {
			$web = ModelsContentModel::getOrderView($ModelsContentModel)['web'];
			$wap = ModelsContentModel::getOrderView($ModelsContentModel)['wap'];
			if($ModelsContentModel->transaction_mode == 2){
				if (Auth::check()) {
					$status = ModelsContentModel::ifModelsIsGoodsDecrement($ModelsContentModel);
					if( $status )
						return response()->json(['code' => 'buy', 'id' => $id]);
						} else {
					return response()->json(['code' => 'login', 'id' => 0]);
						}
					}
					}

		if( !empty($ModelsContentModel['paramaters']) )
			$tempArray = explode ( "|", $ModelsContentModel['paramaters'] );

		$user_data = ModelsContentModel::getUserAndUserDetailData( $ModelsContentModel['uid'] );

		$isAuthor = false;
		// 是否关注
		if ( $this->user['id'] > 0 ) {
			$user_data["isFocus"] = ModelsContentModel::ifIsFocus( $ModelsContentModel['uid'],$this->user['id'] );
			$islogin = true;
			if($this->user['id'] == $ModelsContentModel ['uid'])
				$isAuthor = true;
		} else {
			$islogin = false;
				}
		// 是否点赞
		$user_data["isFavorite"] = ModelsContentModel::ifIsFavorite( $id, $this->user['id'] );
		// 是否收藏
		$user_data["isCollect"]  = ModelsContentModel::ifIsCollect( $id, $this->user['id'] );

		$remarkList = ModelsRemarkModel::select('user_detail.avatar','user_detail.nickname','models_remark.content','models_remark.id','models_remark.uid','models_remark.created_at')
			->where ( 'models_remark.models_id', $id )
			->where('models_remark.remark_id','=','models_remark.id')
			->join("user_detail","user_detail.uid","=","models_remark.uid")->orderBy('models_remark.created_at','desc')->get();

		if(!$remarkList->first()){
			//echo '空的';exit;
			$remark[] ='';
		}else{
			//echo '不空的';exit;
			foreach($remarkList as $v){
				$remark[] = array(
					'remark' =>ModelsRemarkModel::select('user_detail.avatar','user_detail.nickname','models_remark.content','models_remark.id','models_remark.uid','models_remark.created_at')
						->where ( 'models_remark.models_id', $id )
						->where('models_remark.remark_id','=',$v['id'])
						->join("user_detail","user_detail.uid","=","models_remark.uid")->orderBy('models_remark.created_at','desc')
						->orderBy('models_remark.created_at','desc')->get()
				);
			}
		}

		$this->theme->setTitle( $ModelsContentModel['title'] );
		
		$view = [ 
				'content' => $ModelsContentModel,
			'userinfo'    => $user_data,
			'uid'         => $ModelsContentModel['uid'],
			'isLogin'     => $islogin,
			'lookNum'     => $ModelsContentModel['view_count'],
			'remarkList'  => $remarkList,
			'paramaters'  => $tempArray,
			'modelsId'    => $id,
			'isAuthor'    => $isAuthor,
			'remark'      => $remark,
			'web'      	  => isset($web) ? $web : '',
			'wap' 		  => isset($wap) ? $wap : '',
			'vipLogo'     => $user_data['vip'],
			'time'		  => self::DateTimeDiff(date("Y-m-d H:i:s",mb_substr($ModelsContentModel["create_time"],0,10))),
			'userLevel'   => ModelsContentModel::getUserLevel($user_data['experience']),
			'favoriteNum' => ModelsContentModel::getFavoriteNum($id),
			'collectNum'  => ModelsContentModel::getCollectionNum($id),
			'fans'        => ModelsContentModel::getModelsCommentNum($id),
			'otherModelList'=> ModelsContentModel::getOtherModelList($ModelsContentModel['uid'],$_POST['mid']),
			'enroll_status' => $ModelsContentModel['enroll_status'],
		];
		
		return $this->theme->scope( 'models.models_right', $view )->render();
	}


	/**
	 * 作品分享浏览
	 *
	 * @return mixed
	 */
	public function modelsShareView($id = 0) {
		ModelsContentModel::where ( 'id', $id )->increment ( 'view_count');
		$ModelsContentModel = ModelsContentModel::where ( 'id', $id )->first ();


		if ($ModelsContentModel == null) {
			return redirect ( '/' );
		}

		$user_data = UserModel::select ( 'user_type' )->where ( 'users.id', $ModelsContentModel ['uid'] )->first ();




		$view = [
			'content' => $ModelsContentModel,
			'uid' => $ModelsContentModel ['uid'],
			'url' => str_replace("01.".$ModelsContentModel ['baseData'],"",$ModelsContentModel ['cover_img']),
			'modelsId'=>$id,
			'userData'=>$user_data,
		];

		if ($ModelsContentModel ["models_type"] == 1) {
			$this->initTheme ( 'editor_share_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.modelsShareView', $view )->render ();
		} else if ($ModelsContentModel ["models_type"] == 2) {
			$user_data = UserModel::select ( 'users.id', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $ModelsContentModel ['uid'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first();
			$view['user']=$user_data;
			$this->initTheme ( 'editorvr_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.vrView', $view )->render ();
		}else if($ModelsContentModel ["models_type"] == 3){

			$this->initTheme ( '360_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );


			return $this->theme->scope ( 'models.360View', $view )->render ();

		}
	}
	/**
	 * 作品浏览无侧面
	 *
	 * @return mixed
	 */
	public function modelsEmbedView($id = 0) {
		ModelsContentModel::where ( 'id', $id )->increment ( 'view_count', 1 );
		$ModelsContentModel = ModelsContentModel::where ( 'id', $id )->first ();
		if ($ModelsContentModel == null) {
			return redirect ( '/' );
		}
		$user_data = UserModel::select ( 'user_type' )->where ( 'users.id', $ModelsContentModel ['uid'] )->first ();
		$view = [
			'content' => $ModelsContentModel,
			'uid' => $ModelsContentModel ['uid'],
			'url' => str_replace("01.".$ModelsContentModel ['baseData'],"",$ModelsContentModel ['cover_img']),
			'modelsId'=>$id,
			'userData'=>$user_data,
		];

		if ($ModelsContentModel ["models_type"] == 1) {
			$this->initTheme ( 'editor_embed_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.modelsEmbedView', $view )->render ();
		} else if ($ModelsContentModel ["models_type"] == 2) {
			$user_data = UserModel::select ( 'users.id', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $ModelsContentModel ['uid'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first();
			$view['user']=$user_data;
			$this->initTheme ( 'editorvr_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.vrView', $view )->render ();
		}else if($ModelsContentModel ["models_type"] == 3){
			$this->initTheme ( '360_view' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.360View', $view )->render ();
		}
	}
	/**
	 * VR造景浏览
	 *
	 * @return mixed
	 */
	public function modelsVrView($id = 0) {
		$this->initTheme ( 'editorvr_view' ); // 主题初始化
		$this->theme->setTitle ( '3D云2.0' );
		// $models_content = D('ModelsContent')->find($id);
		
		$ModelsContentModel = ModelsVrContentModel::where ( 'id', $id )->first ();
		
		$user_data = UserModel::select ( 'users.id', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $ModelsContentModel ['uid'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ();
		
		$this->theme->setTitle($ModelsContentModel['title']);
		
		$view = [ 
				'content' => $ModelsContentModel,
				'avatar' => $user_data ['avatar'],
				'nickname' => $user_data ['nickname'] 
		];
		
		return $this->theme->scope ( 'models.vrView', $view )->render ();
	}
	public function editModelSave(Request $request) {
		
		// 获取当前登录用户的信息
		$userdata = UserModel::select ( 'users.name as nickname', 'user_detail.avatar', 'user_detail.balance' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$upld="upload";
		$dom=".dom";
		$uid = $this->user ['id'];
		$time = $_POST ['time'];
		$imgFiles=explode(',',$_POST['imgFiles']);
		$imagePath='Uploads/Models/' . $uid . "/" . $time . "/Image/";
		$handle = opendir("./".$imagePath);
		if($handle){
			while(($file=readdir($handle))!=false){
				if($file != '.' && $file != '..' && !strstr($file,$upld) && !strstr($file,$dom)){
					if(!in_array($file,$imgFiles)){
						unlink("./".$imagePath.$file);
					}else{
						
						$cmd = "/usr/local/krpano-1.19-pr8/krpanotools maketiles " .$imagePath.$file." ".$imagePath."128_".$file." 0 -resize=128x*";
						//var_dump($cmd);
						exec($cmd);
					}
				}

			}
		}
		$files = $request->file ();
		
		// print_r($scene);
		
		if ($files) {
			
			$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3'
			];
			
			foreach ( $_FILES as $attr => $file ) {
				
				if (in_array ( $file ['type'], $ext )) {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time, $file ['name'] );
				} else {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time . "/Image", $file ['name'] );
					$cmd = "/usr/local/krpano-1.19-pr8/krpanotools maketiles " .$imagePath.$file ['name']." ".$imagePath."128_".$file ['name']." 0 -resize=128x*";
					exec($cmd);
				}
			}
			
			$baseurl = 'Uploads/Models/' . $uid . '/' . $time . '/';
			$imagePath = $baseurl . 'Image/';
			
			// 上传成功 获取上传文件信息d
			if ($_POST) {
				if (! is_dir ( $imagePath )) {
					@mkdir ( $imagePath, 0777 );
				}
				$dataP = $_POST;
				$img_src = $_POST ['img_src'];
				// $task_id = $_POST["taskId"];
				
				$imgbody = substr ( strstr ( $img_src, ',' ), 1 );
				$cover = base64_decode ( $imgbody );
				$res_img = file_put_contents ( $imagePath . "cover_large.png", $cover );
				
				$bigfilename = $imagePath . "cover_large.png";
				$filename = $imagePath . "cover.png";
				$res = $this->thumb ( $bigfilename, $filename, 300, 300, 2 );
				
				$middlefilename = $imagePath . "cover_middle.png";
				$res = $this->thumb ( $bigfilename, $middlefilename, 300, 300, 1 );
				
				
				$id = $_POST ["id"];
				
				if ($id > 0) {
					$data = array (
							'is_print' => $dataP ['is_print'],
							'is_share' => $dataP ['is_share'],
							'title' => $dataP ['title'],
							'content' => $dataP ['content'],
							'models_id' => $dataP ['models_id'],
							'folder_id' => $dataP ['folder_id'],
							'paramaters'=>  $dataP ['paramaters'],  
					);
					$result = ModelsContentModel::where ( 'id', $id )->update ( $data );
					
					if ($result) {
						
						$data = array (
								'result' => true,
								'message' => '保存成功' 
						);
						return json_encode ( $data );
					} else {
						$data = array (
								'result' => true,
								'message' => '保存失败' 
						);
						return json_encode ( $data );
					}
				} else {
					$data = array (
							'result' => true,
							'message' => '保存失败' 
					);
					return json_encode ( $data );
				}
			}
		} else {
			
			$data = array (
					'result' => true,
					'message' => '保存失败' 
			);
			return json_encode ( $data );
		}
		// }
	}
	
	/**
	 * 作品浏览
	 *
	 * @return mixed
	 */
	public function editModel($id = 0) {
		$ModelsContentModel = ModelsContentModel::select ( "models_content.*", "tc.pid as models_pid" )->leftjoin ( 'cate as tc', 'models_content.models_id', '=', 'tc.id' )->where ( 'models_content.id', $id )->first ();
		$user_data = UserModel::select ( 'users.name as nickname', 'user_detail.avatar', 'user_detail.balance','user_type')->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$modelsList = ModelsModel::select ( "id", "title" )->where ( "status", 1 )->get ()->toArray ();
		$folder = ModelsFolderModel::select ( 'id','name','team_id' )->where ( 'uid',  $this->user ['id'] )->get();
		// 判断是否当前用户的作品
		if ($ModelsContentModel ["uid"] != $this->user ['id']) {
			return redirect ( '/' );
		}
		
	// 获取所有分类
		$cate = TaskCateModel::where ( 'pid', '==', 0 )->paginate ( 10 );
			
		$modelsType = TaskCateModel::where ( 'id', $ModelsContentModel ['models_id'] )->first ();
		if ($modelsType != null) {
			$pid = $modelsType ["pid"];
			$ModelsContentModel ['models_pid'] = $pid;
			$category = TaskCateModel::findByPid([$pid]);
		}
		
		$param = array ();
		if ($ModelsContentModel ['paramaters'] != "") {
			$paramaters = $ModelsContentModel ['paramaters'];
			$tempArray = explode ( "|", $paramaters );
			foreach ( $tempArray as $m => $n ) {
				$paraArray = explode ( "：", $n );
				$param [$m] = $paraArray;
			}
		}
		

		
		$view = [ 
				'content' => $ModelsContentModel,
				'avatar' => $user_data ['avatar'],
				'uid' => $this->user ['id'],
				'nickname' => $user_data ['nickname'],
				'list' => $cate,
				"list1" => $category,
				'paramaters' => $param,
				"userType"=>$user_data['user_type'],
				"folder_id"=>$ModelsContentModel['folder_id'],
				"folder"=>$folder

		];
		
		if ($ModelsContentModel ["models_type"] == 1) {
			$this->initTheme ( 'editor_edit' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.editModel', $view )->render ();
		} else if ($ModelsContentModel ["models_type"] == 2) {
			$this->initTheme ( 'editorvr_edit' ); // 主题初始化
			$this->theme->setTitle ( $ModelsContentModel ['title'] );
			return $this->theme->scope ( 'models.editVrModel', $view )->render ();
		}
	}
	
	
	
	/**
	 * 造景编辑保存
	 *
	 * @return mixed
	 */
	public function editVrModelSave(Request $request) {
		
		// 获取当前登录用户的信息
		//$userdata = UserModel::select ( 'users.name as nickname', 'user_detail.avatar', 'user_detail.balance' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$upld="upload";
		$uid = $this->user ['id'];
		$time = $_POST ['time'];
		$imgFiles=explode(',',$_POST['imgFiles']);
		$imagePath='Uploads/Models/' . $uid . "/" . $time . "/Image/";
		$handle = opendir("./".$imagePath);
		if($handle){
			while(($file=readdir($handle))!=false){
				if($file != '.' && $file != '..'&&!strstr($file,$upld)){
					if(!in_array($file,$imgFiles)){
						if(is_dir("./".$imagePath.$file)){
							$this->deleteDir("./".$imagePath.$file);
						}else{
							unlink("./".$imagePath.$file);
						}
					}
				}
			}
		}
		$files = $request->file ();
		if ($files) {
			$time = $_POST ['time'];
			$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3'
			];
			foreach ( $_FILES as $attr => $file ) {
				if (in_array ( $file ['type'], $ext )) {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time, $file ['name'] );
				} else {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time . "/Image", $file ['name'] );
				}
			}
			$baseurl = 'Uploads/Models/' . $uid . '/' . $time . '/';
			$imagePath = $baseurl . 'Image/';
			// 上传成功 获取上传文件信息
			if ($_POST) {
				if (! is_dir ( $imagePath )) {
					@mkdir ( $imagePath, 0777 );
				}
				$dataP = $_POST;
				$img_src = $_POST ['img_src'];
				// $task_id = $_POST["taskId"];
				$imgbody = substr ( strstr ( $img_src, ',' ), 1 );
				$cover = base64_decode ( $imgbody );
				$res_img = file_put_contents ( $imagePath . "cover_large.png", $cover );
				
				$bigfilename = $imagePath . "cover_large.png";
				$filename = $imagePath . "cover.png";
				$res = $this->thumb ( $bigfilename, $filename, 300, 300, 2 );
				
				$middlefilename = $imagePath . "cover_middle.png";
				$res = $this->thumb ( $bigfilename, $middlefilename, 300, 300, 1 );
				
				$id = $_POST ["id"];
				
				if ($id > 0) {
					$data = array (
							'is_print' => $dataP ['is_print'],
							'is_share' => $dataP ['is_share'],
							'title' => $dataP ['title'],
							'content' => $dataP ['content'],
							'models_id' => $dataP ['models_id'],
							'folder_id' => $dataP ['folder_id'],
							'paramaters'=>  $dataP ['paramaters'],
					);
					
					// 'task_id'=>$task_id,
					$result = ModelsContentModel::where ( 'id', $id )->update ( $data );
					
					if ($result) {
						// $map = array('id'=>$Model_id,'sort'=>$Model_id);
						// $Model->save($map);
						
						$data = array (
								'result' => true,
								'message' => '保存成功' 
						);
						return json_encode ( $data );
					} else {
						$data = array (
								'result' => true,
								'message' => '保存失败' 
						);
						return json_encode ( $data );
					}
				} else {
					$data = array (
							'result' => true,
							'message' => '保存失败' 
					);
					return json_encode ( $data );
				}
			}
		} else {
			
			$data = array (
				'result' => true,
				'message' => '保存失败'
			);
			return json_encode ( $data );
		}
		// }
	}
	public function test() {
		$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3'
		];
		foreach ( $_FILES as $attr => $file ) {
			if (in_array ( $file ['type'], $ext )) {
				$request->file ( $attr )->move ( './Uploads/Models', $file ['name'] );
			} else {
				$request->file ( $attr )->move ( './Uploads/Models/Image', $file ['name'] );
			}
			// $request->file($attr)->move('./Uploads/Models',$file['name']);
			// var_dump($attr);
		}
	}
	public function saveImg(Request $request) {
		$time = $_POST ['time'];
		$uid = $_POST ['id'];
		$baseurl = 'Uploads/Models/' . $uid . '/' . $time . '/';
		$imagePath = $baseurl . 'Image/';
		if ($_FILES) {
			
			$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3' 
			];
			foreach ( $_FILES as $attr => $file ) {
				
				if (in_array ( $file ['type'], $ext )) {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time, $file ['name'] );
				} else {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time . "/Image", $file ['name'] );
				}
			}
			if (false) { // 上传错误提示错误信息
				$this->error ( $upload->getError () );
			} else { // 上传成功 获取上传文件信息
				if ($_POST) {
					if (! is_dir ( $imagePath )) {
						@mkdir ( $imagePath, 0777 );
					}
					$dataP = $_POST;
					$img_src = $_POST ['imgFile'];
					$img_test = str_replace ( ".jpg", "", $img_src );
					// $cmd="/tmp/krpano/krpano-1.19-pr8/krpanotools sphere2cube -config=/tmp/krpano/krpano-1.19-pr8/templates/convertdroplets.config ".$imagePath.$img_src." ".$imagePath.$img_src;//旧
					$cmd="/usr/local/krpano-1.19-pr8/krpanotools makepano -config=/usr/local/krpano-1.19-pr8/templates/multires.config ".$imagePath.$img_src." ".$imagePath.$img_src;//新

//					 $cmd="/usr/local/krpano-1.19-pr8/krpanotools register 'ruza4tk2X4MdHuE7djJQGr9QTftMFHiSH2ac5jkIlFgGqG0K0IVQnh5vF/cicLpwedsURI0QTg+UluEgysRLUytpeVFyBTxdwREEIGquRh1Hp2BY2EtZ8kdO2r6CHLJAFlzY5w6au1rnHwRhJXgaK8J75RwK1DYb/OEZ4tD2pniUrnMrpFwGWwcKnxGyNSmMktsU6qadFjKbMH3HUKNXa7Y59lEzbDZJbsTuP+UynwwBhogv8K+byjs2LDvU48sx4/CNHWi26g=='";//新
					// exec($cmd);

//					$cmd = "F:\\krpano-1.19-pr8\\MAKEMdroplet.bat " . $imagePath . $img_src;
					
					exec ( $cmd );
					for($x = 1; $x <= 8; $x ++) {
						for($y = 1; $y <= 8; $y ++) {
							$source = imagecreatefromjpeg ( $imagePath . $img_test . "/l1_d_" . $x . "_" . $y . ".jpg" );
							$rotate = imagerotate ( $source, - 90, 1 );
							imagejpeg ( $rotate, $imagePath . $img_test . "/l1_d_" . $x . "_" . $y . ".jpg" );
							$source = imagecreatefromjpeg ( $imagePath . $img_test . "/l1_u_" . $x . "_" . $y . ".jpg" );
							$rotate = imagerotate ( $source, 90, 1 );
							imagejpeg ( $rotate, $imagePath . $img_test . "/l1_u_" . $x . "_" . $y . ".jpg" );
						}
					}
					$source = imagecreatefromjpeg ( $imagePath . $img_test ."/mobile_d.jpg" );
					$rotate = imagerotate ( $source, - 90, 1 );
					imagejpeg ( $rotate, $imagePath . $img_test . "/mobile_d.jpg" );
					$source = imagecreatefromjpeg ( $imagePath . $img_test ."/mobile_u.jpg" );
					$rotate = imagerotate ( $source, 90, 1 );
					imagejpeg ( $rotate, $imagePath . $img_test ."/mobile_u.jpg" );
					echo "$imagePath$img_test";
					// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
				}
			}
		}
	}
	
	/**
	 * 保存作品
	 *
	 * @return mixed
	 */
	public function saveModel($uid = 0, Request $request) {
		$files = $request->file ();
		// print_r($scene);
		if ($files) {
			$time = $_POST ['time'];
			// $result = \FileClass::uploadModel ( $files, "models", $uid, $time );
			$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3' 
			];
			$baseurl = 'Uploads/Models/' . $uid . '/' . $time . '/';
			$imagePath = $baseurl . 'Image/';
			foreach ( $_FILES as $attr => $file ) {
				
				if (in_array ( $file ['type'], $ext )) {
					$request->file ( $attr )->move ( $baseurl, $file ['name'] );
				} else {
					$request->file ( $attr )->move ( $imagePath, $file ['name'] );
					$cmd = "/usr/local/krpano-1.19-pr8/krpanotools maketiles " .$imagePath.$file ['name']." ".$imagePath."128_".$file ['name']." 0 -resize=128x*";
					exec($cmd);
				}
			}
			// 上传成功 获取上传文件信息
			if ($_POST) {
				
				if (! is_dir ( $imagePath )) {
					@mkdir ( $imagePath, 0777, true );
				}
				$dataP = $_POST;
				$img_src = $_POST ['img_src'];
				// $task_id = $_POST["taskId"];
				
				$imgbody = substr ( strstr ( $img_src, ',' ), 1 );
				$cover = base64_decode ( $imgbody );
				$res_img = file_put_contents ( $imagePath . "cover_large.png", $cover );
				
				$bigfilename = $imagePath . "cover_large.png";
				$filename = $imagePath . "cover.png";
				
				$middlefilename = $imagePath . "cover_middle.png";
				

				$res = $this->thumb ( $bigfilename, $middlefilename, 300, 300, 1 );
				
				
				$res = $this->thumb ( $bigfilename, $filename, 300, 300, 2 );
				
				$data = array (
						'uid' => $uid,
						'create_time' => $dataP ['time'],
						'is_print' => $dataP ['is_print'],
						'is_share' => $dataP ['is_share'],
						'title' => $dataP ['title'],
						'content' => $dataP ['content'],
						'paramaters' => $dataP ['paramaters'],
						'status' => 1,
						'models_type' => 1,
						'cover_img' => $imagePath . 'cover.png',
						'scene' => $baseurl . 'scene.json',
						'sceneGlobal' => $baseurl . 'sceneGlobal.json',
						'sceneBG' => $baseurl . 'sceneBG.json',
						'baseData' => $baseurl . 'dataBase.json',
						'animationData' => './' . $baseurl . 'animationData.json',
						'models_id' => $dataP ['models_id'],
						'folder_id' => $dataP['folder_id']
				);
				// 'task_id'=>$task_id,
				
				$Model_id = ModelsContentModel::insertGetId ( $data );
				
				if ($Model_id > 0) {
					// $map = array('id'=>$Model_id,'sort'=>$Model_id);
					// $Model->save($map);
					
					$data = array (
							'result' => true,
							'message' => '保存成功' 
					);
					return json_encode ( $data );
				} else {
					$data = array (
							'result' => true,
							'message' => '保存失败' 
					);
					return json_encode ( $data );
				}
			}
		} else {
			
			return $result;
		}
	}
	
	/**
	 * 造景编辑保存
	 *
	 * @return mixed
	 */
	public function saveVrModel($uid = 0, Request $request) {
		
		// 获取当前登录用户的信息
		$userdata = UserModel::select ( 'users.name as nickname', 'user_detail.avatar', 'user_detail.balance' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$uid = $this->user ['id'];
		
		$files = $request->file ();
		
		// print_r($scene);
		
		if ($files) {
			
			$time = $_POST ['time'];
			$ext = [ 
					'application/json',
					'application/x-tgif',
					'audio/mpeg',
					'audio/mp3' 
			];
			
			foreach ( $_FILES as $attr => $file ) {
				
				if (in_array ( $file ['type'], $ext )) {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time, $file ['name'] );
				} else {
					$request->file ( $attr )->move ( './Uploads/Models/' . $uid . "/" . $time . "/Image", $file ['name'] );
				}
			}
			
			$baseurl = 'Uploads/Models/' . $uid . '/' . $time . '/';
			$imagePath = $baseurl . 'Image/';
			
			// 上传成功 获取上传文件信息
			if ($_POST) {
				if (! is_dir ( $imagePath )) {
					@mkdir ( $imagePath, 0777 );
				}
				$dataP = $_POST;
				$img_src = $_POST ['img_src'];
				// $task_id = $_POST["taskId"];
				
				$imgbody = substr ( strstr ( $img_src, ',' ), 1 );
				$cover = base64_decode ( $imgbody );
				$res_img = file_put_contents ( $imagePath . "cover_large.png", $cover );
				
				// $image = new \Think\Image();
				// $image->open($imagePath."cover.png");
				// // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
				// $image->thumb(300, 300,\Think\Image::IMAGE_THUMB_CENTER)->save($imagePath."cover.png");
				
				$bigfilename = $imagePath . "cover_large.png";
				$filename = $imagePath . "cover.png";
				
 				$middlefilename = $imagePath . "cover_middle.png";				
				$res = $this->thumb ( $bigfilename, $middlefilename, 300, 300, 1 );
				
// 				$res = $this->thumb ( $middlefilename, $filename, 300, 300, 2 );
				
				
				$res = $this->thumb ( $bigfilename, $filename, 300, 300, 2 );
				
				if (true) {
					
					$data = array (
							'uid' => $uid,
							'create_time' => $dataP ['time'],
							'is_print' => $dataP ['is_print'],
							'is_share' => $dataP ['is_share'],
							'title' => $dataP ['title'],
							'content' => $dataP ['content'],
							'status' => 1,
							'models_type' => 2,
							'cover_img' => $imagePath . 'cover.png',
							'sceneGlobal' => $baseurl . 'sceneGlobal.json',
							'baseData' => $baseurl . 'dataBase.json',
							'models_id' => $dataP ['models_id'],
							'folder_id' => $dataP ['folder_id'],
							'paramaters' => $dataP ['paramaters'] 
					);
					// 'task_id'=>$task_id,
					
					$Model_id = ModelsContentModel::insertGetId ( $data );
					
					if ($Model_id > 0) {
						// $map = array('id'=>$Model_id,'sort'=>$Model_id);
						// $Model->save($map);
						
						$data = array (
								'result' => true,
								'message' => '保存成功' 
						);
						return json_encode ( $data );
					} else {
						$data = array (
								'result' => true,
								'message' => '保存失败' 
						);
						return json_encode ( $data );
					}
				} else {
					$data = array (
							'result' => true,
							'message' => '保存失败' 
					);
					return json_encode ( $data );
				}
			}
		} else {
			
			return $result;
		}
		// }
	}
	function thumb($fname, $filename, $width, $height, $zoom) { // 文件名，新文件名，宽度，高度，方式（1等比缩放 2按尺寸剪切缩放）
		$dscFile = $filename;
		$data = getimagesize ( $fname, $info );
		switch ($data [2]) {
			case 1 :
				$im = @imagecreatefromgif ( $fname );
				break;
			case 2 :
				$im = @imagecreatefromjpeg ( $fname );
				break;
			case 3 :
				$im = @imagecreatefrompng ( $fname );
				break;
		}
		$srcW = imagesx ( $im ); // 原图的宽度
		$srcH = imagesy ( $im ); // 原图的高度
		
		if ($zoom == 1) {
			
			$temp_height = $height;
			$temp_width = $srcW / ($srcH / $height);
			
			
			$tmp_img = imagecreatetruecolor ( $temp_width, $temp_height );
			$bk_color = imagecolorallocate ( $tmp_img, 255, 255, 255 );
			imagefilledrectangle ( $tmp_img, 0, 0, $width, $height, $bk_color );
			imagecopyresampled ( $tmp_img, $im, 0, 0, 0, 0, $temp_width, $temp_height, $srcW, $srcH );
			imagedestroy ( $im );
			$img = $tmp_img;
			$cr = imagejpeg ( $img, $dscFile, 100 );
		} else {
			if (($srcW / $width) >= ($srcH / $height)) {
				$temp_height = $height;
				$temp_width = $srcW / ($srcH / $height);
				$src_X = abs ( ($width - $temp_width) / 2 );
				$src_Y = 0;
			} else {
				$temp_width = $width;
				$temp_height = $srcH / ($srcW / $width);
				$src_X = 0;
				$src_Y = abs ( ($height - $temp_height) / 2 );
			}
			$tmp_img = imagecreatetruecolor ( $temp_width, $temp_height );
			$bk_color = imagecolorallocate ( $tmp_img, 255, 255, 255 );
			imagefilledrectangle ( $tmp_img, 0, 0, $width, $height, $bk_color );
			imagecopyresampled ( $tmp_img, $im, 0, 0, 0, 0, $temp_width, $temp_height, $srcW, $srcH );
			$tmp_img2 = imagecreatetruecolor ( $width, $height );
			$bk_color2 = imagecolorallocate ( $tmp_img2, 255, 255, 255 );
			imagefilledrectangle ( $tmp_img2, 0, 0, $width, $height, $bk_color2 );
			imagecopyresampled ( $tmp_img2, $tmp_img, 0, 0, $src_X, $src_Y, $width, $height, $width, $height );
			imagedestroy ( $im );
			$img = $tmp_img2;
			$cr = imagejpeg ( $img, $dscFile, 100 );
		}
		
		if ($cr) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 作品点赞
	 *
	 * @param Request $request        	
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function favorite(Request $request) {
		$id = intval ( $_POST ['id'] ); // 被关注者ID
		
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！' 
			);
			return json_encode ( $data );
		}
		// 关注用户ID
		$uid = $this->user ['id'];
		$favorite = ModelsFavoriteModel::where ( "models_id", $id )->where ( 'uid', $uid )->first ();
		if ($favorite) {
			$data = array (
					'result' => false,
					'message' => '您已经给该作品点赞过！' 
			);
			return json_encode ( $data );
		}
		$data = array (
				'uid' => $uid,
				'models_id' => $id 
		)
		;
		$insert_id = ModelsFavoriteModel::insertGetId ( $data );
		if ($insert_id > 0) {
			$data = array (
					'result' => true,
					'message' => '操作成功！' 
			);
			return json_encode ( $data );
		}
		$data = array (
				'result' => false,
				'message' => '关注失败！' 
		);
		return json_encode ( $data );
	}
	
	

	/**
	 * 取消点赞
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function cancelfavorite(Request $request) {
	
		$id = intval ( $_POST ['id'] );//作品ID
	
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		//取消点赞用户ID
		$uid = $this->user ['id'];
	
		$result = ModelsFavoriteModel::where("models_id",$id)->where('uid',$uid)->delete();
		if ($result) {
			$data = array (
					'result' => true,
					'message' => '取消成功！'
			);
			return json_encode ( $data );
	
		}
		$data = array (
				'result' => false,
				'message' => '取消失败！'
		);
		return json_encode ( $data );
	}
	
	
	/**
	 * 收藏作品
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function collect(Request $request) {
		$id = intval ( $_POST ['id'] ); // 被关注者ID
	
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		// 关注用户ID
		$uid = $this->user ['id'];
		$favorite = ModelsCollectModel::where ( "models_id", $id )->where ( 'uid', $uid )->first ();
		if ($favorite) {
			$data = array (
					'result' => false,
					'message' => '您已经收藏过该作品！'
			);
			return json_encode ( $data );
		}
		$data = array (
				'uid' => $uid,
				'models_id' => $id
		)
		;
		$insert_id = ModelsCollectModel::insertGetId ( $data );
		if ($insert_id > 0) {
			$data = array (
					'result' => true,
					'message' => '操作成功！'
			);
			return json_encode ( $data );
		}
		$data = array (
				'result' => false,
				'message' => '关注失败！'
		);
		return json_encode ( $data );
	}
	
	
	/**
	 * 取消收藏
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function cancelCollect(Request $request) {
	
		$id = intval ( $_POST ['id'] );//作品ID
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		//取消收藏用户ID
		$uid = $this->user ['id'];
	
		$result = ModelsCollectModel::where("models_id",$id)->where('uid',$uid)->delete();
		if ($result) {
			$data = array (
					'result' => true,
					'message' => '取消成功！'
			);
			return json_encode ( $data );
	
		}
		$data = array (
				'result' => false,
				'message' => '取消失败！'
		);
		return json_encode ( $data );
	}
	

	/**
	 * 评论作品
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postRemark(Request $request) {

		$id = intval ( $_POST ['id'] );//作品ID

		if(isset($_POST['remark_id'])){
			$remark_id = intval( $_POST['remark_id']); //评论主题ID
			if (! $id || !$remark_id)  {
				$data = array (
					'result' => false,
					'message' => '参数错误！'
				);
				return json_encode ( $data );
			}
		}else{
			if (! $id) {
				$data = array (
					'result' => false,
					'message' => '参数错误！'
				);
				return json_encode ( $data );
			}
			$remark_id = 0; //主评论还没有，也就不存在回复id，设置一个默认为0
		}

		$uid = $this->user ['id'];   //用户ID
		$content = $_POST["content"];
		$data = array (
			'uid' => $uid,
			'models_id' => $id,
			'remark_id' => $remark_id,//评论ID
			'content'   =>$content,
			'created_at'=>date('Y-m-d H:i:s')
		)
		;
		$insert_id = ModelsRemarkModel::insertGetId($data);
		$userData = UserDetailModel::where('uid','=',$uid)->first();
		if(!$userData['avatar']){
			$userLogo = url('/themes/default/assets/images/defauthead.png');
		}else{
			$userLogo = url($userData['avatar']);
		};

		if ($insert_id > 0) {
			$data = array (
				'result'  => true,
				'message' => '评论成功！',
				'name'    => $userData['nickname'],
				'userLogo'=> $userLogo,
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '评论失败！'
		);

		return json_encode ( $data );

	}
	
	/**
	 * 作品编辑页面
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function modelsEditAjax($id=0) {
		
		$this->initTheme ( 'ajaxpage' ); // 主题初始化
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}		
		
		$models = ModelsContentModel::where("id",$id)->first();
		
		// 获取所有分类
		$cate = TaskCateModel::where ( 'pid', '==', 0 )->paginate ( 10 );
			
		$modelsType = TaskCateModel::where ( 'id', $models ['models_id'] )->first ();
		if ($modelsType != null) {
			$pid = $modelsType ["pid"];
			$models ['models_pid'] = $pid;
			$category = TaskCateModel::findByPid ( [
					$pid
			] );
		}
		$param = array ();
		if ($models ['paramaters'] != "") {
			$paramaters = $models ['paramaters'];
			$tempArray = explode ( "|", $paramaters );
			foreach ( $tempArray as $m => $n ) {
				$paraArray = explode ( "：", $n );
				$param [$m] = $paraArray;
			}
		}
		
		
		$domain = \CommonClass::getDomain ();
		$view = [
				'id' => $id,
				'list' => $cate,
				"list1" => $category,
				'content' => $models,
				'paramaters' => $param,
				'domain'=>$domain
		];
			
		return $this->theme->scope ( 'ajax.models_edit', $view )->render ();
		
		
		
	}
	
	
	/**
	 * 作品编辑页面
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postModelsEditAjax(Request $request) {
		
		$id = intval ( $_POST ['id'] );//作品ID
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		
		$data = array (
				'update_time' => time (),
				'title' => $_POST ['title'],
				'content' => $_POST ['content'],
				'paramaters' => $_POST ['paramater'],
				'models_id' => $_POST ['models_id'],
		);
		//更新模型表
		$result = ModelsContentModel::where('id', $id)->where('uid',$this->user ['id'])->update ( $data );
		if($result){
			$data = array (
					'result' => true,
					'message' => '修改成功'
			);
			return json_encode ( $data );
		
		}
		$data = array (
				'result' => false,
				'message' => '修改失败'
		);
		return json_encode ( $data );
		
	}	
	
	/**
	 * 编辑作品的隐私权限
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function editPrivate(Request $request) {
	
		$id = intval ( $_POST ['id'] );//作品ID
		$auth_type = $_POST["auth_type"];
		if (! $id) {
			$data = array (
					'result' => false,
					'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
	
		$data = array (
				'update_time' => time (),
				'is_private' => $auth_type,
		);
		//更新模型表
	$result = ModelsContentModel::where('id', $id)->where('uid',$this->user ['id'])->update( $data );
		if($result){
			$data = array (
					'result' => true,
					'message' => '修改成功'
			);
			return json_encode ( $data );
	
		}
		$data = array (
				'result' => false,
				'message' => '修改失败'
		);
		return json_encode ( $data );
	
	}

	/**
	 * 计算时间差
	 * @param $the_time
	 * @return string
	 *
	 */
	public static  function DateTimeDiff($the_time){//时间比较函数，返回两个日期相差几秒、几分钟、几小时或几天
		$now_time = date("Y-m-d H:i:s", time()); //获取当前时间
		$now_time = strtotime($now_time);
		$show_time = strtotime($the_time);
		$dur = $now_time - $show_time;
		//var_dump($dur);exit;
		if ($dur < 0) {
			return $the_time;
		} else {
			if ($dur < 60) {
				return $dur . '秒前';
			} else {
				if ($dur < 3600) {
					return floor($dur / 60) . '分钟前';
				} else {
					if ($dur < 86400) {
						return floor($dur / 3600) . '小时前';
					} else {
						if ($dur < 2592000) {//3天内
							return floor($dur / 86400) . '天前';
						} else {
							if ($dur < 31536000) {//3天内
								return floor($dur / 2592000) . '月前';
							}else{
								if ($dur >= 31536000) {//3天内
								return floor($dur / 31536000) . '年前';
							}else {
									return $the_time;
								}
							}
						}
					}
				}
			}
		}
	}



	//社会化分享
	public function getShare($id){
		$url = "http://www.zwuvr.com/view-".$id;
		return view('share.getShare',['url' => $url]);
	}
	/**
	 * 创建360图片
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function add360(Request $request) {

		$this->initTheme ( 'add360' ); // 主题初始化

		$query = TaskCateModel::where ( 'pid', '==', 0 );
		$cate = $query->paginate ( 10 );
		$folder = ModelsFolderModel::select ( 'id','name','team_id' )->where ( 'uid',  $this->user ['id'] )->get();
		if ($cate != null) {
			$pid = $cate [0] ["id"];
			$category = TaskCateModel::findByPid ( [
				$pid
			] );
		}
		$view = [
			'uid' => $this->user ['id'],
			'list' => $cate,
			"list1" => $category,
			'folder'=>$folder
		];

		return $this->theme->scope ( 'models.add360', $view )->render ();

	}

	/**
	 * 上传360图片
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function upload360(Request $request) {

		$uid = $this->user ['id'];
		$time = $_POST ['time'];
		$file = $_FILES['fileList'];

		$baseUrl = './Uploads/Models/' . $uid . "/" . $time . "/Image";

		$request->file("fileList")->move ($baseUrl, $file ['name'] );

		$data = array (
			'result' => true,
			'message' => '上传成功',
			'baseUrl'=>$baseUrl
		);
		return json_encode ( $data );
	}


	/**
	 * 上传360图片
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function save360(Request $request) {
		$dataP = $_POST;
		$uid = $this->user ['id'];
		$time = $dataP ['time'];
		$imageLength = $dataP ['imageLength'];
		$imagePath =  './Uploads/Models/' . $uid . "/" . $time . "/Image/";
		$imageType = $dataP ['imageType'];
		$imageType = str_replace("image/","",$imageType);
		$data = array (
			'uid' => $uid,
			'create_time' => $dataP ['time'],
			'title' => $dataP ['title'],
			'content' => $dataP ['content'],
			'paramaters' => $dataP ['paramaters'],
			'status' => 1,
			'models_type' => 3,
			'cover_img' => $imagePath . '01.'.$imageType,//封面
			'scene' => $imageLength,//图片数量  360全图
			'sceneGlobal' => $dataP ['imageWidth'],//图片宽度
			'sceneBG' => $dataP ['imageHeight'],//图片高度
			'baseData' => $imageType,//图片类型
			'models_id' => $dataP ['models_id'],
			'folder_id' => $dataP ['folder_id']
		);
		// 'task_id'=>$task_id,

		$Model_id = ModelsContentModel::insertGetId ( $data );

		if ($Model_id > 0) {
			// $map = array('id'=>$Model_id,'sort'=>$Model_id);
			// $Model->save($map);

			$data = array (
				'result' => true,
				'message' => '保存成功'
			);
			return json_encode ( $data );
		} else {
			$data = array (
				'result' => true,
				'message' => '保存失败'
			);
			return json_encode ( $data );
		}
	}

	//第一步：用户同意授权，获取code
	public function weiXin(){
		//这个链接是获取code的链接 链接会带上code参数
//		echo 11;exit;
		$REDIRECT_URI = "http://www.zwuvr.com";
		echo $REDIRECT_URI."<br>";
		$REDIRECT_URI = urlencode($REDIRECT_URI);
		echo $REDIRECT_URI."<br>";
		//以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的
		//$scope = "snsapi_userinfo"; //如果用这个会提示scope权限不够
		$scope = "snsapi_login";
		echo $scope."<br>";
		$state = md5(time());
		echo $state."<br>";
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->APPID."&redirect_uri=".$this->APPSECRET."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
//		echo $url;exit;
		return  $this->https_request($url);
		//return header("location:http://www.baidu.com");

	}
	//用户同意之后就获取code  通过获取code可以获取一切东西了  机智如我
	function getCode(){
		header("Content-type:text/html;charset=utf-8");
		//获取accse_token
		$code = $_GET["code"];
		//echo $code;
		//echo "<br>";
		//用code获取access_yoken
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".APPID."&secret=".APPSECRET."&code=".$code."&grant_type=authorization_code";
		//这里可以获取全部的东西  access_token openid scope
		$res = $this->https_request($url);
		$res  = json_decode($res,true);
		$openid = $res["openid"];
		$access_token = $res["access_token"];
		//echo $access_token;
		//这里是获取用户信息
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
		$res = $this->https_request($url);
		$res = json_decode($res,true);
		//var_dump($res);exit;
		//return $res;
		//写入session
		//print_r($res);
		//把用户的信息写入session 以备查用
		//$weixin = $res["openid"];
		//$nickname = $res["nickname"];
		//$_SESSION["weixin"]=$weixin;
		//header("location:http://pj.ppzw.com/index.php/Home/WxPay/getIndex");
		$open['openid'] = $res['openid'];

		$model = D('Admin/MyindexMember');
		$data = $model -> where($open)->select();

		if($data == null){
			$this->assign(array(
				'data' => $res,
			));
			$this->display('MyIndex/index');
		}else{
			$this->assign(array(
				'data' => $data[0],
				'title' => $data[0]['member_name'].'的品牌通名帖',
			));
			$this->display('MyIndex/getMem');
		}

		//return $res;
		//print_r($res);
		//写入session


//        print_r($res);
		//把用户的信息写入session 以备查用
//        $weixin = $res["openid"];
//        $nickname = $res["nickname"];
//        $img = $res['headimgurl'];
//        $_SESSION["weixin"]=$weixin;

//        header("location:http://pj.ppzw.com/index.php/Home/WxPay/accept");
	}
	public function https_request($url, $data = null)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	/**
	 * 更新作品淘宝链接状态
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateTbLink(){
		$id = intval ( $_POST ['id'] );//作品ID
		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}

		$data = array (
			'tblink' => 1,
		);
		$result = ModelsContentModel::where('id', $id)->where('uid',$this->user ['id'])->update( $data );
		if($result){
			$data = array (
				'result' => true,
				'message' => '修改成功'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '修改失败'
		);
		return json_encode ( $data );
	}



}






