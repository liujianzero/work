<?php

namespace App\Modules\Order\Model;

use App\Modules\Bre\Model\UserRandModel;
use App\Modules\Employ\Models\EmployModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\ServiceModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskServiceModel;
use App\Modules\User\Model\ModelsOrderMaterialModel;
use App\Modules\User\Model\ModelsOrderModel;
use App\Modules\User\Model\ModelsOrderServiceModel;
use App\Modules\User\Model\ModelsOrderViewModel;
use App\Modules\User\Model\UserCapacityModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\UserTypeModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
class OrderModel extends Model
{

    
    protected $table = 'order';

    protected $fillable = [
        'code', 'title', 'uid','task_id','member_id', 'buy_type', 'cash', 'status', 'invoice_status', 'note', 'created_at'
    ];

    public $timestamps = false;

    protected $hidden = [

    ];


    
    static function randomCode($uid)
    {
        $zero = '';
        for ($i = 0; $i < 6; $i++) {
            $zero .= '0';
        }
        return date('YmdHis') . $zero . $uid;
    }
    
    static function createOne($data,$uid)
    {
        $model = new OrderModel();
        $model->code = isset($data['code'])?$data['code']:Self::randomCode($uid);
        $model->title = $data['title'];
        $model->uid = $uid;
        $model->task_id = isset($data['task_id'])?$data['task_id']:'';
        $model->member_id = isset($data['member_id'])?$data['member_id']:'';
        $model->cash = $data['cash'];
        $model->status = isset($data['status'])?$data['status']:0;
        $model->invoice_status = isset($data['invoice_status'])?$data['invoice_status']:0;
        $model->note = isset($data['note'])?$data['note']:'';
        $model->created_at = date('Y-m-d H:i:s', time());
        $model->buy_type = $data['buy_type'];

        $model->save();
        return $model;
    }

    
    static function bountyOrder($uid,$money,$task_id)
    {
        $status = DB::transaction(function() use($uid,$money,$task_id){
            
            $order = [
                'code'=>Self::randomCode($uid),
                'title'=>'赏金托管',
                'uid'=>$uid,
                'cash'=>$money,
                'task_id'=>$task_id,
                'status'=>1,
                'created_at'=>date('Y-m-d H:i:s', time()),
            ];
            $order_obj = OrderModel::createOne($order,$uid);
            if($order_obj)
            {
                $bounty = TaskModel::select('task.bounty')->where('id','=',$task_id)->first();
                $bounty_order = [
                    'title'=>'赏金托管',
                    'cash'=>$bounty['bounty'],
                    'order_id'=>$order_obj->id,
                    'order_code'=>$order_obj->code,
                    'product_type'=>1,
                    'uid'=>$uid,
                    'status'=>0,
                    'created_at'=>date('Y-m-d H:i:s',time()),
                ];
                SubOrderModel::create($bounty_order);
            }
            $service = TaskServiceModel::where('task_id',$task_id)->lists('service_id')->toArray();
            if(!empty($service))
            {
                $service_ids = array_flatten($service);
                $service = ServiceModel::whereIn('id',$service_ids)->get()->toArray();
                foreach($service as $k=>$v)
                {
                    $sub_order = [
                        'title'=>'增值服务',
                        'cash'=>$v['price'],
                        'order_id'=>$order_obj->id,
                        'order_code'=>$order_obj->code,
                        'product_id'=>$v['id'],
                        'product_type'=>2,
                        'uid'=>$uid,
                        'created_at'=>date('Y-m-d H:i:s',time()),
                    ];
                    SubOrderModel::create($sub_order);
                }
            }
            return $order_obj;
        });
        return $status;
    }

    
    static function memberOrder($uid,$money,$memberId,$title,$type){

    	$order = [
    			'code'       => Self::randomCode($uid),
    			'title'      => $title,
    			'uid'        => $uid,
    			'cash'       => $money,
    			'member_id'  => $memberId,
    			'status'     => 0,
    			'created_at' => date('Y-m-d H:i:s', time()),
    			'buy_type'   => $type,
    	];
    	$order_obj = OrderModel::createOne($order,$uid);
    	
    	return $order_obj;
    }
    
    
    public function employBounty($uid,$money,$task_id)
    {

    }

