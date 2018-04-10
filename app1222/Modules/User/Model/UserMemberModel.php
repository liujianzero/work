<?php

namespace App\Modules\User\Model;

use App\Modules\Bre\Model\MoocOrderModel;
use App\Modules\Bre\Model\MoocPriceModel;
use App\Modules\Bre\Model\UserRandModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Order\Model\OrderKickBackModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Shop\Models\ShopOtherModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Log;

class UserMemberModel extends Model
{
    protected $table = 'user_member';

    public $timestamps = false;

    protected $fillable = [
        'name', 'price','month_number','remark','status'
    ];


    /**
     * Use: 根据类型获取支付类型和用户账户余额
     * @param int $id
     * @param null $type
     * @param int $typeId
     * @param null $userId
     * @return array
     */
    static function getPayTypeData( $id = 0 , $type = null , $typeId = 0 , $userId = null ){
//        var_dump($typeId);
//        exit;
        $is_buy = OrderModel::getUserIsBuy( $id,$userId,$type,1);
        if($type == 'member'){  //会员类 1,会员只高不低
            $userData    = UserModel::getUserData($userId);
            if($userData['user_type'] >= intval($id) ){
                $message = '必须购买高于当前会员状态的会员类型';
                $id = 0;
            }else{
                $message = '会员参数出现错误';
            }
            $model = UserTypeModel::getUserTypeData($id);
            $title = '购买会员_'.$model['type'];
            $expireDate = strtotime($userData['member_expire_date']);
            if($expireDate && $expireDate > time()){
                $title = '续费会员_'.$model['type'];
            }

        }elseif($type == 'study'){ //课程类 1,购买与否
            if(!empty($is_buy)){
                $message = '您已经购买过此课程，无需重复购买！！！';
                $id = 0;
            }else{
                $message = '课程参数出现错误';
            }
            $model   = MoocPriceModel::where("id",intval($id))->first();
            $title   = '购买课程_'.$model['type'];
            $ranNum  = UserRandModel::where('uid',$userId)->get()->toArray();
            $num = null;
            foreach($ranNum as $v) $num += $v['randnum'];

        }elseif($type == 'goods'){ //商品类 1,购买与否 2,是否商品
            if( intval($typeId) == 0 || !empty($is_buy) && $typeId != 4){
                $message = '作品参数出现错误或已购买过此作品';
                $model = null;
            }else{
                $model = ModelsOrderModel::where(["id" => intval($id),'user_id' => intval($userId)])->first();
                $model['price'] = $model->total_price - $model->paid_price;
                $title = ModelsContentModel::getGoodsType(intval($typeId), $model);
            }
        }elseif($type == 'capacity'){ //容量类
            $message = '容量参数出现错误';
            $model   = UserCapacityModel::getAppointID($id);
            $title   = '升级容量_'.$model['capacity'];

        }elseif($type == 'recharge'){ //余额充值
            echo '正在开发中';exit;
        }elseif($type == 'withdrawals'){ //余额提现
            echo '正在开发中';exit;
        }else{
            $model = null;
        }

        $user_data = UserDetailModel::getUserBalance( $userId );
        $data = [
            'model'   => $model,
            'type'    => $type,
            'title'   => isset($title) ? $title : '您访问的页面找不到',
            'message' => isset($message) ? $message : '参数错误',
            'balance' => floatval($user_data) + ( isset($num) ? $num : 0.00 ),
        ];
        return $data;
    }



