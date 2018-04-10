<?php
namespace App\Modules\User\Model;

use App\Modules\Manage\Model\UserLevelModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModelsContentModel extends Model
{

    protected $table = 'models_content';
    protected $primaryKey = 'id';
   
    protected $fillable = [
        'id','title', 'content', 'view_count', 'cover_id','models_type', 'models_id','folder_id', 'uid', 'reply_count', 'create_time',
    	'update_time', 'status', 'jsfile', 'sourcefile', 'is_download', 'is_private', 'cover_img','upload_cover_image',
    	'price', 'license','scene', 'sceneGlobal', 'sceneBG', 'baseData', 'animationData',
    	'collect', 'share', 'is_share', 'is_print', 'sort','sort_index', 'tblink','paramaters','is_goods','view_price', 'view_mode',
        'goods_type_id', 'goods_number', 'is_on_sale', 'goods_cat_id', 'old_uid'
    ];

    public $timestamps = false;

    /**
     * 获取所有订单商品。
     */
    public function goods()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderGoodsModel', 'goods_id', 'id');
    }

    /**
     * 获取所有付费查看。
     */
    public function views()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderViewModel', 'models_id', 'id');
    }

    /**
     * 获取所有购买素材。
     */
    public function materials()
    {
        return $this->hasMany('App\Modules\User\Model\ModelsOrderMaterialModel', 'models_id', 'id');
    }

    /**
     * 获取相应的属性类型。
     */
    public function type()
    {
        return $this->belongsTo('App\Modules\User\Model\GoodsType');
    }

    //更改文件夹及其下面模型的公开状态
    static function updatePrivate($data)
    {
    	$status = DB::transaction(function () use ($data){
    		
    		//修改文件夹的公开状态
    		ModelsFolderModel::where ( 'id', $data['id'] )->update ( [
    		'auth_type' => $data['auth_type'],
    		] );
    		
    		ModelsContentModel::where ( 'folder_id', $data['id'] )->update ( [
    		'is_private' => $data['auth_type'],
    		] );
    		
    	
    	});
    	return is_null($status) ? true : false;
    }
    
	static function getGoodsType($typeId, $model){
		if( $typeId == 1 ){
			$type = '购买商品';
		}elseif( $typeId == 2  ){
			if($model->view){
				$type = '购买付费_' . $model->view->goods->title;
			}else{
				abort(404);
			}

		}elseif( $typeId == 3 ){
			if ($model->material) {
				$type = '购买素材_' . $model->material->goods->title;
			} else {
				abort(404);
			}

		}elseif( $typeId == 4 ){
			if($model->service){
				$type = '购买服务_' . $model->service->goods->goods_name;
		}else{
				abort(404);
			}
		}else{
			abort(404);
		}
		return $type;
	}
    
	static function ModelsIncrement($id){
		return ModelsContentModel::where( 'id', $id )->increment( 'view_count');
	}

	static function getDataForId($id){
		return ModelsContentModel::find($id);
	}

	static function ifModelsIsGoods($ModelsContentModel){
		$uid = Auth::user()->id;
		if ($uid != $ModelsContentModel->uid) {// 【作者本人则放行】-【非作者则进行验证】
			$viewPay = ModelsOrderViewModel::where('user_id', $uid)
				->where('models_id', $ModelsContentModel->id)->latest()->first();//只存在一条
			$status = false;
			if (!$viewPay) {// 无购买记录
				$status = true;
			} else {// 有购买记录
				if ($viewPay->permanent == 'N') {// 非永久
					$time = date('Y-m-d H:i:s');
					if ($viewPay->expiration_date) {// 月付
						if ($time > $viewPay->expiration_date) {
							if ($viewPay->times > 0) {//次付
								$status = false;
							} else {
								$status = true;
							}
						} else {
							$status = false;
						}
					} else {
						if ($viewPay->times > 0) {//次付
							$status = false;
						} else {
							$status = true;
						}
					}
				} else {
					$status = false;
				}
			}
		}
		return $status;
	}

	static function ifModelsIsGoodsDecrement( $ModelsContentModel ){
		$uid = Auth::user()->id;
		if ($uid != $ModelsContentModel->uid) {// 【作者本人则放行】-【非作者则进行验证】
			$viewPay = ModelsOrderViewModel::where('user_id', $uid)
				->where('models_id', $ModelsContentModel->id)->latest()->first();//只存在一条
			$status = false;
			if (!$viewPay) {// 无购买记录
				$status = true;
			} else {// 有购买记录
				if ($viewPay->permanent == 'N') {// 非永久
					$time = date('Y-m-d H:i:s');
					if ($viewPay->expiration_date) {// 月付
						if ($time > $viewPay->expiration_date) {
							if ($viewPay->times > 0) {//次付
								ModelsOrderViewModel::where('user_id', $uid)
									->where('models_id', $ModelsContentModel->id)
									->decrement('times');
								$status = false;
							} else {
								$status = true;
							}
						} else {
							$status = false;
						}
					} else {
						if ($viewPay->times > 0) {//次付
							ModelsOrderViewModel::where('user_id', $uid)
								->where('models_id', $ModelsContentModel->id)
								->decrement('times');
							$status = false;
						} else {
							$status = true;
						}
					}
				} else {
					$status = false;
				}
			}
		}
		return $status;
	}

	static function getModelsUserType($id){
		 return UserModel::where ( 'users.id', $id )->value('user_type');
	}

	static function getUserAndUserDetailData($uid){
		$data = UserModel::select ( 'users.id','users.user_type','users.experience', 'user_detail.avatar', 'user_detail.balance', 'user_detail.nickname', 'user_detail.introduce' )
			->where ( 'users.id', $uid )
			->join ( 'user_detail', 'users.id', '=', 'user_detail.uid' )
			->first ();

		if(empty($data['avatar']))
			$data['avatar'] = 'themes/default/assets/images/default_avatar.png';

		if($data['introduce']){
			$data['introduce'] = mb_strimwidth($data['introduce'],0,30,'..');
		}else{
			$data['introduce'] = '这家伙很懒，什么都没有留下';
		}

		if($data['user_type'] ==1 || $data['user_type'] ==2 ){
			$data['vip'] = 'themes/default/assets/images/vip.png';
		}elseif($data['user_type'] ==3 || $data['user_type'] ==4 ){
			$data['vip'] = 'themes/default/assets/images/vipqi.png';
		}else{
			$data['vip'] = '';
		}

		return $data;
	}

	static function getUserLevel($experience){
		return UserLevelModel::where( 'min', '<=', $experience )->where( 'max', '>=', $experience )->value('name');
	}

	static function getOtherModelList($uid,$id){
		return ModelsContentModel::where('uid',$uid)->where('id','!=',$id)->where('is_private',0)->limit(6)->get();
	}

	static function getFavoriteNum($id){
		return ModelsFavoriteModel::where( 'models_id', $id )->count();
	}
	static function ifIsFavorite($id,$uid){
		if ( ModelsFavoriteModel::where( 'models_id', $id )->where( 'uid', $uid )->first() ) {
			$status = true;
		} else {
			$status = false;
		}
		return $status;
	}

	static function getCollectionNum($id){
		return ModelsCollectModel::where( 'models_id', $id )->count();
	}

	static function ifIsCollect($id,$uid){
		if ( ModelsCollectModel::where ( 'models_id', $id )->where( 'uid', $uid )->first() ) {
			$status = true;
		} else {
			$status = false;
		}
		return $status;
	}

	static function getModelsCommentNum($id){
		return ModelsRemarkModel::where( 'models_id',$id )->count();
	}

	static function ifIsFocus($id,$uid){
		if ( UserFocusModel::where( 'focus_uid', $id )->where ( 'uid', $uid )->first() ) {
			$status = true;
		} else {
			$status = false;
		}
		return $status;
	}

	static function getOrderView($ModelsContentModel){
		if (Auth::check()) {
			$uid = Auth::user()->id;
			if ($ModelsContentModel->uid == $uid) {//作者本人
				switch ($ModelsContentModel->transaction_mode) {
					case 1://出售商品
						$web = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');">我的商品</a>';
						$wap = '<a class="purchase-btn" href="#">我的商品</a>';
						break;
					case 2://查看付费
						$web = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #006AD5;">我的付费</a>';
						$wap = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #006AD5;border: none;">我的付费</a>';
						break;
					case 3://出售素材
						$web = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #0AB85D;">我的素材</a>';
						$wap = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #0AB85D;border: none;">我的素材</a>';
						break;
					case 4://定制服务
						$web = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #F19C00;">我的服务</a>';
						$wap = '<a class="purchase-btn" href="javascript:layer.msg(\'为了保障恶意刷单，您不可以购买自己的商品\');" style="background: #F19C00;border: none;">我的服务</a>';
						break;
					default://普通作品
						$web = '<a class="purchase-btn" href="javascript:layer.msg(\'这是您自己的作品\');">我的作品</a>';
						$wap = '<a class="purchase-btn" href="javascript:layer.msg(\'这是您自己的作品\');">我的作品</a>';
						break;
				}
			} else {
				switch ($ModelsContentModel->transaction_mode) {
					case 1://出售商品
                        $web = '<a class="purchase-btn" href="'
                            . route('goods.info.web', ['id' => $ModelsContentModel->id])
                            . '">购买商品</a>';
                        $wap = '<a class="purchase-btn">购买商品</a>';
						break;
					case 2://查看付费
						$viewPay = ModelsOrderViewModel::where('user_id', $uid)
							->where('models_id', $ModelsContentModel->id)
							->latest()
							->first();
						if ($viewPay) {
							if ($viewPay->permanent == 'N') {
								$web = '<a class="purchase-btn" href="'
									. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
									.'" style="background: #006AD5;">续费</a>';
								$wap = '<a class="purchase-btn" href="'
									. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
									. '" style="background: #006AD5;border: none;">续费</a>';
							} else {
								$web = '<a class="purchase-btn" href="javascript:layer.msg(\'您当前具备永久查看权限\');" style="background: #006AD5;">永久查看</a>';
								$wap = '<a class="purchase-btn" href="javascript:layer.msg(\'您当前具备永久查看权限\');" style="background: #006AD5;border: none;">永久查看</a>';
							}
						} else {
							$web = '<a class="purchase-btn" href="'
								. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
								.'" style="background: #006AD5;">付费查看</a>';
							$wap = '<a class="purchase-btn" href="'
								. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
								. '" style="background: #006AD5;border: none;">付费查看</a>';
						}
						break;
					case 3://出售素材
						$material = ModelsOrderMaterialModel::where('user_id', $uid)
							->where('models_id', $ModelsContentModel->id)
							->latest()
							->first();
						if ($material && $material->auth == 'Y') {
							$web = '<a class="purchase-btn" href="'
								. route('myOrder.materialDownload', ['id' => $material->models_id])
								. '" style="background: #0AB85D;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
							$wap = '<a class="purchase-btn" href="'
								. route('myOrder.materialDownload', ['id' => $material->models_id])
								. '" style="background: #0AB85D;border: none;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
						} else {
							if ($ModelsContentModel->price == '0.00') {
								$web = '<a class="purchase-btn" href="'
									. route('myOrder.materialDownload', ['id' => $ModelsContentModel->id])
									. '" style="background: #0AB85D;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
								$wap = '<a class="purchase-btn" href="'
									. route('myOrder.materialDownload', ['id' => $ModelsContentModel->id])
									. '" style="background: #0AB85D;border: none;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
							} else {
							$web = '<a class="purchase-btn" href="'
								. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
								. '" style="background: #0AB85D;">购买素材</a>';
							$wap = '<a class="purchase-btn" href="'
								. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
								. '" style="background: #0AB85D;border: none;">购买素材</a>';
						}
						}
//						if ($material && $material->auth == 'Y') {
//							$web = '<a class="purchase-btn" href="'
//								. route('myOrder.materialDownload', ['id' => $material->models_id])
//								. '" style="background: #0AB85D;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
//							$wap = '<a class="purchase-btn" href="'
//								. route('myOrder.materialDownload', ['id' => $material->models_id])
//								. '" style="background: #0AB85D;border: none;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
//						} else {
//							$web = '<a class="purchase-btn" href="'
//								. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
//								. '" style="background: #0AB85D;">购买素材</a>';
//							$wap = '<a class="purchase-btn" href="'
//								. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
//								. '" style="background: #0AB85D;border: none;">购买素材</a>';
//						}
						break;
					case 4://定制服务
						$web = '<a class="purchase-btn" href="'
							. route('myOrder.myTaskOutBuy', ['id' => $ModelsContentModel->id])
							. '" style="background: #F19C00;">定制服务</a>';
						$wap = '<a class="purchase-btn" href="'
							. route('myOrder.myTaskOutBuy', ['id' => $ModelsContentModel->id])
							. '" style="background: #F19C00;border: none;">定制服务</a>';
						break;
					default://普通作品
						$web = '<a class="purchase-btn">请您欣赏</a>';
						$wap = '<a class="purchase-btn">请您欣赏</a>';
						break;
				}
			}
		} else {
			switch ($ModelsContentModel->transaction_mode) {
				case 1://出售商品
                    $web = '<a class="purchase-btn" href="'
                        . route('goods.info.web', ['id' => $ModelsContentModel->id])
                        . '">购买商品</a>';
                    $wap = '<a class="purchase-btn">购买商品</a>';
					break;
				case 2://查看付费
					$web = '<a class="purchase-btn" href="'
						. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #006AD5;">付费查看</a>';
					$wap = '<a class="purchase-btn" href="'
						. route('myOrder.viewPayBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #006AD5;border: none;">付费查看</a>';
					break;
				case 3://出售素材
					if($ModelsContentModel->price == '0.00'){
						$web = '<a class="purchase-btn" href="'
							. route('myOrder.materialDownload', ['id' => $ModelsContentModel->id])
							. '" style="background: #0AB85D;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
						$wap = '<a class="purchase-btn" href="'
							. route('myOrder.materialDownload', ['id' => $ModelsContentModel->id])
							. '" style="background: #0AB85D;border: none;"><i class="fa fa-cloud-download"></i> 下载素材</a>';
					}else{
					$web = '<a class="purchase-btn" href="'
						. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #0AB85D;">购买素材</a>';
					$wap = '<a class="purchase-btn" href="'
						. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #0AB85D;border: none;">购买素材</a>';
					}
//					$web = '<a class="purchase-btn" href="'
//						. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
//						. '" style="background: #0AB85D;">购买素材</a>';
//					$wap = '<a class="purchase-btn" href="'
//						. route('myOrder.materialBuy', ['id' => $ModelsContentModel->id])
//						. '" style="background: #0AB85D;border: none;">购买素材</a>';
					break;
				case 4://定制服务
					$web = '<a class="purchase-btn" href="'
						. route('myOrder.myTaskOutBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #F19C00;">定制服务</a>';
					$wap = '<a class="purchase-btn" href="'
						. route('myOrder.myTaskOutBuy', ['id' => $ModelsContentModel->id])
						. '" style="background: #F19C00;border: none;">定制服务</a>';
					break;
				default://普通作品
					$web = '<a class="purchase-btn">请您欣赏</a>';
					$wap = '<a class="purchase-btn">请您欣赏</a>';
					break;
			}
		}
		$data = [
			'web' => $web,
			'wap' => $wap,
		];

		return $data;
	}
}