    public $transactionData;

    
    public function recharge($payType, array $data)
    {
        switch ($payType){
            case 'alipay':
            case 'wechat':
                
                $orderInfo = OrderModel::where('code', $data['code'])->where('status', 0)->first();
                if (!empty($orderInfo)){
                    $financeInfo = array(
                        'code' => $data['code'],
                        'action' => 3,
                        'pay_type' => $payType == 'aplipay' ? 2 : 3,
                        'pay_account' => $data['pay_account'],
                        'pay_code' => $data['pay_code'],
                        'cash' => $data['money'],
                        'uid' => $orderInfo['uid'],
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s', time())
                    );
                    $this->transactionData['orderInfo'] = $orderInfo;
                    $this->transactionData['financeInfo'] = $financeInfo;
                    $status = DB::transaction(function (){
                        OrderModel::where('code', $this->transactionData['orderInfo']->code)->update(array('status' => 1));
                        FinancialModel::create($this->transactionData['financeInfo']);
                        $this->transactionData['status'] = UserDetailModel::where('uid', $this->transactionData['orderInfo']->uid)
                            ->increment('balance', $this->transactionData['financeInfo']['cash']);
                    });
                    Log::info($this->transactionData['status']);
                    return is_null($status) ? true : false;
                }
                break;
            case 'unionbank':
                break;
        }
    }

    
    static function adminRecharge($order)
    {
        $status = DB::transaction(function() use ($order){
            $order->update(array('status' => 1));
            $data = array(
                'action' => 3,
                'pay_type' => 1,
                'cash' => $order->cash,
                'uid' => $order->uid,
                'created_at'=>date('Y-m-d H:i:s',time())
            );
            FinancialModel::create($data);
            UserDetailModel::where('uid', $order->uid)->increment('balance', $order->cash);
        });

        return is_null($status) ? true : false;
    }
    
    static function dispatcher($order)
    {
        $prefix = [
            'e'=>'employ/success',
        ];
        
        $initial_str = substr($order,0,1);
        if(!empty($prefix[$initial_str]))
        {
            if($initial_str=='e')
            {
                
                $result = EmployModel::employResult();
                $route  = $prefix[$initial_str].'/'.$result['id'];
            }
            if(!$result)
                return false;

            return $route;
        }
        return false;
    }

    /**
     * Use:按照指定状态查找指定用户是否购买此产品
     * @param null $id
     * @param null $userId
     * @param null $type
     * @param null $status
     * @return mixed
     */
    static function getUserIsBuy( $id = null, $userId = null, $type = null, $status = null){
        $list = OrderModel::whereRaw('1 = 1');
        if($id){
            $list = $list -> where('member_id',intval($id));
        }

        if($userId){
            $list = $list -> where('uid',$userId);
        }

        if($type){
            $list = $list -> where('buy_type',$type);
        }

        if($status){
            $list = $list -> where('status',$status);
        }
        $data = $status == 1 ? $list->first() : $list->get();
        return $data;
    }

    static function getUserCapacity($uid){
        $data = OrderModel::where([
            'uid'      => $uid,
            'status'   => 1,
            'buy_type' => 'capacity',
        ])->get();

        $capacity = null;
        foreach($data as $v){
            $capacity += UserCapacityModel::getAppointID($v['member_id'])['capacity'];
        }
        return $capacity;
    }

    /**
     * Use:支付宝或微信渠道开通会员或者续费会员
     * @param $pay_type
     * @param $data
     * @return bool
     */
    static function thirdPayBuyMember($pay_type,$data){

        $info = OrderModel::where('code', $data['code'])->first();
        if ($info->status == 1) {
            return true;
        } else {
            if ($info->cash == $data['money']) {
                $status = DB::transaction(function () use ($info,$data,$pay_type) {
                    $res = OrderModel::where('code', $data['code'])->first();
                    $typeId = $res['member_id'] - 1;
                    $uid    = $res['uid'];
                    $user = UserModel::where('id',$uid)->first();
                    $expireDate = strtotime($user['member_expire_date']);
                    $nowTime    = time();
                    if($expireDate && $expireDate > $nowTime){ //续费会员
                        $userType  = $user['user_type'];  //当前会员类型  $typeId当前需要升级的会员类型
                        if($userType == $typeId){ //一样
                            $FinalDate = date('Y-m-d H:i:s',$expireDate + 3600*24*365); //最终的到期时间
                            UserModel::where('id',$uid)->update(['user_type'=>$userType,'member_expire_date'=>$FinalDate]);
                        }else{
                            $getUpgradeTime = UserTypeModel::getUpgradeTime($userType,$res['member_id']);
                            UserModel::where('id',$uid)->update(['user_type' => $typeId,'member_expire_date'=>$getUpgradeTime]);
                        }
                    }else{ //开通会员
                        $openTime = date('Y-m-d H:i:s',$nowTime + 3600*24*365);
                        UserModel::where('id',$uid)->update(['user_type' => $typeId,'member_expire_date'=> $openTime]);
                        $red_packet = UserRandModel::getRandNumForUserType($res['member_id']);
                        UserRandModel::createRandNum($uid,$red_packet,1);
                        FinancialModel::createOneRecord(13,$red_packet,$uid,'开通会员奖励红包',5);
                    }
                    //生成财务记录
                    $financial = [
                        'action'      => 1,
                        'pay_type'    => $pay_type,
                        'cash'        => $data['money'],
                        'uid'         => $info->uid,
                        'pay_account' => $data['pay_account'],
                        'pay_code'    => $data['pay_code'],
                        'created_at'  => date('Y-m-d H:i:s', time()),
                        'title'       => $info->title
                    ];

                    FinancialModel::create($financial);
                    //修改订单状态
                    OrderModel::where('code', $data['code'])->update(['status' => 1]);
                });
                return is_null($status)?true:false;
            } else {
                return false;
            }
        }
    }