    /**
     * Use:根据类型获取支付价格和用户账户余额和标题
     * @param int $id
     * @param null $type
     * @param int $typeId
     * @param null $userId
     * @return array
     */
    static function createPayData( $id = 0 , $type = null , $typeId = 0 , $userId = null ){
        $userBalance  = UserDetailModel::getUserBalance( $userId );
        if($type == 'member'){  //会员类
            $packageData = UserTypeModel::getUserTypeData($id);  //查询套餐数据
            $price       = floatval($packageData['price']);
            $title       = '购买会员_'.$packageData['type'];       //标题
            $userData    = UserModel::getUserData($userId);
            $expireDate  = strtotime($userData['member_expire_date']);
            if($expireDate && $expireDate > time()){
                $title   = '续费会员_'.$packageData['type'];
            }

        }elseif($type == 'study'){ //课程类 打折
            $packageData = MoocPriceModel::where("id",intval($id))->first();
            $discount    = ShopOtherModel::where('user_type_id',Auth::user()->user_type)->value('discount');
            $price       = floatval($packageData['price']);
            $title       = '购买课程_'.$packageData['type'];
            $ranNum      = UserRandModel::where('uid',$userId)->get()->toArray();  //用户的红包
            $num = null;
            foreach($ranNum as $v) $num += $v['randnum'];
            $userBalance = floatval($userBalance) + $num;
            if(empty($num) && Auth::user()->user_type > 0){
                $price   = floatval($packageData['price']) * floatval($discount) / 10;
            }

        }elseif($type == 'goods'){ //商品类 1,购买与否 2,是否商品
            $model = ModelsOrderModel::where(["id" => intval($id),'user_id' => intval($userId)])->first();
            $price = $model->total_price - $model->paid_price;
            $title = ModelsContentModel::getGoodsType(intval($typeId), $model);

        }elseif($type == 'capacity'){ //容量类
            $packageData = UserCapacityModel::getAppointID($id);
            $price       = floatval($packageData['price']);
            $title       = '升级容量_'.$packageData['capacity'];

        }else{
            $price       = '1000000000';
            $title       = '购买其它服务';
        }

        $data = [
            'price'   => $price,
            'title'   => isset($title) ? $title : '您访问的页面找不到',
            'balance' => floatval($userBalance),
        ];
        return $data;
    }




