<?php
/**
 * ------------------------
 * Created by PhpStorm.
 * ------------------------
 *
 * ------------------------
 * Author: frank
 * Date: 16-4-19
 * Desc:
 * ------------------------
 *
 */

namespace App\Http\Controllers;

use App\Http\Controllers\AuthController;
use App\Http\Requests;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;

use App\Modules\User\Http\Controllers\WorkModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsCollectModel;
use App\Modules\User\Model\UserFocusModel;
use App\Modules\Manage\Model\UserLevelModel;
use App\Modules\User\Model\UserUrlModel;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Theme;


class DomainController extends IndexController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('common');

    }
    public function getModelsUserInfo(Request $request) {

        $this->initTheme ( 'ajaxpage' ); // 主题初始化

        $id = $_POST['mid'];
        $ModelsContentModel = ModelsContentModel::where ( 'id', $id )->first ();
        $goods = GoodsModel::where('mid',$id)->first();
        if($goods){
            $goods_id = $goods['id'];
            $goods_type = $goods['type'];
        }else{
            $goods_type = 0;
            $goods_id = 0;
        }

        if ($ModelsContentModel == null) {
            return redirect ( '/' );
        }


        $user_data = UserModel::select ( 'users.id','users.user_type','users.experience', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $ModelsContentModel ['uid'] )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ();
        $this->theme->setTitle ( $ModelsContentModel ['title'] );
        $tempArray = Array ();
        if ($ModelsContentModel ['paramaters'] != "") {
            $paramaters = $ModelsContentModel ['paramaters'];
//			$paramaters=str_replace( '&nbsp;','',$paramaters);
//			$paramaters=preg_replace('# #','',$paramaters);
            $tempArray = explode ( "|", $paramaters );
        }
        $isAuthor = false;

        $otherModelList = ModelsContentModel::where('uid',$ModelsContentModel ['uid'])->where('id','!=',$id)->where('is_private',0)->limit(6)->get();


        $userLevel = UserLevelModel::select ( 'name', 'min', 'max' )->where ( 'min', '<=',  $user_data['experience'] )->where ( 'max', '>=',  $user_data['experience'] )->first ()->toArray ();

        // 是否关注
        if ($this->user ['id'] > 0) {
            $isFocus = UserFocusModel::where ( 'focus_uid', $ModelsContentModel ['uid'] )->where ( 'uid', $this->user ['id'] )->first ();
            if ($isFocus) {
                $user_data ["isFocus"] = true;
            } else {
                $user_data ["isFocus"] = false;
            }
            $islogin = true;
            if($this->user ['id'] == $ModelsContentModel ['uid']){
                $isAuthor = true;
            }


        } else {
            $islogin = false;
        }
        // 点赞数
        $favoriteNum = ModelsFavoriteModel::where ( 'models_id', $id )->count ();
        // 是否点赞
        $isFavorite = ModelsFavoriteModel::where ( 'models_id', $id )->where ( 'uid', $this->user ['id'] )->first ();
        if ($isFavorite) {
            $user_data ["isFavorite"] = true;
        } else {
            $user_data ["isFavorite"] = false;
        }
        // 收藏数
        $collectionNum = ModelsCollectModel::where ( 'models_id', $id )->count ();
        // 是否点赞
        $isCollect = ModelsCollectModel::where ( 'models_id', $id )->where ( 'uid', $this->user ['id'] )->first ();
        if ($isCollect) {
            $user_data ["isCollect"] = true;
        } else {
            $user_data ["isCollect"] = false;
        }

        $remarkList = ModelsRemarkModel::select('user_detail.avatar','user_detail.nickname','models_remark.content','models_remark.id','models_remark.uid','models_remark.created_at')
            ->where ( 'models_remark.models_id', $id )
            ->where('models_remark.remark_id','=','models_remark.id')
            ->join("user_detail","user_detail.uid","=","models_remark.uid")->orderBy('models_remark.created_at','desc')->get();

        //$fans_num = UserFocusModel::where ( 'focus_uid',$ModelsContentModel ['uid'] )->count ();
        $fans_num = ModelsRemarkModel::where ( 'models_id',$id )->count ();
        //var_dump($fans_num );exit;
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
//		$fans_num = UserFocusModel::where ( 'focus_uid',$ModelsContentModel ['uid'] )->count ();



        $view = [
            'content'     => $ModelsContentModel,
            'userinfo'    => $user_data,
            'uid'         => $ModelsContentModel ['uid'],
            'isLogin'     => $islogin,
            'userLevel'   => $userLevel['name'],
            'favoriteNum' => $favoriteNum,
            'collectNum'  => $collectionNum,
            'lookNum'     => $ModelsContentModel['view_count'],
            'remarkList'  => $remarkList,
            'paramaters'  => $tempArray,
            'modelsId'    => $id,
            'fans'        => $fans_num,
            'goods_id'    => $goods_id,
            'goods'       => $goods,
            'goods_type'  => $goods_type,
            'otherModelList'=> $otherModelList,
            'isAuthor'    => $isAuthor,
            'remark'      => $remark,
            'time'		  => self::DateTimeDiff(date("Y-m-d H:i:s",mb_substr($ModelsContentModel["create_time"],0,10))),
            'enroll_status'=> $ModelsContentModel['enroll_status'],
        ];


        return $this->theme->scope ( 'models.models_right', $view )->render ();



    }
    public function zone(Request $request) {
        $url = $request->server("HTTP_HOST");
        $user_url = UserUrlModel::where(['url' => $url,'status' => 1])->first();
        $id = $user_url['uid']; //用户id
        $folder=0;
        $this->initTheme ( 'zoneDomain' ); // 主题初始化

        // 获取用户信息
        $userInfo = UserDetailModel::where ( 'uid', $id )->first ();

        $level=['一','二','三','四','五','六','七','八','九','十','十一'];
        //获取用户等级和经验值
        $user_data = UserModel::select ( 'users.id','users.experience', 'users.user_type','user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )->where ( 'users.id', $id )->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )->first ();
        $userLevel = UserLevelModel::select ('id', 'name', 'min', 'max' )->where ( 'min', '<=',  $user_data['experience'] )->where ( 'max', '>=',  $user_data['experience'] )->first ()->toArray ();
        $userLevel['pct']=($user_data['experience']-$userLevel['min'])/($userLevel['max']-$userLevel['min'])*100;
        $userLevel['level']=$level[$userLevel['id']-1];

        // 获取用户的文件夹
//		$folderList = ModelsFolderModel::select ( 'id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time' )->where ( 'uid', '=', $id )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();

        // 获取用户的所有作品
        $models = ModelsContentModel::select ( 'id', 'title', 'content', 'cover_img','upload_cover_image', 'create_time' ,'price')->where ( 'uid', '=', $id );

//		$folder = intval ($folder);
//		if($folder && $folder != 0){
//			$models = $models->where ('folder_id',$folder);
//		}

        $models = $models->where ( 'is_private', '=', 0 )->orderBy ( 'create_time', 'desc' )->get();

        $modelsCount = ModelsContentModel::select ( 'id', 'title', 'content', 'cover_img', 'create_time' )->where ( 'uid', '=', $id )->where ( 'is_private', '=', 0 )->count ();
        //收藏作品数量
        $modelsCollectCount = ModelsCollectModel::where('uid',$id)->count();
        // 关注数量
        $userFen = UserFocusModel::where ( 'focus_uid', $id )->count ();
        $userFocus = UserFocusModel::where ( 'uid', $id )->count ();

        $userInfo ["fen"] = $userFen;
        $userInfo ["focus"] = $userFocus;
        $view = [
            'user_data' => $userInfo,
            'models' => $models,
            'list' => $models,
            'uid' => $id,
            'modelsCount' => $modelsCount,
            'isLogin' => false,
            'folder'=>$folder,
            'userLevel'=>$userLevel,
            'modelsCollectCount'=>$modelsCollectCount,
            'user_type' => $user_data['user_type'],
        ];
        $this->theme->set ( 'TYPE', 1 );
        $this->theme->setTitle ( $userInfo['nickname'] );
        return $this->theme->scope ( 'user.zone.domainzoneindex', $view )->render ();
    }






    public function modelsView($id = 0,Request $request) {
       
		$url = $request->server("HTTP_HOST");
        $user_url = UserUrlModel::where(['url' => $url,'status' => 1])->first();
        $uid = $user_url['uid']; //用户id
		ModelsContentModel::ModelsIncrement($id);
		$ModelsContentModel = ModelsContentModel::getDataForId($id);

		if(empty($ModelsContentModel)||  $ModelsContentModel ['uid'] != $uid) return redirect ( '/' );

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
			'userData' => 4,
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




}