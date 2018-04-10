<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\IndexController as IndexController;
use App\Http\Controllers\UserCenterController as BasicUserCenterController;
use App\Http\Controllers\AuthController;
use App\Http\Requests;
use App\Modules\Employ\Models\EmployCommentsModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\User\Http\Requests\PasswordRequest;
use App\Modules\User\Http\Requests\UserInfoRequest;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\PromoteModel;
use App\Modules\User\Model\TeamPowerModel;
use App\Modules\User\Model\TeamUserModel;
use App\Modules\User\Model\UserLoginModel;
use App\Modules\User\Model\TagsModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserTagsModel;
use App\Modules\User\Model\UserModel;

use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Http\Controllers\WorkModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsCollectModel;
use App\Modules\User\Model\UserFocusModel;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\Manage\Model\UserLevelModel;
use App\Modules\User\Model\UserTypeModel;
use Auth;
use Illuminate\Http\Request;
use Gregwar\Image\Image;
use Illuminate\Support\Facades\Session;
use Theme;

use DB;

use App\Modules\User\Model\ActionModel;
use App\Modules\User\Model\ActionLogModel;

class UserCenterController extends BasicUserCenterController {
	public function __construct() {
		parent::__construct ();
		$this->user = Auth::user();

		/* 当天首次签到状态判断 */
		$sign = ActionLogModel::isFirstSign();
		if( $sign['code'] ){
			$this->theme->set('first_sign', true);
		}else{
			$this->theme->set('first_sign', false);
		}
	}

//	public function userInfo(){
//		$user_data = UserModel::select ('users.user_type', 'user_detail.nickname', 'user_detail.avatar', 'user_detail.sex', 'user_detail.balance', 'user_detail.introduce', 'users.experience', 'users.con_login_day as loginDay' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
//		$dirname = 'Uploads/Models/'.$this->user ['id'];
//		if(file_exists($dirname)){
//			$countDir = $this->dirSize($dirname); //计算的是b
//		}else{
//			$countDir = 0;
//		}
//		//按照会员类型来获取存储空间
//		$storage = UserTypeModel::where('type_id','=',$user_data['user_type'])->first();
//		//转成b字节
//		$storageGetB = intval($storage['storage']) * 1024 * 1024 * 1024;
//		//转换百分比
//		$percentage = round($countDir / $storageGetB * 100 , 2).'%';
////		var_dump($aouts);exit;
//		$view = [
////			'user_data'  => $user_data,
////			'folder'     => $folderList,
////			'defaultFolderCount' => $defaultFolderCount,
////			'domain'     => $domain,
//			'percentage' => $percentage,
//			'storage'    => $storage['storage'],
//		];
//		$this->theme->set ( 'TYPE', 1 );
//		return $this->theme->scope ( 'user.user_info', $view )->render ();
//	}
	/**
	 * 用户中心首页页面
	 */
	public function index() {
		$this->initTheme ( 'userindex' ); // 主题初始化
		$this->theme->setTitle ( '用户中心' );
		$this->theme->set ( 'keywords', '用户中心,管理中心,用户管理中心' );
		$this->theme->set ( 'description', '用户中心，用户管理中心。' );

		PromoteModel::settlementByUid ( Auth::id () );
		$user_data = UserModel::select ('users.user_type', 'user_detail.nickname', 'user_detail.avatar', 'user_detail.sex', 'user_detail.balance', 'user_detail.introduce', 'users.experience', 'users.con_login_day as loginDay' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$domain = \CommonClass::getDomain ();
		$user_data ['avatar_url'] = $domain . '/' . $user_data ['avatar'] . md5 ( $this->user ['id'] . 'large' ) . '.jpg';

		//默认文件夹的作品总数
		$query = ModelsContentModel::where('uid', '=', $this->user ['id'] )->where('folder_id',0)->where('is_goods', 0);
		$defaultFolderCount = $query->count();

		// 获取用户的文件夹
		$folderList = ModelsFolderModel::select ( 'id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time','team_id' )->where ( 'uid', '=', $this->user ['id'] )->orderBy ( 'create_time', 'desc' )->get ();

		foreach ( $folderList as &$v ) {
			$v ["count"] = ModelsContentModel::where ( 'uid', '=', $this->user ['id'] )->where ( 'folder_id', $v ["id"] )->where('is_goods', 0)->count ();
		}
		$funcData   = TeamPowerModel::getTeamPowerDataForType(2);
		$view = [
			'user_data'  => $user_data,
			'folder'     => $folderList,
			'defaultFolderCount' => $defaultFolderCount,
			'domain'     => $domain,
			'funcUrl'    => $funcData,
//				'storage'    => $storage['storage'],
		];
		$this->theme->set ( 'TYPE', 1 );
		return $this->theme->scope ( 'user.index', $view )->render ();
	}



	/**
	 * 用户中心首页页面
	 */
	public function folder($id = 0) {
		$this->initTheme ( 'userindex' ); // 主题初始化
		$this->theme->setTitle ( '用户中心' );
		$this->theme->set ( 'keywords', '用户中心,管理中心,用户管理中心' );
		$this->theme->set ( 'description', '用户中心，用户管理中心。' );

		PromoteModel::settlementByUid ( Auth::id () );
		$user_data = UserModel::select ( 'user_type','user_detail.nickname', 'user_detail.avatar', 'user_detail.sex', 'user_detail.balance', 'user_detail.introduce', 'users.experience', 'users.con_login_day as loginDay' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();
		$domain = \CommonClass::getDomain ();
		$user_data ['avatar_url'] = $domain . '/' . $user_data ['avatar'] . md5 ( $this->user ['id'] . 'large' ) . '.jpg';

		if ($id != 0) {

			$models = ModelsContentModel::select ( 'id','uid','tblink', 'title', 'content', 'cover_img','upload_cover_image', 'create_time','is_private' )->where ( 'folder_id', '=', $id )->where('is_goods', 0)->orderBy ( 'create_time', 'desc' )->get ();

			$folder = ModelsFolderModel::select ( 'id','name', 'cover_img', 'update_time', 'create_time' )->where ( 'id', '=', $id )->orderBy ( 'create_time', 'desc' )->first ();
		} else {
			// 获取默认文件夹下的所有作品
			$models = ModelsContentModel::select ( 'id','uid','tblink','title', 'content', 'cover_img','upload_cover_image', 'create_time','is_private' )->where ( 'uid', '=', $this->user ['id'] )->where ( 'folder_id', '=', $id )->where('is_goods', 0)->orderBy ( 'create_time', 'desc' )->get ();
			$folder = null;
		}

		// 获取用户的所有文件夹
		$folderList = ModelsFolderModel::select ( 'id', 'name','team_id', 'cover_img', 'update_time', 'create_time' )->where ( 'id', '!=', $id )->where ( 'uid', '=', $this->user ['id'] )->orderBy ( 'create_time', 'desc' )->get ();

		$folderCount = ModelsContentModel::where ( 'uid', '=', $this->user ['id'] )->where ( 'id', '!=', $id )->count ();

		$view = [
			'user_data' => $user_data,
			'folder' => $folder,
			'models' => $models,
			'folderList' => $folderList,
			'domain' => $domain ,
			'folderCount' => $folderCount
		];
		$this->theme->set ( 'TYPE', 1 );
		return $this->theme->scope ( 'user.folder', $view )->render ();
	}

	/**
	 * 空间主页
	 *
	 * @return mixed
	 */
	public function zone($id = 0,$folder=0) {
		$this->initTheme ( 'zone' ); // 主题初始化
		if (! $id) {
			return redirect ()->to ( '/' );
		}

		// 获取用户信息
		$userInfo = UserDetailModel::where ( 'uid', $id )->first ();
		// 获取好评信息

		/*$empComments['total']= EmployCommentsModel::where('to_uid','=',$id)->count();
		if($empComments['total']){
			$empComments['speed']= EmployCommentsModel::where('to_uid','=',$id)->sum('speed_score');
			$empComments['quality']= EmployCommentsModel::where('to_uid','=',$id)->sum('quality_score');
			$empComments['attitude']= EmployCommentsModel::where('to_uid','=',$id)->sum('attitude_score');
			$empComments['good']= EmployCommentsModel::where('to_uid','=',$id)->where('type','=',1)->count();
			$empComments['sRate'] = $empComments['speed']/($empComments['total']*5)*100;
			$empComments['qRate'] = $empComments['quality']/($empComments['total']*5)*100;
			$empComments['aRate'] = $empComments['attitude']/($empComments['total']*5)*100;
			$empComments['gRate'] = $empComments['good']/($empComments['total'])*100;
		}else{
			$empComments['good']= 0;
			$empComments['sRate'] = 0;
			$empComments['qRate'] = 0;
			$empComments['aRate'] = 0;
			$empComments['gRate'] = 0;
		}*/

		$level=['一','二','三','四','五','六','七','八','九','十','十一'];
		//获取用户等级和经验值
		$user_data = UserModel::select ( 'users.id','users.experience','users.user_type', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $id )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ();
		$userLevel = UserLevelModel::select ('id', 'name', 'min', 'max' )->where ( 'min', '<=',  $user_data['experience'] )->where ( 'max', '>=',  $user_data['experience'] )->first ()->toArray ();
		$userLevel['pct']=($user_data['experience']-$userLevel['min'])/($userLevel['max']-$userLevel['min'])*100;
		$userLevel['level']=$level[$userLevel['id']-1];

		// 获取用户的文件夹
//		$folderList = ModelsFolderModel::select ( 'id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time' )->where ( 'uid', '=', $id )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();

		// 获取用户的所有作品
		$models = ModelsContentModel::select ( 'id', 'title', 'content','transaction_mode', 'cover_img','upload_cover_image', 'create_time' ,'price' , 'is_goods')->where ( 'uid', '=', $id );

//		$folder = intval ($folder);
//		if($folder && $folder != 0){
//			$models = $models->where ('folder_id',$folder);
//		}


		$models = $models->where ( 'is_private', '=', 0 )->orderBy ( 'transaction_mode', 'desc' )->get();
//		dd($models);exit;
		$modelsCount = ModelsContentModel::select ( 'id', 'title', 'content', 'cover_img', 'create_time' )->where ( 'uid', '=', $id )->where ( 'is_private', '=', 0 )->count ();
		//收藏作品数量
		$modelsCollectCount = ModelsCollectModel::where('uid',$id)->count();
		// 关注数量
		$userFen = UserFocusModel::where ( 'focus_uid', $id )->count ();
		$userFocus = UserFocusModel::where ( 'uid', $id )->count ();

		$userInfo ["fen"] = $userFen;
		$userInfo ["focus"] = $userFocus;
		// 是否关注
		if ($this->user ['id'] > 0) {
			$isFocus = UserFocusModel::where ( 'focus_uid', $id )->where ( 'uid', $this->user ['id'] )->first ();
			if ($isFocus) {
				$userInfo ["isFocus"] = true;
			} else {
				$userInfo ["isFocus"] = false;
			}
			$islogin = true;
		} else {
			$islogin = false;
		}

		$view = [
			'user_data' => $userInfo,
			'models' => $models,
			'list' => $models,
			'uid' => $id,
			'modelsCount' => $modelsCount,
			'isLogin' => $islogin,
			'folder'=>$folder,
			'userLevel'=>$userLevel,
			'modelsCollectCount'=>$modelsCollectCount,
			'user_type' => $user_data['user_type'],
//				'folderList' => $folderList,
//				'empCmt'=>$empComments
		];
		$this->theme->set ( 'TYPE', 1 );
		$this->theme->setTitle ($userInfo['nickname']);
		return $this->theme->scope ( 'user.zone.zoneindex', $view )->render ();
	}



	/**
	 * 空间商品展示页
	 *
	 * @return mixed
	 */
	public function zoneGoods($id = 0,$type=0) {

		$this->initTheme ( 'zone' ); // 主题初始化
		if (! $id) {
			return redirect ()->to ( '/' );
		}

		if($type != 1 && $type != 2 && $type != 3){
			$type = 1;
		}
		// 获取用户信息
		$userInfo = UserDetailModel::where ( 'uid', $id )->first ();
		$paginate = ($this->themeName = 'black') ? 18 : 12;
		$shopGoods = GoodsModel::select('goods.*','models_content.cover_img')->leftJoin('models_content','models_content.id','=','goods.mid')
			->where('goods.status',1)->where('goods.type',$type)->where('goods.uid',$id)->paginate (12);

		// 关注数量
		$userFen = UserFocusModel::where ( 'focus_uid', $id )->count ();
		$userFocus = UserFocusModel::where ( 'uid', $id )->count ();

		$userInfo ["fen"] = $userFen;
		$userInfo ["focus"] = $userFocus;
		// 是否关注
		if ($this->user ['id'] > 0) {
			$isFocus = UserFocusModel::where ( 'focus_uid', $id )->where ( 'uid', $this->user ['id'] )->first ();
			if ($isFocus) {
				$userInfo ["isFocus"] = true;
			} else {
				$userInfo ["isFocus"] = false;
			}
			$islogin = true;
		} else {
			$islogin = false;
		}



		$view = [
			'user_data' => $userInfo,
			'models' => $shopGoods,
			'list' => $shopGoods,
			'uid' => $id,
			'type' => $type,
			'isLogin' => $islogin,
		];
		$this->theme->setTitle ($userInfo['nickname']. '的空间' );
		return $this->theme->scope ( 'user.zone.goods', $view )->render ();

	}







	/**
	 * 用户详细信息修改页面
	 *
	 * @return mixed
	 */
	public function info() {
		$this->initTheme ( 'userinfo' ); // 主题初始化
		$this->theme->setTitle ( '用户中心' );
		// 创建用户的信息
		$uinfo = UserDetailModel::findByUid ( $this->user ['id'] );
		// 查询省信息
		$province = DistrictModel::findTree ( 0 );
		// 查询城市数据
		if (! is_null ( $uinfo ['province'] )) {
			$city = DistrictModel::findTree ( $uinfo ['province'] );
		} else {
			$city = DistrictModel::findTree ( $province [0] ['id'] );
		}
		// 查询地区信息
		if (! is_null ( $uinfo ['city'] )) {
			$area = DistrictModel::findTree ( $uinfo ['city'] );
		} else {
			$area = DistrictModel::findTree ( $city [0] ['id'] );
		}

		$user = UserModel::where ( 'id', Auth::id () )->first ();

		$view = array (
			'uinfo' => $uinfo,
			'province' => $province,
			'city' => $city,
			'area' => $area,
			'user' => $this->user,
			'mobile' => $user ['mobile']
		);
		return $this->theme->scope ( 'user.info', $view )->render ();
	}

	/* @author orh @time 2017-08-01 @add start */
	/**
	 * 安全设置
	 *
	 * @param void
	 * @return mixed
	 */
	public function safeSet(){
		$this->initTheme ( 'userinfo' ); // 主题初始化
		$this->theme->setTitle ( '安全设置' );

		$view = [
			'user' => $this->user,
			'loginList' => UserLoginModel::getLoginInfo( $this->user->id )
		];

		return $this->theme->scope ( 'user.safeSet', $view )->render ();
	}

	/**
	 * 微信绑定
	 *
	 * @param void
	 * @return mixed
	 */
	public function bindWeChat(){
		$this->initTheme ( 'userinfo' ); // 主题初始化
		$this->theme->setTitle ( '微信绑定' );

		$view = [];

		return $this->theme->scope ( 'user.bindWeChat', $view )->render ();
	}
	/* @author orh @time 2017-08-01 @add end */

	/**
	 * 用户信息更新，在第一次的时候创建
	 *
	 * @param UserInfoRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function infoUpdate(UserInfoRequest $request) {
		$data = $request->except ( '_token', 'name', 'email' );


		$result = UserDetailModel::where ( 'uid', $this->user ['id'] )->update ( $data );

		if (! $result) {
			return redirect ()->back ()->with ( [
				'error' => '修改失败！'
			] );
		}

        // 新手任务【完善资料】
        $ok = true;
        unset($data['mobile_status'], $data['wechat_status'], $data['qq_status'], $data['sex']);
        foreach ($data as $v) {
            if (empty($v)) {
                $ok = false;
                break;
            }
        }

		$action = new ActionModel();
		$msg = [ 'code' => 'success', 'massage' => '修改成功！' ];
		if( $ok ){
			$action->newbieTaskIE( 3, $request->getClientIp() );
		}else{
			$ret = $action->checkNewbieTask(3);
			if($ret['code']){
				$msg = [ 'code' => 'error', 'massage' => '如果您不填写完整内容，您将无法完成【新手任务：完善资料】！' ];
			}
		}

		return redirect ()->back ()->with ($msg);
	}

	/**
	 * 用户修改密码页
	 *
	 * @return mixed
	 */
	public function loginPassword() {
		$this->initTheme ( 'userinfo' );
		$this->theme->setTitle ( '修改密码' );

		$view = [
			'user' => $this->user
		];

		return $this->theme->scope ( 'user.userpassword', $view )->render ();
	}

	/**
	 * 用户修改密码
	 *
	 * @param PasswordRequest $request
	 * @return $this|\Illuminate\Http\RedirectResponse
	 */
	public function passwordUpdate(PasswordRequest $request) {
		// 验证用户的密码
		$data = $request->except ( '_token' );

		// 验证原密码是否正确
		if (! UserModel::checkPassword ( $this->user ['email'], $data ['oldpassword'] )) {
			return redirect ()->back ()->with ( 'error', '原始密码错误！' );
		}
		$result = UserModel::psChange ( $data, $this->user );

		if (! $result) {
			return redirect ()->back ()->with ( 'error' . '密码修改失败！' ); // 回传错误信息
		}
		Auth::logout ();
		return redirect ( 'login' )->with ( [
			'message' => '修改密码成功，请重新登录'
		] );
	}

	/**
	 * 用户修改支付密码
	 *
	 * @return mixed
	 */
	public function payPassword() {
		$this->initTheme ( 'userinfo' );
		$this->theme->setTitle ( '修改支付密码' );
		UserDetailModel::closeTips ();

		$view = [
			'user' => $this->user
		];
		return $this->theme->scope ( 'user.paypassword', $view )->render ();
	}

	/**
	 * 检测发送邮件倒计时时间(修改支付密码)
	 */
	public function checkInterVal() {
		$sendTime = Session::get ( 'send_code_time' );
		$nowTime = time ();
		if (empty ( $sendTime )) {
			return response ()->json ( [
				'errCode' => 3
			] );
		} else {
			if ($nowTime - $sendTime < 60) { // 时间在0-60
				return response ()->json ( [
					'errCode' => 1,
					'interValTime' => 60 - ($nowTime - $sendTime)
				] );
			} else {
				return response ()->json ( [
					'errCode' => 2
				] ); // 大于60
			}
		}
	}

	/**
	 * 用户修改密码发送邮件
	 */
	public function sendEmail(Request $request) {
		$email = $request->get ( 'email' );
		// 验证用户填写邮箱
		if ($email != $this->user ['email']) {
			return response ()->json ( [
				'errCode' => 0,
				'errMsg' => '请输入注册时候填写的邮箱！'
			] );
		}
		$result = \MessagesClass::sendCodeEmail ( $this->user );

		if (! $result) {
			return response ()->json ( [
				'errCode' => 0,
				'errMsg' => $result
			] );
		} else {
			Session::put ( 'send_code_time', time () );
			return response ()->json ( [
				'errCode' => 1
			] );
		}
	}

	/**
	 * 验证用户输入邮箱是否注册邮箱
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function checkEmail(Request $request) {
		$sendTime = Session::get ( 'send_code_time' );
		$nowTime = time ();
		if ($nowTime - $sendTime < 60) {
			return response ()->json ( [
				'errCode' => 0,
				'errMsg' => '请稍后点击发送验证码！'
			] );
		}
		$email = $request->get ( 'email' );
		// 验证用户填写邮箱
		if ($email != $this->user ['email']) {
			return response ()->json ( [
				'errCode' => 0,
				'errMsg' => '请输入注册时候填写的邮箱！'
			] );
		} else {
			return response ()->json ( [
				'errCode' => 1
			] );
		}
	}

	/**
	 * 验证用户的验证码跳转修改密码页面
	 */
	public function validateCode(Request $request) {
		$this->initTheme ( 'userinfo' );
		$this->theme->setTitle ( '修改支付密码' );
		// 验证验证码
		$code = $request->get ( 'code' );
		$email = $request->get ( 'email' );
		$session_code = Session::get ( 'payPasswordCode' );
		if ($code != $session_code) {
			return redirect ()->to ( 'user/payPassword' )->withInput ( [
				'email' => $email,
				'code' => $code
			] )->withErrors ( [
				'code' => '验证码错误'
			] );
		}

		return $this->theme->scope ( 'user.paypasswordupdate' )->render ();
	}

	/**
	 * 用户修改支付密码提交
	 *
	 * @param PasswordRequest $request
	 * @return $this|\Illuminate\Http\RedirectResponse
	 */
	public function payPasswordUpdate(PasswordRequest $request) {
		$data = $request->except ( '_token' );

		$result = UserModel::payPsUpdate ( $data, $this->user );

		if (! $result) {
			return redirect ()->back ()->with ( 'error', '密码修改失败！' ); // 回传错误信息
		}

		return redirect ()->to ( 'user/payPassword' )->with ( 'message', '密码修改成功！' );
	}

	/**
	 * 标签修改页面
	 *
	 * @return mixed
	 */
	public function skill() {
		$this->initTheme ( 'userinfo' );
		$this->theme->setTitle ( '标签设置' );
		// 查询用户原有的标签id
		$tag = UserTagsModel::myTag ( $this->user ['id'] );
		$tags = array_flatten ( $tag );
		// 查询所有标签
		$hotTag = TagsModel::findAll ();

		$view = array (
			'hotTag' => $hotTag,
			'tags' => $tags,
			'user' => $this->user
		);
		return $this->theme->scope ( 'user.sign', $view )->render ();
	}

	/**
	 * 用户设置标签一次性添加
	 *
	 * @param Request $request
	 */
	public function skillSave(Request $request) {
		$data = $request->all ();

		$tags = explode ( ',', $data ['tags'] );
		// 查询用户所有的标签id
		$old_tags = UserTagsModel::myTag ( $this->user ['id'] );
		$old_tags = array_flatten ( $old_tags );
		// 验证用户更改了标签
		if ((empty ( $data ['tags'] ) && $data ['tags'] != 'change')) {
			return redirect ()->back ()->withErrors ( [
				'tags_name' => '请更新标签后提交！'
			] );
		}

		// 判断是在添加标签还是在删除标签
		if (count ( $tags ) > count ( $old_tags )) {
			// 判断用户有多少个标签
			if (count ( $tags ) > 5) {
				return redirect ()->back ()->withErrors ( [
					'tags_name' => '一个用户最多只能有五个标签'
				] );
			}
			$dif_tags = array_diff ( $tags, $old_tags );
			$result = UserTagsModel::insert ( $dif_tags, $this->user ['id'] );
			if (! $result)
				return redirect ()->back ()->with ( 'error', '更新标签错误' ); // 回传错误信息
		} else if (count ( $tags ) < count ( $old_tags )) {
			$dif_tags = array_diff ( $old_tags, $tags );
			$result = UserTagsModel::tagDelete ( $dif_tags, $this->user ['id'] );
			if (! $result)
				return redirect ()->back ()->with ( 'error', '更新标签错误' ); // 回传错误信息
		} else if (count ( $tags ) == count ( $old_tags )) {
			// 增加新标签
			$dif_tags = array_diff ( $tags, $old_tags );
			if (empty ( $dif_tags )) {
				return redirect ()->back ()->withErrors ( [
					'tags_name' => '请更新标签后提交！'
				] );
			}
			$result2 = UserTagsModel::insert ( $dif_tags, $this->user ['id'] );
			// 删除老标签
			$dif_tags = array_diff ( $old_tags, $tags );
			$result = UserTagsModel::tagDelete ( $dif_tags, $this->user ['id'] );
			if (! $result && ! $result2)
				return redirect ()->back ()->with ( 'error', '更新标签错误' ); // 回传错误信息
		}

		return redirect ()->back ()->with ( 'massage', '标签更新成功' );
	}

	/**
	 * 用户头像设置页
	 *
	 * @return mixed
	 */
	public function userAvatar() {
		$theme = Theme::uses ( 'default' )->layout ( 'usercenter' );
		$theme->setTitle ( '头像设置' );
		// 查询用户的头像信息
		$user_detail = UserDetailModel::findByUid ( $this->user ['id'] );

		$view = [
			'avatar' => $user_detail ['avatar'],
			'id' => $this->user ['id']
		];

		return $this->theme->scope ( 'user.avatar', $view )->render ();
	}

	/**
	 * ajax头像裁剪
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function avatarEdit(Request $request) {
		$data = $request->except ( '_token' );
		$data = $data ['data'];
		// 查询用户头像路径
		$user_head = UserDetailModel::findByUid ( $this->user ['id'] );
		$path = $user_head ['avatar'] . md5 ( $this->user ['id'] . 'large' ) . '.jpg';
		$img = Image::open ( $path );
		$img->crop ( intval ( $data ['x'] ), intval ( $data ['y'] ), intval ( $data ['width'] ), intval ( $data ['height'] ) );
		$result = $img->save ( $path );
		$domain = \CommonClass::getDomain ();
		$json = [
			'status' => 1,
			'message' => '成功保存',
			'url' => $path,
			'path' => $domain . '\\' . $path
		];
		// 生成三张图片
		$result2 = \FileClass::headHandle ( $json, $this->user ['id'] );

		if (! $result || ! $result2) {
			array_replace ( $json, [
				'status' => 0,
				'message' => '编辑失败'
			] );
		}
		return response ()->json ( $json );
	}

	/**
	 * ajax头像上传
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function ajaxAvatar(Request $request) {
		$file = $request->file ( 'avatar' );

		// 处理上传图片
		$result = \FileClass::uploadFile ( $file, $path = 'user' );
		$result = json_decode ( $result, true );

		// 判断文件是否上传
		if ($result ['code'] != 200) {
			return response ()->json ( [
				'code' => 0,
				'message' => $result ['message']
			] );
		}
		// 产生一条新纪录
		$attachment_data = array_add ( $result ['data'], 'status', 1 );
		$attachment_data ['created_at'] = date ( 'Y-m-d H:i:s', time () );
		// 将记录写入到attchement表中
		$result2 = AttachmentModel::create ( $attachment_data );
		if (! $result2)
			return response ()->json ( [
				'code' => 0,
				'message' => $result ['message']
			] );

		// 删除原来的头像
		$avatar = \CommonClass::getAvatar ( $this->user ['id'] );
		if (file_exists ( $avatar )) {
			$file_delete = unlink ( $avatar );
			if ($file_delete) {
				AttachmentModel::where ( 'url', $avatar )->delete ();
			} else {
				AttachmentModel::where ( 'url', $avatar )->update ( [
					'status' => 0
				] );
			}
		}
		// 修改用户头像
		$data = [
			'avatar' => $result ['data'] ['url']
		];
		$result3 = UserDetailModel::updateData ( $data, $this->user ['id'] );
		if (! $result3) {
			return \CommonClass::formatResponse ( '文件上传失败' );
		}

		return response ()->json ( $result );
	}

	/**
	 * ajax获取城市、地区数据
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function ajaxCity(Request $request) {
		$id = intval ( $request->get ( 'id' ) );
		if (! $id) {
			return response ()->json ( [
				'errMsg' => '参数错误！'
			] );
		}
		$province = DistrictModel::findTree ( $id );
		// 查询第一个市的数据
		$area = DistrictModel::findTree ( $province [0] ['id'] );
		$data = [
			'province' => $province,
			'area' => $area
		];
		return response ()->json ( $data );
	}

	/**
	 * ajax获取地区的数据
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function ajaxArea(Request $request) {
		$id = intval ( $request->get ( 'id' ) );
		if (! $id) {
			return response ()->json ( [
				'errMsg' => '参数错误！'
			] );
		}
		$area = DistrictModel::findTree ( $id );
		return response ()->json ( $area );
	}

	/**
	 * ajax进行每日签到
	 *
	 * @param Request $request
	 * @return json
	 */
	public function ajaxDailySign(Request $request){
		$json = [ 'code' => 'error', 'msg' => '' ];
		if(Auth::check()){
			$action = new ActionModel();
			$status = $action->dailyIE( $request->getClientIp(), 'user_sign' );
			if( $status['code'] )
				$json['code'] = 'success';
		}
		return json_encode( $json );
	}

	/**
	 * 新建文件夹
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function createFolder(Request $request) {
		$name = $_POST ['name'];
		if (! $name) {
			$data = array (
				'result' => false,
				'message' => "文件夹名称为空!"
			);
			return json_encode ( $data );
		}
		$uid = $this->user ['id'];
		$data = array (
			'uid' => $uid,
			'name' => $name,
			'create_time' => time ()
		);
		// 'task_id'=>$task_id,

		$Model_id = ModelsFolderModel::insertGetId ( $data );
		if ($Model_id > 0) {

			$data = array (
				'result' => true,
				'id' => $Model_id
			);
		} else {

			$data = array (
				'result' => false,
				'message' => "保存失败!"
			);
		}
		return json_encode ( $data );
	}

	/**
	 * 修改文件夹
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateFolder(Request $request) {
		$name = $_POST ['name'];
		$id = intval ( $_POST ['id'] );
		if (! $name || ! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		$uid = $this->user ['id'];
		$data = array (
			'name' => $name,
			'update_time' => time ()
		);

		$result = ModelsFolderModel::where ( 'id', $id )->update ( $data );
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
	}

	/**
	 * 删除文件夹
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteFolder(Request $request) {
		$id = intval ( $_POST ['id'] );
		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		
	
		$re = ModelsFolderModel::where ( 'id', $id )->where ( 'uid', '=', $this->user ['id'] )->delete ();
		if (! $re) {
			$data = array (
				'result' => false,
				'message' => '操作失败！'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => true,
			'message' => '删除成功！'
		);
		return json_encode ( $data );
	}

	/**
	 * 删除文件夹及作品
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteFolderProduct(Request $request) {
		$id = intval ( $_POST ['id'] );
		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		//开启事务
		DB::beginTransaction();
		$res = ModelsFolderModel::where ( 'id', $id )->where ( 'uid', '=', $this->user ['id'] )->delete ();
		$res2=  ModelsContentModel::where ( 'folder_id', $id )->where ( 'uid', '=', $this->user ['id'] )->delete ();
		if($res && $res2){
			DB::commit();
			$data = array (
				'result' => true
			);
			return json_encode ( $data );
		}else{
			DB::rollback();
			$data = array (
				'result' => false,
				'message' => '操作失败！'
			);
			return json_encode ( $data );
		}

	}

	public function saveCoverImg(Request $request) {
		$file = $request->file ( 'coverImg' );

		// 处理上传图片
		$result = \FileClass::uploadFile ( $file, $path = 'user' );
		$result = json_decode ( $result, true );

		$id = $_POST ['editFolderId'];
		// 判断文件是否上传
		if ($result ['code'] != 200) {
			return response ()->json ( [
				'code' => 0,
				'message' => $result ['message']
			] );
		}
		// 产生一条新纪录
		$attachment_data = array_add ( $result ['data'], 'status', 1 );
		$attachment_data ['created_at'] = date ( 'Y-m-d H:i:s', time () );
		// 将记录写入到attchement表中
		$result2 = AttachmentModel::create ( $attachment_data );
		if (! $result2)
			return response ()->json ( [
				'code' => 0,
				'message' => $result ['message']
			] );

		// 删除原来的头像
		if(!isset($id)){
			$avatar = \CommonClass::getAvatar ( $this->user ['id'] );
			if (file_exists ( $avatar )) {
				$file_delete = unlink ( $avatar );
				if ($file_delete) {
					AttachmentModel::where ( 'url', $avatar )->delete ();
				} else {
					AttachmentModel::where ( 'url', $avatar )->update ( [
						'status' => 0
					] );
				}
			}
		}

		// 修改用户头像
		$data = [
			'cover_img' => $result ['data'] ['url']
		];

		$result3 = ModelsFolderModel::where( 'id', $id )->update ( $data );
		if (! $result3) {
			return \CommonClass::formatResponse ( '文件上传失败' );
		}

		return response ()->json ( $result );
	}

	/**
	 * 修改作品信息
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function editModel(Request $request) {
		$name = $_POST ['name'];
		$content = $_POST ['content'];
		$id = intval ( $_POST ['id'] );
		if (! $name || ! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		$uid = $this->user ['id'];
		$data = array (
			'title' => $name,
			'content' => $content,
			'update_time' => time ()
		);

		$result = ModelsContentModel::where ( 'id', $id )->where ( "uid", $uid )->update ( $data );
		if ($result) {

			$data = array (
				'result' => true,
				'message' => '保存成功'
			);
			return json_encode ( $data );
		} else {
			$data = array (
				'result' => false,
				'message' => '保存失败'
			);
			return json_encode ( $data );
		}
	}

	/**
	 * 删除作品信息
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteModel(Request $request) {
		$id = intval ( $_POST ['id'] );
		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}

		$models = ModelsContentModel::where ( 'id', $id )->where ( 'uid', $this->user ['id'] )->first ();
		if ($models == null) {
			$data = array (
				'result' => false,
				'message' => '删除失败！'
			);
			return json_encode ( $data );
		}

		$pathItep = $models ['cover_img'];
		$pathMtep = $models ['scene'];
		$pathIarr = explode ( '/', $pathItep );
		$pathMarr = explode ( '/', $pathMtep );
		array_pop ( $pathIarr );
		array_pop ( $pathMarr );
		$pathI = implode ( '/', $pathIarr );
		$pathM = implode ( '/', $pathMarr );
		$path = array (
			$pathM,
			$pathI
		);

		$res = $this->delDirAndFile ( $path, TRUE );

		if ($res == true) {
			$data = array (
				'result' => false,
				'message' => '操作失败！'
			);
			return json_encode ( $data );
		}
		$re = ModelsContentModel::where ( 'id', $id )->where ( 'uid', $this->user ['id'] )->delete ();
		if (! $re) {
			$data = array (
				'result' => false,
				'message' => '操作失败！'
			);
			return json_encode ( $data );
		}

		$data = array (
			'result' => true
		);
		return json_encode ( $data );
	}

	/**
	 * 设置文件夹的访问权限
	 * saveFolderAuth
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function setFolderAuth(Request $request) {
		$id = intval ( $_POST ['id'] );
		$auth_type = $_POST ["auth_type"];

		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}

		$data = array (
			'auth_type' => $auth_type,
			'id'=>$id,
			'update_time' => time ()
		);

		$result = ModelsContentModel::updatePrivate($data);
		if ($result) {
			$data = array (
				'result' => true,
				'message' => '保存成功'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '保存失败'
		);
		return json_encode ( $data );
	}

	/**
	 * 设置封面
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function setFolderCover(Request $request) {

		$id = intval($_POST ['id']);
		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		$models = ModelsContentModel::select ( 'folder_id', 'cover_img' )->where ( "id", $id )->first ();
		if ($models) {

			if ($models ['folder_id'] == 0) {
				$data = array (
					'result' => false,
					'message' => '默认相册封面无法设置！'
				);
				return json_encode ( $data );
			}
			$data = array (
				'cover_img' => $models ['cover_img'],
				'update_time' => time ()
			);
			$result = ModelsFolderModel::where ( 'id', $models ['folder_id'] )->update ( $data );
			if ($result) {
				$data = array (
					'result' => true,
					'message' => '保存成功'
				);
				return json_encode ( $data );
			}
			$data = array (
				'result' => false,
				'message' => '保存失败'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '保存失败'
		);
		return json_encode ( $data );
	}

	/**
	 * 移动作品到新文件夹
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function moveModel(Request $request) {
		$id = intval ( $_POST ['id'] );
		$folder_id = intval ( $_POST ['folder_id'] );
		if (! $id || ! $folder_id) {
			$data = array (
				'result' => false,
				'message' => '参数错误！'
			);
			return json_encode ( $data );
		}
		$data = array (
			'folder_id' => $folder_id,
			'update_time' => time ()
		);
		$result = ModelsContentModel::where ( 'id', $id )->where ( "uid", $this->user ['id'] )->update ( $data );
		if ($result) {
			$data = array (
				'result' => true,
				'message' => '保存成功'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '保存失败'
		);
		return json_encode ( $data );
	}

	/**
	 * 删除目录及目录下所有文件或删除指定文件
	 *
	 * @param str $path
	 *        	待删除目录路径
	 * @param int $delDir
	 *        	是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
	 * @return bool 返回删除状态
	 */
	function delDirAndFile($path, $delDir = FALSE) {
		if (is_array ( $path )) {
			foreach ( $path as $subPath )
				$this->delDirAndFile ( $subPath, $delDir );
		} else if (is_dir ( $path )) {
			$handle = opendir ( $path );
			if ($handle) {
				while ( false !== ($item = readdir ( $handle )) ) {
					if ($item != "." && $item != "..")
						is_dir ( $path . "/" . $item ) ? $this->delDirAndFile ( $path . "/" . $item, $delDir ) : unlink ( $path . "/" . $item );
				}
				closedir ( $handle );
				if ($delDir)
					return rmdir ( $path );
			}
		} else {
			if (file_exists ( $path )) {
				return unlink ( $path );
			} else {
				return FALSE;
			}
		}
		clearstatcache ();
	}

	/**
	 * 关注用户
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function focus(Request $request) {
		$id = intval ( $_POST ['uid'] ); // 被关注者ID

		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '关注失败！'
			);
			return json_encode ( $data );
		}
		// 关注用户ID
		$uid = $this->user ['id'];
		$focus = UserFocusModel::where ( "focus_uid", $id )->where ( 'uid', $uid )->first ();
		if ($focus) {
			$data = array (
				'result' => false,
				'message' => '您已经关注过该用户！'
			);
			return json_encode ( $data );
		}
		$data = array (
			'uid' => $uid,
			'focus_uid' => $id
		)
		;
		$insert_id = UserFocusModel::insertGetId ( $data );
		if ($insert_id > 0) {
			$data = array (
				'result' => true,
				'message' => '关注成功！'
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
	 * 取消关注
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function cancelfocus(Request $request) {
		$id = intval ( $_POST ['uid'] ); // 被关注者ID

		if (! $id) {
			$data = array (
				'result' => false,
				'message' => '关注失败！'
			);
			return json_encode ( $data );
		}
		// 关注用户ID
		$uid = $this->user ['id'];

		$result = UserFocusModel::where ( "focus_uid", $id )->where ( 'uid', $uid )->delete ();
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
	 * 我的收藏
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */

	public function myCollection(Request $request){

		$this->initTheme ( 'userindex' ); // 主题初始化
		$this->theme->setTitle ( '我的收藏' );

		$paginate = ($this->themeName = 'black') ? 18 : 12;
		$uid = $this->user ['id'];
		$user_data = UserModel::select ( 'user_detail.nickname', 'user_detail.avatar', 'user_detail.sex', 'user_detail.balance', 'user_detail.introduce', 'users.experience', 'users.con_login_day as loginDay' )->where ( 'users.id', $this->user ['id'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ()->toArray ();

		$collectList = ModelsCollectModel::select('models_content.*')->where('models_collect.uid', $uid )->join('models_content', 'models_content.id', '=', 'models_collect.models_id')->paginate ( $paginate );

		$view = [
			'list' => $collectList,
		];
		// 'ad'=>$ad,

		return $this->theme->scope ( 'user.collect', $view )->render ();

	}

	/**
	 * 上传作品封面
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function setModelCover(Request $request) {

		$file = $request->file('coverImg');

		$id = $_POST["editModelsId"];

		$model = ModelsContentModel::where('id', $id)->first();
		if($model){
			$coverImage = $model['cover_img'];
			$url = str_replace("cover.png","",$coverImage);
			$url = str_replace("cover.jpg","",$url);
			$extension = $file->getClientOriginalExtension ();
			$realName = 'upload_cover.' . $extension;
//			unlink("./".$url.$realName);
			if($file->move ( $url, $realName )){

				$data = [
					'upload_cover_image' => $url.$realName
				];
				$result = ModelsContentModel::where ( 'id', $id )->update ( $data );
//				if($result){
				$data = array (
					'result' => true,
					'url' => $url.$realName."?".rand(1000,9999),
				);
				return json_encode ( $data );
//				}

			}
			$data = array (
				'result' => false,
				'message' => '保存失败'
			);
			return json_encode ( $data );
		}
		$data = array (
			'result' => false,
			'message' => '保存失败'
		);
		return json_encode ( $data );
	}


}