    /**
     * Use:创建财务纪录和处理订单状态
     * @param $type
     * @param $typeId
     * @param $uid
     * @param $money
     * @param $title
     * @param $code
     * @param $goodsTypeId
     * @return bool
     */
    static function createOrder($type,$typeId,$uid,$money,$title,$code,$goodsTypeId){
        $status = DB::transaction(function() use($type,$typeId,$uid,$money,$title,$code,$goodsTypeId){
            if($type == 'member'){  //会员类
                //扣除用户的余额
                UserDetailModel::where('uid', $uid)->where('balance_status','!=', 1)->decrement('balance',$money);
                $expireDate = strtotime(Auth::user()->member_expire_date);
                $nowTime    = time();
                if($expireDate && $expireDate > $nowTime){ //续费会员
                    $userType  = Auth::user()->user_type;  //当前会员类型  $typeId当前需要升级的会员类型
                    if($userType == (intval($typeId) - 1)){ //一样
                        $FinalDate = date('Y-m-d H:i:s',$expireDate + 3600*24*365); //最终的到期时间
                        UserModel::where('id',$uid)->update(['user_type'=>$userType,'member_expire_date'=>$FinalDate]);
                    }else{
                        $getUpgradeTime = UserTypeModel::getUpgradeTime($userType,$typeId);
                        UserModel::where('id',$uid)->update(['user_type' => intval($typeId) - 1,'member_expire_date'=>$getUpgradeTime]);
                    }
                }else{ //开通会员
                    $openTime = date('Y-m-d H:i:s',$nowTime + 3600*24*365);
                    $user_type = intval($typeId) - 1;
                    UserModel::where('id',$uid)->update(['user_type' => $user_type,'member_expire_date'=> $openTime]);
                    $red_packet = UserRandModel::getRandNumForUserType($typeId);
                    UserRandModel::createRandNum($uid,$red_packet,1);
                    FinancialModel::createOneRecord(13,$red_packet,$uid,'开通会员奖励红包');
                }
                $action = 1;
            }elseif($type == 'study'){ //课程类 打折
                $randNum = UserRandModel::getRandNumForUid($uid);
                $allRandNum = $randNum['userRand'] + $randNum['testRand'];
                if($money <= $allRandNum){
                    $action = 8;
                    if($money <= $randNum['userRand']){
                        $title = '会员红包购买课程';
                        //扣除开通会员赠送红包金额
                        UserRandModel::where('uid','=', $uid)->where('type',1)->decrement('randnum', $money);
                    }else{
                        $title = '学前红包购买课程';
                        if($money <= $randNum['testRand']){
                            //扣除测一测红包金额
                            UserRandModel::where('uid','=', $uid)->where('type',2)->decrement('randnum', $money);
                        }else{
                            $surplusMoney = $money - $randNum['userRand']; //计算红包以外的金额
                            //扣除开通会员赠送红包金额和测一测红包金额
                            UserRandModel::where('uid','=', $uid)->where('type',1)->decrement('randnum', $randNum['userRand']);
                            UserRandModel::where('uid','=', $uid)->where('type',2)->decrement('randnum', $surplusMoney);
                            //生成财务记录
                            FinancialModel::createOneRecord($action,$randNum['userRand'],$uid,'会员红包购买课程');
                            $money = $surplusMoney;
                        }
                    }
                }else{
                    if($randNum['userRand']){
                        UserRandModel::where('uid','=', $uid)->where('type',1)->decrement('randnum', $randNum['userRand']);
                        //生成会员红包财务记录
                        FinancialModel::createOneRecord(8,$randNum['userRand'],$uid,'会员红包购买课程');
                    }

                    if($randNum['testRand']){
                        UserRandModel::where('uid','=', $uid)->where('type',2)->decrement('randnum', $randNum['testRand']);
                        FinancialModel::createOneRecord(8,$randNum['testRand'],$uid,'学前红包购买课程');
                    }
                    $action = 2;
                    $surplusMoney = $money - $randNum['userRand'] - $randNum['testRand'];
                    UserDetailModel::where('uid','=', $uid)->where('balance_status', '!=', 1)->decrement('balance', $surplusMoney);
                    $money = $surplusMoney;
                }
            }elseif($type == 'goods'){ //商品类 1,购买商品 2,付费查看 3,购买素材 4,购买服务
                $info   =  ModelsOrderModel::find(intval($typeId));
                $price  = $info->total_price - $info->paid_price;//要支付的钱
                $time   = date('Y-m-d H:i:s');
                $action = 4;
                if(!$info || $price <= 0.00 || $price != $money){
                    abort(404);
                }

                switch($goodsTypeId){
                    case 1:
                        var_dump('购买商品');exit;
                        break;
                    case 2:
                        $title  = $title.$info->type;
                        UserMemberModel::getGoodsBuyLook($info,$price,$money,$time,$uid);
                        break;
                    case 3:
                        UserMemberModel::getGoodsBuyMaterial($info,$price,$money,$time,$uid);
                        break;
                    case 4:
                        UserMemberModel::getGoodsBuyTask($info,$price,$money,$time,$uid);
                        break;
                    default:
                        exit('参数有误');
                        break;
                }

                if($goodsTypeId == 2 || $goodsTypeId == 3){
                    $kickback = OrderKickBackModel::getOrderKickback('goods',$info->transaction_mode,$info->type);
                    $kickbackMoney = round($money*$kickback,2);
                    if($kickbackMoney > '0.1'){
                        UserDetailModel::where('uid', $info->shop_id)->increment('balance',$kickbackMoney);
                        FinancialModel::createOneRecord(15,$kickbackMoney,$info->shop_id,$title);
                    }
                }
            }elseif($type == 'capacity'){ //容量类
                $action = 3;
                UserDetailModel::where('uid', $uid)->where('balance_status','!=', 1)->decrement('balance',$money);
            }else{
                echo '开发中';exit;
            }
            //生成财务记录
            FinancialModel::createOneRecord($action,$money,$uid,$title);
            //修改订单状态
            OrderModel::where('code', $code)->update(['status' => 1]);
        });
        return is_null($status)?true:false;
    }

    /**
     * Use:处理查看付费各种模式的数据
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    static function getGoodsBuyLook($info,$price,$money,$time,$uid){
        if($price == $money && $price > 0.00) {
            $order = [
                'pay_status'      => 2,
                'pay_at'          => $time,
                'paid_price'      => $price,
                'payment_details' => ModelsOrderModel::serializePaymentDetails($info,$price,$time)
            ];
            switch($info->type){
                case 'once':
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->increment('times');
                    break;
                case 'month':
                    $view = ['expiration_date' => ModelsOrderModel::getGoodsMouthTime($info,$time)];
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->update($view);
                    break;
                case 'permanent':
                    ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
                    ModelsOrderViewModel::where('user_id', $uid)->where('id', $info->view->id)->update(['permanent' => 'Y']);
                    break;
                default:
                    return redirect('/');
                    break;
            }
            UserDetailModel::where('uid', $uid)->where('balance_status','!=', 1)->decrement('balance',$money);
        }else{
            abort(404);
        }
    }

    /**
     * Use:处理定制素材各种模式的数据
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     *
     */
    static function getGoodsBuyMaterial($info,$price,$money,$time,$uid){
        if ($price == $money && $price > 0) {
            $order = [
                'pay_status' => 2,
                'pay_at'     => $time,
                'paid_price' => $price,
                'payment_details' => ModelsOrderModel::serializePaymentDetails($info,$price,$time)
            ];
            ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
            ModelsOrderMaterialModel::where('user_id', $uid)->where('id', $info->material->id)->update(['auth' => 'Y']);
            UserDetailModel::where('uid', $uid)->where('balance_status','!=', 1)->decrement('balance',$money);
        } else {
            abort(404);
        }
    }

