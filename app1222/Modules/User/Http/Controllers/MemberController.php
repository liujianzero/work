<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController as BasicUserCenterController;
use App\Http\Controllers\IndexController;
use App\Modules\Bre\Model\MoocOrderModel;
use App\Modules\Bre\Model\MoocPriceModel;
use App\Modules\Bre\Model\UserRandModel;
use App\Modules\User\Model\MemberAbstractModel;
use App\Modules\User\Model\UserTypeModel;
use Illuminate\Http\Request;
use App\Modules\User\Model\ModelsModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsVrContentModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserMemberModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\Order\Model\OrderModel;
use Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Omnipay;
use Gregwar\Image\Image;
// use Intervention\Image\ImageManager;
class MemberController extends IndexController {
	public function __construct() {
		parent::__construct ();
		$this->user = Auth::user ();
		parent::__construct();
		$this->initTheme('member');
	}


	/**
	 * 购买会员首页
	 * @return mixed
	 */

	public function index(){
		$this->theme->setTitle ( '购买会员' );
		$mFunctionData = MemberAbstractModel::findContent();
		if($this->user){
			$userData = UserModel::find($this->user['id']);
			$user_type = $userData['user_type'];
		}else{
			$user_type = 0;
		}
		$memberType = UserTypeModel::select('type','storage','price','outside','cdn','user_type.id','is_download','shop_power.shop_num','shop_power.Renovation','shop_power.recommend','shop_power.url','shop_other.account','shop_other.discount','shop_other.red_packet','shop_other.service')
			->join('shop_power','shop_power.user_type_id','=','user_type.type_id')
			->join('shop_other','shop_other.user_type_id','=','user_type.type_id')
			->get();
		$memberList = UserMemberModel::where("status",1)->paginate (10);

		$buylist = OrderModel::select('user_detail.nickname','user_detail.avatar','order.created_at')
			->where("order.title","购买会员")
			->where("order.status",'=',1)
			->join("user_detail",'user_detail.uid','=','order.uid')
			->orderBy('order.created_at','desc')
			->limit(12)->get();


		$total = OrderModel::where("order.title","购买会员")->count();

		$data = array(
			"list"       => $memberList,
			'buylist'    => $buylist,
			'total'      => $total,
			'memberType' => $memberType,
			'user_type'  => $user_type,
			'mFunData'   => $mFunctionData,
		);
		return $this->theme->scope('member.index',$data)->render();

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

	/**
	 * 详情
	 * @return mixed
	 */

	public function info(){

		$memberList = UserMemberModel::where("status",1)->paginate (10);


		$data = array(
			"list"=>$memberList
		);

		return $this->theme->scope('member.info',$data)->render();

	}


	/**
	 * 选择套餐和购买时间
	 * @return mixed
	 */
	public function select(){


		$data = array(

		);

		return $this->theme->scope('member.select',$data)->render();

	}



	public function bountys($id=0){
		return view('welcome');
	}


	/**
	 * Use:购买支付页面
	 * @param $id
	 * @param $type
	 * @param $typeId
	 * @return mixed
	 */
	public function bounty( $id , $type , $typeId ){
		$payTypeData = UserMemberModel::getPayTypeData( $id , $type , $typeId , $this->user['id'] );
		$this->theme->setTitle ( $payTypeData['title'] );
		if(!$payTypeData['model']){
			$view = [
				'error' => $payTypeData['message'],
			];
			return $this->theme->scope('member.info',$view)->render();
		}
		$data = array(
			"balance" => $payTypeData['balance'],
			"model"   => $payTypeData['model'],
			"type"    => $payTypeData['type'],
			"typeId"  => $typeId,
		);
		return $this->theme->scope('member.pay',$data)->render();
	}


	/**
	 * 支付
	 * @return mixed
	 */
	public function bountyUpdate(Request $request){
		$data = $request->except('_token');
		//创建支付需要的数据
		$payData = UserMemberModel::createPayData( $data['id'], $data['type'], $data['type_id'], $this->user['id'] );
		//创建订单
		$is_ordered = OrderModel::memberOrder( Auth::user()['id'], $payData['price'], $data['id'], $payData['title'], $data['type'] );
		if($payData['balance'] >= $payData['price'] && $data['pay_canel'] == 0){ //余额支付
			$password = UserModel::encryptPassword($data['password'], Auth::user()['salt']);
			if ($password != Auth::user()['alternate_password']){ //验证用户的密码是否正确
				return redirect()->back()->with(['error' => '您的支付密码不正确']);
			}
			//支付产生订单
			$res = UserMemberModel::createOrder( $data['type'], $data['id'], Auth::user()['id'], $payData['price'], $payData['title'], $is_ordered->code,intval($data['type_id']));
			if($res){
				return  self::getReturnPage($data['type'],intval($data['type_id']),$data['id']);
			}
			return redirect()->back()->with(['message' => '支付失败！']);

		}else if (isset($data['pay_type']) && $data['pay_canel'] == 1) {
			//跳转支付赏金托管流程,返回成功之后就直接执行上面的托管
			if ($data['pay_type'] == 1) {//支付宝支付
				$config = ConfigModel::getPayConfig('alipay');
				$objOminipay = Omnipay::gateway('alipay');
				$objOminipay->setPartner($config['partner']);
				$objOminipay->setKey($config['key']);
				$objOminipay->setSellerEmail($config['sellerEmail']);
				$objOminipay->setReturnUrl(env('ALIPAY_RETURN_URL', url('/order/pay/alipay/return')));
				$objOminipay->setNotifyUrl(env('ALIPAY_NOTIFY_URL', url('/order/pay/alipay/notify')));
				$response = Omnipay::purchase([
					'out_trade_no' => $is_ordered->code, //your site trade no, unique
					'subject' => \CommonClass::getConfig('site_name') . $payData['title'], //order title
					'total_fee' => $is_ordered->cash, //order total fee
				])->send();
				return \CommonClass::formatResponse('确认充值', 200, array('url' => $response->getRedirectUrl(), 'orderCode' => Crypt::encrypt($is_ordered->code)));
			} else if ($data['pay_type'] == 2) {//微信支付
				return \CommonClass::formatResponse('确认充值', 200, array('url' => '/finance/wechatPay/' . Crypt::encrypt($is_ordered), 'orderCode' => Crypt::encrypt($is_ordered->code)));
			} else if ($data['pay_type'] == 3) {
				dd('银联支付！');
			}
		} else if (isset($data['account']) && $data['pay_canel'] == 2) {//银行卡支付
			dd('银行卡支付！');
		} else//如果没有选择其他的支付方式
		{
			return redirect()->back()->with(['error' => '请选择一种支付方式']);
		}
		return $this->theme->scope('member.pay',$data)->render();



//		$is_ordered = OrderModel::memberOrder(Auth::user()['id'], $memberPackeg['price'], $data['id'],$title);
//		//余额支付
//		if ($balance >= $memberPackeg['price'] && $data['pay_canel'] == 0)
//		{
//			//验证用户的密码是否正确
//			$password = UserModel::encryptPassword($data['password'], Auth::user()['salt']);
//			if ($password != Auth::user()['alternate_password'])
//			{
//				return redirect()->back()->with(['error' => '您的支付密码不正确']);
//			}
//			//支付产生订单
//			if($type == 'mooc'){
//				$res = UserMemberModel::moocBounty($memberPackeg['price'],$randNum,Auth::user()['id'], $is_ordered->code);
//			}else{
//				$res = UserMemberModel::memberBounty($memberPackeg['price'],$memberPackeg['month_number'],Auth::user()['id'], $is_ordered->code,8);
//			}
//			if($res){
//				return redirect()->back()->with(['message' => '支付成功!']);
//			}
//			return redirect()->back()->with(['message' => '支付失败！']);
//		}else if (isset($data['pay_type']) && $data['pay_canel'] == 1) {
//			//跳转支付赏金托管流程,返回成功之后就直接执行上面的托管
//			if ($data['pay_type'] == 1) {//支付宝支付
//				$config = ConfigModel::getPayConfig('alipay');
//				$objOminipay = Omnipay::gateway('alipay');
//				$objOminipay->setPartner($config['partner']);
//				$objOminipay->setKey($config['key']);
//				$objOminipay->setSellerEmail($config['sellerEmail']);
//				$objOminipay->setReturnUrl(env('ALIPAY_RETURN_URL', url('/order/pay/alipay/return')));
//				$objOminipay->setNotifyUrl(env('ALIPAY_NOTIFY_URL', url('/order/pay/alipay/notify')));
//
//				$response = Omnipay::purchase([
//					'out_trade_no' => $is_ordered->code, //your site trade no, unique
//					'subject' => \CommonClass::getConfig('site_name') . $title, //order title
//					'total_fee' => $is_ordered->cash, //order total fee
//				])->send();
//				return \CommonClass::formatResponse('确认充值', 200, array('url' => $response->getRedirectUrl(), 'orderCode' => Crypt::encrypt($is_ordered->code)));
//				break;
//			} else if ($data['pay_type'] == 2) {//微信支付
//				return \CommonClass::formatResponse('确认充值', 200, array('url' => '/finance/wechatPay/' . Crypt::encrypt($is_ordered), 'orderCode' => Crypt::encrypt($is_ordered->code)));
//				break;
//
//			} else if ($data['pay_type'] == 3) {
//				dd('银联支付！');
//			}
//		} else if (isset($data['account']) && $data['pay_canel'] == 2) {//银行卡支付
//			dd('银行卡支付！');
//		} else//如果没有选择其他的支付方式
//		{
//			return redirect()->back()->with(['error' => '请选择一种支付方式']);
//		}
//		return $this->theme->scope('member.pay',$data)->render();
	}

	/**
	 * Use:支付完成跳转地址
	 * @param $type
	 * @param $typeId
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	static function getReturnPage($type,$typeId,$id){
		if($type == 'goods'){
			if($typeId == 2){
				return redirect()->route('myOrder.myViewOut')->with(['message' => '支付成功!']);
			}elseif($typeId == 3){
				return redirect()->route('myOrder.myMaterialOut')->with(['message' => '支付成功!']);
			}elseif($typeId == 4){
				return redirect()->route('myOrder.myTaskOut')->with(['message' => '支付成功!']);
			}
		}elseif($type == 'study'){
			return redirect()->route('video',$id)->with(['message' => '支付成功!']);
		}else{
			return redirect()->route('indexPage')->with(['message' => '支付成功!']);
		}
	}
}