    /**
     * Use:支付宝渠道购买课程
     * @param $data
     * @param $pay_type
     * @return bool
     */
    static function thirdPayBuyStudy($pay_type,$data){
        $info = OrderModel::where('code', $data['code'])->first();
        if($info->status == 1){
            return true;
        }else{
            if($info->cash == $data['money']){
                $status = DB::transaction(function() use ($pay_type,$data){
                    $res = OrderModel::where('code', $data['code'])->first();
                    //生成财务记录
                    $financial = [
                        'action'      => 2,
                        'pay_type'    => $pay_type,
                        'cash'        => $data['money'],
                        'uid'         => $res['uid'],
                        'pay_account' => $data['pay_account'],
                        'pay_code'    => $data['pay_code'],
                        'title'       => $res['title'],
                        'created_at'  => date('Y-m-d H:i:s', time()),
                    ];
                    FinancialModel::create($financial);
                    //修改订单状态
                    OrderModel::where('code', $data['code'])->update(['status' => 1]);
                });
                return is_null($status)?true:false;
            }else{
                return false;
            }
        }
    }

    /**
     * Use:支付宝渠道购买课容量
     * @param $data
     * @param $pay_type
     * @return bool
     */
    static function thirdPayBuyCapacity($pay_type,$data){
        $info = OrderModel::where('code', $data['code'])->first();
        if($info->status == 1){
            return true;
        }else{
            if($info->cash == $data['money']){
                $status = DB::transaction(function() use ($pay_type,$data){
                    $res = OrderModel::where('code', $data['code'])->first();
                    //生成财务记录
                    $financial = [
                        'action'      => 3,
                        'pay_type'    => $pay_type,
                        'cash'        => $data['money'],
                        'uid'         => $res['uid'],
                        'pay_account' => $data['pay_account'],
                        'pay_code'    => $data['pay_code'],
                        'title'       => $res['title'],
                        'created_at'  => date('Y-m-d H:i:s', time()),
                    ];
                    FinancialModel::create($financial);
                    //修改订单状态
                    OrderModel::where('code', $data['code'])->update(['status' => 1]);
                });
                return is_null($status)?true:false;
            }else{
                return false;
            }
        }
    }

    /**
     * Use: 支付宝充值
     * @param $data
     * @param $pay_type
     * @return bool
     *
     */
    static function thirdPayRecharge($pay_type, $data){
        $info = OrderModel::where('code', $data['code'])->first();
        if ($info->status == 1) {
            return true;
        } else {
            if ($info->cash == $data['money']) {
                $status = DB::transaction(function() use ($pay_type, $data) {
                    $res = OrderModel::where('code', $data['code'])->first();
                    //生成财务记录
                    $financial = [
                        'action'      => 11,
                        'pay_type'    => $pay_type,
                        'cash'        => $data['money'],
                        'uid'         => $res['uid'],
                        'pay_account' => $data['pay_account'],
                        'pay_code'    => $data['pay_code'],
                        'title'       => $res['title']
                    ];
                    FinancialModel::createOne($financial);
                    OrderModel::where('code', $data['code'])->update(['status' => 1]);
                    UserDetailModel::where('uid', $res['uid'])->increment('balance', $data['money']);
                });
                return is_null($status) ? true : false;
            } else {
                return false;
            }
        }
    }