    /**
     * Use:处理任务服务各种模式的数据
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     */
    static function getGoodsBuyTask($info,$price,$money,$time,$uid){
        if ($price == $money && $price > 0) {
            $update = [
                'pay_status' => 2,
                'pay_at'     => $time,
                'paid_price' => $info->paid_price + $price,
                'payment_details' => ModelsOrderModel::serializePaymentDetails($info,$price,$time)
            ];
            ModelsOrderModel::where('id', $info->id)->where('user_id', $uid)->update($update);
            ModelsOrderServiceModel::where('order_id', $info->id)->where('user_id', $uid)->update(['task_status' => 1]);
            UserDetailModel::where('uid', $uid)->where('balance_status','!=', 1)->decrement('balance',$money);
        } else {
            abort(404);
        }
    }

    static function memberBounty($money,$month,$uid,$code,$type=1){
    	
    	 $status = DB::transaction(function () use ($money, $month, $uid, $code, $type) {
            //扣除用户的余额
            DB::table('user_detail')->where('uid','=', $uid)->where('balance_status', '!=', 1)->decrement('balance', $money);
         
            //生成财务记录，action 8表示购买会员套餐
            $financial = [
                'action'   => 8,
                'pay_type' => $type,
                'cash'     => $money,
                'uid'      => $uid,
                'created_at' => date('Y-m-d H:i:s', time())
            ];
            FinancialModel::create($financial);
            //修改订单状态
            OrderModel::where('code', $code)->update(['status' => 1]);
            
            //修改用户的会员期限
            $userModel = UserModel::where('id',$uid)->first();   
               
            //判断用户购买之前是否会员
            $time = time();
           
		    Log::info('memberBounty.');
			Log::info('time==='.$time);
			
		   
		   $member_expire_date = strtotime($userModel['member_expire_date']);
		   Log::info('member_expire_date==='. $member_expire_date);

//             $user_type = '';
             if($money == '988'){
                 $user_type = 1;
             }elseif($money == '3688'){
                 $user_type = 2;
             }elseif($money == '9688'){
                 $user_type = 3;
             }elseif($money == '16888'){
                 $user_type = 4;
             }else{
                 $user_type = 1;
             }

            if( $member_expire_date <= $time){       
            	
            	$member_expire_date = $time + 3600*24*30*$month; 
            	UserModel::where('id',$uid)->update(['user_type'=>$user_type,'member_expire_date'=>date('Y-m-d H:i:s',$member_expire_date)]);
            }else{            		
            	$member_expire_date =  $member_expire_date + 3600*24*30*$month;
				UserModel::where('id',$uid)->update(['user_type'=>$user_type,'member_expire_date'=>date('Y-m-d H:i:s',$member_expire_date)]);
            }
      
        });
    	 
    	return is_null($status)?true:false;
    	 
    }

    static function moocBounty($money,$randNum,$uid,$code,$type=1){

        $status = DB::transaction(function () use ($money, $randNum, $uid, $code, $type) {
            //扣除用户的余额
            if($randNum != 0){
                DB::table('user_randnum')->where('uid','=', $uid)->decrement('randnum', $randNum);
                $type = 13;
            }

            $money = floatval($money) - $randNum;
            DB::table('user_detail')->where('uid','=', $uid)->where('balance_status', '!=', 1)->decrement('balance', $money);

            //生成财务记录，action 13表示购买课程
            $financial = [
                'action'     => 13,
                'pay_type'   => $type,
                'cash'       => $money,
                'uid'        => $uid,
                'created_at' => date('Y-m-d H:i:s', time())
            ];
            FinancialModel::create($financial);
            //修改订单状态
            OrderModel::where('code', $code)->update(['status' => 1]);
            MoocOrderModel::where('code',$code)->update(['status' => 1,'pay_type' => $type]);

        });

        return is_null($status)?true:false;
    }


}