    /**
     * Use:第三方支付处理商品类的逻辑
     * @param $pay_type
     * @param $data
     * @return bool
     */
    static function thirdPayBuyGoods($pay_type, $data){
        $res = OrderModel::where('code', $data['code'])->first();
        if ($res->status == 1) {
            return true;
        } else {
            if ($res->cash == $data['money']) {
                $status = DB::transaction(function() use( $res, $pay_type, $data ) {
                    $info   =  ModelsOrderModel::find(intval($res->member_id));
                    $price  = $info->total_price - $info->paid_price;//要支付的钱
                    $time   = date('Y-m-d H:i:s');
                    $title  = $res['title'];
                    if(!$info || $price <= 0.00 || $price != $data['money']){
                        return false;
                    }

                    switch($info->transaction_mode){
                        case 1:
                            var_dump('购买商品');exit;
                            break;
                        case 2:
                            $title  = $res['title'].$info->type;
                            OrderModel::thirdPayBuyLook($info,$price,$data['money'],$time,$res['uid']);
                            break;
                        case 3:
                            OrderModel::thirdPayBuyMaterial($info,$price,$data['money'],$time,$res['uid']);
                            break;
                        case 4:
                            OrderModel::thirdPayBuyTask($info,$price,$data['money'],$time,$res['uid']);
                            break;
                        default:
                            return false;
                            break;
                    }

                    if($info->transaction_mode == 2 || $info->transaction_mode == 3){
                        $kickback = OrderKickBackModel::getOrderKickback('goods',$info->transaction_mode,$info->type);
                        $kickbackMoney = round($data['money']*$kickback,2);
                        if($kickbackMoney > '0.1'){
                            UserDetailModel::where('uid', $info->shop_id)->increment('balance',$kickbackMoney);
                            //生成财务记录
                            $financial = [
                                'action'      => 15,
                                'pay_type'    => $pay_type,
                                'cash'        => $kickbackMoney,
                                'uid'         => $info->shop_id,
                                'pay_account' => $data['pay_account'],
                                'pay_code'    => $data['pay_code'],
                                'title'       => str_replace('购买','出售',$title),
                                'created_at'  => date('Y-m-d H:i:s', time()),
                            ];
                            FinancialModel::create($financial);
                        }
                    }

                    //生成财务记录
                    $financial = [
                        'action'      => 4,
                        'pay_type'    => $pay_type,
                        'cash'        => $data['money'],
                        'uid'         => $res['uid'],
                        'pay_account' => $data['pay_account'],
                        'pay_code'    => $data['pay_code'],
                        'title'       => $title,
                        'created_at'  => date('Y-m-d H:i:s', time()),
                    ];
                    FinancialModel::create($financial);
                    //修改订单状态
                    OrderModel::where('code', $data['code'])->update(['status' => 1]);
                });
                return is_null($status) ? true : false;
            } else {
                return false;
            }
        }
    }

    /**
     * Use:第三方支付购买商品查看付费的支付逻辑
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     * @return bool
     *
     */
    static function thirdPayBuyLook($info,$price,$money,$time,$uid){
        $status = DB::transaction(function() use ($info,$price,$money,$time,$uid) {
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
                    return false;
                    break;
            }
        });
        return is_null($status) ? true : false;
        }
    /**
     * Use:第三方支付购买商品素材的支付逻辑
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     * @return bool
     *
     */
    static function thirdPayBuyMaterial($info,$price,$money,$time,$uid){
        $status = DB::transaction(function() use($info,$price,$money,$time,$uid){
            $order = [
                'pay_status' => 2,
                'pay_at'     => $time,
                'paid_price' => $price,
                'payment_details' => ModelsOrderModel::serializePaymentDetails($info,$price,$time)
            ];
            ModelsOrderModel::where('user_id', $uid)->where('id', $info->id)->update($order);
            ModelsOrderMaterialModel::where('user_id', $uid)->where('id', $info->material->id)->update(['auth' => 'Y']);
        });
        return is_null($status) ? true : false;
    }

    /**
     * Use:第三方支付购买商品服务的支付逻辑
     * @param $info
     * @param $price
     * @param $money
     * @param $time
     * @param $uid
     * @return bool
     *
     */
    static function thirdPayBuyTask($info,$price,$money,$time,$uid){
        $status = DB::transaction(function() use($info,$price,$money,$time,$uid){
            $update = [
                'pay_status' => 2,
                'pay_at'     => $time,
                'paid_price' => $info->paid_price + $price,
                'payment_details' => ModelsOrderModel::serializePaymentDetails($info,$price,$time)
            ];
            ModelsOrderModel::where('id', $info->id)->where('user_id', $uid)->update($update);
            ModelsOrderServiceModel::where('order_id', $info->id)->where('user_id', $uid)->update(['task_status' => 1]);
        });
        return is_null($status) ? true : false;
    }

    /**
     * Use:获取商品支付成功后调整的页面
     * @param $data
     * @return \Illuminate\Http\RedirectResponse
     */
    static function getPayReturnPage($data){
        $res  = OrderModel::where('code', $data['code'])->first();
        $info = ModelsOrderModel::find(intval($res->member_id));
        switch($info->transaction_mode){
            case 1:
                return redirect()->route('indexPage')->with(['message' => '支付成功!']);
                break;
            case 2:
                return redirect()->route('myOrder.myViewOut')->with(['message' => '支付成功!']);
                break;
            case 3:
                return redirect()->route('myOrder.myMaterialOut')->with(['message' => '支付成功!']);
                break;
            case 4:
                return redirect()->route('myOrder.myTaskOut')->with(['message' => '支付成功!']);
                break;
            default:
                return redirect()->route('indexPage')->with(['message' => '支付成功!']);
                break;
        }
    }
}
