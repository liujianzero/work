<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController as BasicUserCenterController;
use App\Http\Requests;
use App\Modules\Employ\Models\EmployCommentsModel;
use App\Modules\Employ\Models\EmployGoodsModel;
use App\Modules\Employ\Models\EmployModel;
use App\Modules\Employ\Models\UnionAttachmentModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Manage\Model\ServiceModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Order\Model\ShopOrderModel;
use App\Modules\Shop\Models\GoodsCommentModel;
use App\Modules\Shop\Models\GoodsServiceModel;
use App\Modules\Shop\Models\ShopModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Http\Requests\ServiceRequest;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsFolderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends  BasicUserCenterController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('myshop');//主题初始化
        $this->user = Auth::user();
    }

    /**
     * 创建service的控制器
     */
    public function serviceCreate()
    {
        $this->theme->setTitle('发布服务');
        $uid = Auth::id();
        $arrCate = TaskCateModel::findByPid([0]);
        $arrCateSecond = array();
        //查询店铺id
        $shopId = ShopModel::getShopIdByUid($uid);
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($uid);

        if(!empty($arrCate[0]['id']))
            $arrCateSecond = TaskCateModel::findByPid([$arrCate[0]['id']]);
        //查询发布商品推荐服务上否开启
        $service = ServiceModel::where('status',1)->where('type',2)->where('identify','FUWUTUIJIAN')->first();
        $is_open = 1;
        if(!$service)
        {
            $is_open = 0;
        }
        //查询作品平台抽佣
        $tradeRateArr = ConfigModel::getConfigByAlias('employ_percentage');
        if(!empty($tradeRateArr)){
            $tradeRate = $tradeRateArr->rule;
        }else{
            $tradeRate = 0;
        }
        //查询当前的服务的单位
        $recommend_service_unit = (\CommonClass::getConfig('recommend_service_unit'))?\CommonClass::getConfig('recommend_service_unit'):0;//默认一天，防止错误
        $map = [
            0=>'一天',
            1=>'一个月',
            2=>'三个月',
            3=>'六个月',
            4=>'一年'
        ];
        $view = [
            'arr_cate' => $arrCate,
            'arrCateSecond'=>$arrCateSecond,
            'arr_cate' => $arrCate,
            'service'=>$service,
            'is_open'=>$is_open,
            'recommend_service_unit'=>$recommend_service_unit,
            'map'=>$map,
            'is_shop_open'=>$isOpenShop,
            'shop_id'=>$shopId,
            'trade_rate'=>$tradeRate
        ];
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.service.servicecreate',$view)->render();
    }

    /**
     * 服务提交
     * @param Request $request
     */
    public function serviceUpdate(ServiceRequest $request)
    {
        $data = $request->except('_token');

        $uid = Auth::user()['id'];
        //验证用户是否开店
        $is_shop = ShopModel::where('uid',$uid)->where('status',1)->first();
        if(!$is_shop)
            return redirect()->back()->with('error','您还未开店，或店铺未激活，不能发布');
        //处理封面
        if (!empty($data['cover'])){
            $cover = $request->file('cover');
            $result = \FileClass::uploadFile($cover,'sys');
            if ($result){
                $result = json_decode($result, true);
                $data['cover'] = $result['data']['url'];
            }
        }else{
            $data['cover'] = '';
        }
        //查询是否需要审核
        $service_switch = \CommonClass::getConfig('service_check');


        //将服务写入到数据表
        $service = [
            'uid'=>$uid,
            'shop_id'=>$is_shop['id'],
            'title'=>e($data['title']),
            'desc'=>\CommonClass::removeXss($data['desc']),
            'cate_id'=>intval($data['secondCate']),
            'type'=>2,
            'cash'=>$data['cash'],
            'cover'=>$data['cover'],
            'is_recommend'=>0,
            'created_at'=>date('Y-m-d H:i:s',time()),
            'file_id'=>!empty($data['file_id'])?$data['file_id']:'',
        ];
        if($service_switch==2)
        {
            $service['status'] = 1;//免审核
        }
        $result = GoodsModel::serviceCreate($service);

        if(!$result)
            return redirect()->back()->with(['error'=>'创建失败！']);

        if(isset($data['is_recommend']))
            return redirect()->to('user/serviceBounty/'.$result['id']);

        return redirect()->to('user/waitServiceHandle/'.$result['id']);
    }

    /**
     * 等待页面
     * @param $id
     * @return mixed
     */
    public function waitServiceHandle($id)
    {
        $this->theme->setTitle('服务审核');
        //查询商品状态
        $goodsInfo = GoodsModel::where('id',$id)->where('type',2)->where('is_delete',0)->first();
        //判断该商品是否审核通过
        if(!empty($goodsInfo) && $goodsInfo['status'] == 1){
            return redirect('user/serviceList');
        }
        $qq = \CommonClass::getConfig('qq');
        $data = array(
            'id' => $id,
            'goods_info' => $goodsInfo,
            'qq' => $qq
        );
        $this->theme->setTitle('服务审核');
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.service.servicesuccess',$data)->render();
    }
    /**
     * 购买增值服务页面
     * @param $id
     * @return mixed
     */
    public function serviceBounty($id)
    {
        $this->initTheme('userfinance');
        $this->theme->setTitle('购买增值服务');
        $id = intval($id);
        $uid = Auth::id();

        //查询推送服务
        $service = ServiceModel::where('identify','FUWUTUIJIAN')->where('status',1)->first();

        if(!$service)
            return redirect()->back()->with('message','推送商城服务已关闭！');

        $userInfo = UserDetailModel::select('balance')->where('uid', $uid)->first();

        $payConfig = ConfigModel::getConfigByType('thirdpay');
        foreach ($payConfig as $k => $v){
            if ($v['status']){
                $pay[$k] = 1;
            }
        }
        $this->theme->set('TYPE',3);
        $data = [
            'service_cash' => $service['price'],
            'pay_config' => $pay,
            'balance' => $userInfo->balance,
            'good_id' => $id,
            'service_id'=>$service['id']
        ];

        return $this->theme->scope('user.service.servicebounty', $data)->render();
    }

    /**
     * 服务发布支付提交
     * @param Request $request
     */
    public function serviceBountyPay(Request $request)
    {
        $uid = Auth::id();
        $data = $request->except('_token');
        $service = ServiceModel::where('id',$data['service_id'])->first();
        $goods_id = intval($data['goods_id']);
        $money = $service['price'];
        //查询用户的余额
        $balance = UserDetailModel::where(['uid' => $uid])->first();
        $balance = (float)$balance['balance'];

        //生成一条订单
        $is_ordered = ShopOrderModel::serviceOrder($uid,$service['price'],$service['id']);
        //选择使用不同的支付当时支付
        //如果余额足够就直接余额付款
        if ($balance >= $money && $data['pay_canel'] == 0)
        {
            //验证用户的密码是否正却
            $password = UserModel::encryptPassword($data['password'], $this->user['salt']);
            if ($password != $this->user['alternate_password']) {
                return redirect()->back()->with(['error' => '您的支付密码不正确']);
            }
            //余额支付
            $result = GoodsModel::servicePay($money,$uid,$goods_id,$is_ordered['id']);
            $service = GoodsModel::where('id',$goods_id)->first();
            //跳转到置顶页面
            if(!$result)
                return redirect()->back()->with(['error'=>'支付失败']);

            return redirect()->to('user/serviceList')->with(['message'=>'您的服务成功被置顶到商城,'.date('Y-m-d',strtotime($service['recommend_end'])).'到期']);

        } else if (isset($data['pay_type']) && $data['pay_canel'] == 1) {
            //跳转支付赏金托管流程,返回成功之后就直接执行上面的托管
            if ($data['pay_type'] == 1) {//支付宝支付
                $config = ConfigModel::getPayConfig('alipay');
                $objOminipay = Omnipay::gateway('alipay');
                $objOminipay->setPartner($config['partner']);
                $objOminipay->setKey($config['key']);
                $objOminipay->setSellerEmail($config['sellerEmail']);
                $siteUrl = \CommonClass::getConfig('site_url');
                $objOminipay->setReturnUrl($siteUrl . '/order/pay/alipay/return');
                $objOminipay->setNotifyUrl($siteUrl . '/order/pay/alipay/notify');

                $response = Omnipay::purchase([
                    'out_trade_no' => $is_ordered->code, //your site trade no, unique
                    'subject' => \CommonClass::getConfig('site_name'), //order title
                    'total_fee' => $money, //order total fee $money
                ])->send();
                $response->redirect();
            } else if ($data['pay_type'] == 2) {//微信支付
                $config = ConfigModel::getPayConfig('wechatpay');
                $wechat = Omnipay::gateway('wechat');
                $wechat->setAppId($config['appId']);
                $wechat->setMchId($config['mchId']);
                $wechat->setAppKey($config['appKey']);
                $out_trade_no = $is_ordered->code;
                $params = array(
                    'out_trade_no' => $is_ordered->code, // billing id in your system
                    'notify_url' => env('WECHAT_NOTIFY_URL', url('order/pay/wechat/notify')), // URL for asynchronous notify
                    'body' => \CommonClass::getConfig('site_name') . '余额充值', // A simple description
                    'total_fee' => $money, // Amount with less than 2 decimals places
                    'fee_type' => 'CNY', // Currency name from ISO4217, Optional, default as CNY
                );
                $response = $wechat->purchase($params)->send();

                $img = QrCode::size('280')->generate($response->getRedirectUrl());

                $view = array(
                    'cash'=>$money,
                    'img' => $img
                );
                return $this->theme->scope('task.wechatpay', $view)->render();
            } else if ($data['pay_type'] == 3) {
                dd('银联支付！');
            }
        } else if (isset($data['account']) && $data['pay_canel'] == 2) {//银行卡支付
            dd('银行卡支付！');
        } else//如果没有选择其他的支付方式
        {
            return redirect()->back()->with(['error' => '请选择一种支付方式']);
        }

    }

    /**
     * 购买服务
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function serviceBuy($id)
    {
        $id = intval($id);
        //查询服务的价格
        $service = GoodsModel::where('id',$id)->first();

        return redirect()->to('employ/create/'.$service['uid'].'/'.$service['id']);
    }

    /**
     * 服务列表
     * @param Request $request
     * @return mixed
     */
    public function serviceList(Request $request)
    {
        $this->theme->setTitle('我发布的服务');
        $data = $request->all();
        $uid = Auth::user()['id'];
        $all_cate = TaskCateModel::findAllCache();
        $all_cate = \CommonClass::keyBy($all_cate,'id');

        $goodsModel = new GoodsModel();
        $service = $goodsModel->serviceList($uid,$data);
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($uid);
        //店铺id
        $shopId = ShopModel::getShopIdByUid($uid);
        $map = [
                0=>'待审核',
                1=>'出售中',
                2=>'已下架',
                3=>'审核未通过'
        ];

        $serviceStatistics = $goodsModel->serviceStatistics($uid);
        $this->theme->set('TYPE',3);
        $domain = url();
        $data = [
            'service'=>$service,
            'all_cate'=>$all_cate,
            'serviceStatistic'=>$serviceStatistics,
            'map'=>$map,
            'domain'=>$domain,
            'shop_id'=>$shopId,
            'is_open_shop'=>$isOpenShop
        ];
        return $this->theme->scope('user.service.servicelist', $data)->render();
    }

    /**
     * 上下架服务
     * @param $service_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function serviceAdded($service_id)
    {
        $uid = Auth::user()['id'];
        //查询当前服务是否通过审核
        $service = GoodsModel::where('id',$service_id)->where('uid',$uid)->whereIn('status',[1,2])->first();

        if(!$service)
            return redirect()->back()->with('error','操作失败！');
        //判断当前的店铺是否开启
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($uid);
        if($isOpenShop!=1)
            return redirect()->back()->with(['error'=>'店铺关闭不能操作！']);
        //修改当前的服务的上下架状态
        $result = false;
        if($service['status']==1)
        {
            $result = GoodsModel::where('id',$service_id)->update(['status'=>2,'updated_at'=>date('Y-m-d H:i:s',time())]);
        }elseif($service['status']==2)
        {
            $result= GoodsModel::where('id',$service_id)->update(['status'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);
        }
        if(!$result)
            return redirect()->back()->with('error','操作失败！');

        return redirect()->back()->with('error','操作成功！');
    }

    /**
     * 删除服务
     * @param $service_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function serviceDelete($service_id)
    {
        $uid = Auth::user()['id'];
        //查询当前删除是否合法
        $result = GoodsModel::where('id',$service_id)->where('uid',$uid)->update(['is_delete'=>1,'updated_at'=>date('Y-m-d H:i:s',time())]);

        if(!$result)
            return redirect()->back()->with('error','操作失败！');

        return redirect()->back()->with('error','操作成功！');
    }

    /**
     * 我购买的服务
     */
    public function serviceMine(Request $request)
    {
        $this->initTheme('accepttask');//主题初始化
        $this->theme->setTitle('我购买的服务');
        $uid = Auth::user()['id'];
        $data = $request->all();

        //查询我购买的服务
        $employ = new EmployModel();
        $employ = $employ->employMine($uid,$data);
        $map = [
            0=>'待受理',
            1=>'工作中',
            2=>'验收中',
            3=>'待评价',
            4=>'交易完成',
            5=>'交易失败',
            6=>'交易失败',
            7=>'交易维权',
            8=>'交易维权',
            9=>'交易失败'
        ];

        $domian = url();
        $view = [
            'employ'=>$employ,
            'map'=>$map,
            'domain'=>$domian
        ];
        $this->theme->set('TYPE',2);
        return $this->theme->scope('user.service.servicemine', $view)->render();
    }

    /**
     * 我承接的任务
     */
    public function serviceMyJob(Request $request)
    {
        $this->initTheme('accepttask');//主题初始化
        $this->theme->setTitle('我承接的服务');
        $uid = Auth::user()['id'];
        $data = $request->all();

        //查询我承接的任务
        $employ = new EmployModel();
        $employ = $employ->employMyJob($uid,$data);

        //查询我承接的任务的任务来源
        $employ_ids = $employ->where('employee_uid',$uid)->where('employ_type',1)->where('bounty_status',1)->lists('id')->toArray();
        $employ_goods = EmployGoodsModel::select('employ_goods.*','gs.title','gs.id as goods_id')->whereIn('employ_goods.employ_id',$employ_ids)
            ->leftjoin('goods as gs','employ_goods.service_id','=','gs.id')
            ->get()->toArray();

        $employ_goods = \CommonClass::keyBy($employ_goods,'employ_id');

        $map = [
            0=>'待受理',
            1=>'工作中',
            2=>'验收中',
            3=>'待评价',
            4=>'交易完成',
            5=>'交易失败',
            6=>'交易失败',
            7=>'交易维权',
            8=>'交易维权',
            9=>'交易失败'
        ];
        $domain = url();
        $view = [
            'employ'=>$employ,
            'map'=>$map,
            'employ_goods'=>$employ_goods,
            'domain'=>$domain
        ];
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.service.servicemyjob', $view)->render();
    }

    //编辑未审核的
    public function serviceEdit($id)
    {
        $this->theme->setTitle('编辑服务');
        $service = GoodsModel::where('id',$id)->where('is_delete',0)->first();

        if(!$service)
            return redirect()->with('error','该任务已经删除！');

        if($service['status']!=0 && $service['status']!=3)
            return redirect()->with('error','该任务已经审核通过，不能编辑！');

        //查询附件
        $service_attachment = UnionAttachmentModel::where('object_id',$service['id'])->where('object_type',4)->lists('attachment_id');
        $service_attachment = AttachmentModel::whereIn('id',$service_attachment)->get()->toArray();
        $service_attachment = json_encode($service_attachment);
        //查询分类
        $cate_data = TaskCateModel::findById($service['cate_id']);
        $arrCate = TaskCateModel::findByPid([0]);
        $arrCate = \CommonClass::keyBy($arrCate,'id');
        $arrCateSecond = array();
        if(!empty($arrCate[$cate_data['pid']]))
            $arrCateSecond = TaskCateModel::findByPid([$arrCate[$cate_data['pid']]['id']]);

        //查询当前的服务的单位
        $recommend_service_unit = (\CommonClass::getConfig('recommend_service_unit'))?\CommonClass::getConfig('recommend_service_unit'):0;//默认一天，防止错误
        $map = [
            0=>'一天',
            1=>'一个月',
            2=>'三个月',
            3=>'六个月',
            4=>'一年'
        ];
        $domian = url();

        $view = [
            'service'=>$service,
            'arrCate'=>$arrCate,
            'arrCateSecond'=>$arrCateSecond,
            'service_attachment'=>$service_attachment,
            'domain'=>$domian,
            'recommend_service_unit'=>$recommend_service_unit,
            'map'=>$map,
            'cate'=>$cate_data
        ];
        return $this->theme->scope('user.service.serviceEdit', $view)->render();
    }
    //编辑删除附件
    public function serviceEditUpdate(ServiceRequest $request)
    {
        $data = $request->except('_token');

        $uid = Auth::user()['id'];
        //判斷當前用戶是否是當前服務的發佈者
        $service = GoodsModel::where('id',$data['id'])->where('uid',$uid)->first();

        if(!$service)
            return redirect()->back()->with(['error'=>'你不是当前服务的发布者']);

        if($service['status']!=0)
            return redirect()->back()->with(['error'=>'当前任务已经审核通过了，不能再修改！']);
        //处理封面
        if (!empty($data['cover'])){
            $cover = $request->file('cover');
            $result = \FileClass::uploadFile($cover,'sys');
            if ($result){
                $result = json_decode($result, true);
                $data['cover'] = $result['data']['url'];
            }
        }else{
            $data['cover'] = $service['cover'];
        }
        //修改当前的任务数据
        $service_update = [
            'id'=>$service['id'],
            'secondCate'=>$data['secondCate'],
            'title'=>e($data['title']),
            'desc'=>\CommonClass::removeXss($data['desc']),
            'cate_id'=>intval($data['secondCate']),
            'cash'=>$data['cash'],
            'cover'=>$data['cover'],
            'file_id'=>!empty($data['file_id'])?$data['file_id']:'',
        ];
        $result = GoodsModel::updateService($service_update);

        if(!$result)
            return redirect()->back()->with(['error'=>'修改失败！']);

        return redirect()->back()->with(['message'=>'修改成功！']);
    }

    /**
     * 删除附件
     */
    public function serviceAttchDelete(Request $request)
    {
        $id = $request->get('id');
        //查询当前的附件
        $file = AttachmentModel::where('id',$id)->first()->toArray();
        if(!$file)
        {
            return response()->json(['errCode' => 0, 'errMsg' => '附件没有上传成功！']);
        }
        //删除附件
        if(is_file($file['url']))
            unlink($file['url']);
        $result = AttachmentModel::destroy($id);

        //删除附件和服务关系
        UnionAttachmentModel::where('attachment_id',$id)->delete();

        if (!$result) {
            return response()->json(['errCode' => 0, 'errMsg' => '删除失败！']);
        }
        return response()->json(['errCode' => 1, 'errMsg' => '删除成功！']);
    }

    /**
     * 编辑未审核通过的
     */
    public function serviceEditNew($id)
    {
        $this->theme->setTitle('编辑服务');
        $service = GoodsModel::where('id',$id)->where('is_delete',0)->first();

        if(!$service)
            return redirect()->with('error','该任务已经删除！');

        if($service['status']!=0 && $service['status']!=3)
            return redirect()->with('error','该任务已经审核通过，不能编辑！');

        //查询附件
        $service_attachment = UnionAttachmentModel::where('object_id',$service['id'])->where('object_type',4)->lists('attachment_id');
        $service_attachment = AttachmentModel::whereIn('id',$service_attachment)->get()->toArray();

        //查询分类
        $cate_data = TaskCateModel::findById($service['cate_id']);
        $arrCate = TaskCateModel::findByPid([0]);
        $arrCate = \CommonClass::keyBy($arrCate,'id');
        $arrCateSecond = array();
        if(!empty($arrCate[$cate_data['pid']]))
            $arrCateSecond = TaskCateModel::findByPid([$arrCate[$cate_data['pid']]['id']]);
        //查询当前的服务的单位
        $recommend_service_unit = (\CommonClass::getConfig('recommend_service_unit'))?\CommonClass::getConfig('recommend_service_unit'):0;//默认一天，防止错误
        $map = [
            0=>'一天',
            1=>'一个月',
            2=>'三个月',
            3=>'六个月',
            4=>'一年'
        ];
        //查询发布商品推荐服务上否开启
        $service_recommend = ServiceModel::where('status',1)->where('type',2)->where('identify','FUWUTUIJIAN')->first();
        $is_open = 1;
        if(!$service)
        {
            $is_open = 0;
        }
        $domian = url();
        $view = [
            'service'=>$service,
            'arrCate'=>$arrCate,
            'arrCateSecond'=>$arrCateSecond,
            'service_attachment'=>$service_attachment,
            'domain'=>$domian,
            'recommend_service_unit'=>$recommend_service_unit,
            'map'=>$map,
            'is_open'=>$is_open,
            'service_recommend'=>$service_recommend,
            'cate'=>$cate_data
        ];
        return $this->theme->scope('user.service.serviceEditNew', $view)->render();
    }
    /**
     * 创建一条新的服务
     * @param Request $request
     */
    public function serviceEditCreate(ServiceRequest $request)
    {
        $data = $request->except('_token');
        $uid = Auth::user()['id'];
        //验证用户是否开店
        $is_shop = ShopModel::where('uid',$uid)->where('status',1)->first();
        if(!$is_shop)
            return redirect()->back()->with('error','您还未开店，或店铺未激活，不能发布');
        //处理封面
        if (!empty($data['cover'])){
            $cover = $request->file('cover');
            $result = \FileClass::uploadFile($cover,'sys');
            if ($result){
                $result = json_decode($result, true);
                $data['cover'] = $result['data']['url'];
            }
        }elseif(!empty($data['cover_old'])){
            $data['cover'] = $data['cover_old'];
        }else{
            $data['cover'] = '';
        }
        //将服务写入到数据表
        $service = [
            'uid'=>$uid,
            'shop_id'=>$is_shop,
            'title'=>e($data['title']),
            'desc'=>\CommonClass::removeXss($data['desc']),
            'cate_id'=>intval($data['secondCate']),
            'type'=>2,
            'cash'=>$data['cash'],
            'cover'=>$data['cover'],
            'is_recommend'=>0,
            'created_at'=>date('Y-m-d H:i:s',time()),
            'file_id'=>isset($data['file_id'])?$data['file_id']:'',
        ];

        $result = GoodsModel::serviceCreate($service);

        if(!$result)
            return redirect()->back()->with(['error'=>'创建失败！']);

        if(isset($data['is_recommend']) && $data['is_recommend']==1)
            return redirect()->to('user/serviceBounty/'.$result['id']);

        return redirect()->to('user/serviceList')->with('message','创建成功！');
    }

    public function shopcommentowner(Request $request)
    {
        $this->initTheme('accepttask');//主题初始化
        $this->theme->setTitle('交易评价');
        $data = $request->all();
        $uid = Auth::user()['id'];
        //查询当前用户的shop_id
        $shop = ShopModel::where('uid',$uid)->first();
        $shop_id = $shop['id'];
        $is_open_shop = 1;
        if(!$shop)
            $is_open_shop = 0;

        if(!empty($data['type']) && $data['type']==1 && $is_open_shop==1)
        {
            //查询当前用户的所有服务id
            $service_ids = GoodsModel::where('shop_id',$shop_id)->where('type',2)->lists('id')->toArray();
            //查询所有的employ_id
            $employ_ids = EmployGoodsModel::whereIn('service_id',$service_ids)->lists('employ_id')->toArray();
            //关联查询所有的服务
            $service = EmployGoodsModel::select('employ_goods.employ_id','gs.*')
                ->whereIn('service_id',$service_ids)
                ->leftjoin('goods as gs','gs.id','=','employ_goods.service_id')
                ->get()->toArray();
            $service = \CommonClass::keyBy($service,'employ_id');

            $comments = EmployCommentsModel::select('employ_comment.*','us.name as user_name','ud.avatar')
                ->whereIn('employ_comment.employ_id',$employ_ids);
            if(!empty($data['from']) &&  $data['from']==1)
            {
                $comments = $comments->where('employ_comment.to_uid',$uid)
                    ->join('users as us','us.id','=','employ_comment.from_uid')
                    ->leftjoin('user_detail as ud','ud.uid','=','employ_comment.from_uid');

            }else{
                $comments = $comments->where('employ_comment.from_uid',$uid)
                    ->join('users as us','us.id','=','employ_comment.to_uid')
                    ->leftjoin('user_detail as ud','ud.uid','=','employ_comment.from_uid');
            }
            $comments = $comments->paginate(5);
            $comments_toArray = $comments->toArray();
            $comments_toArray['data'] = \CommonClass::keyBy($comments_toArray['data'],'employ_id');
            $view['service'] = $service;
        }else if($is_open_shop==1){
            //查询当前用户的所有商品id
            $service_ids = GoodsModel::where('shop_id',$shop_id)->where('type',1)->lists('id')->toArray();
            $comments = GoodsCommentModel::select('goods_comment.*','ud.avatar','us.name as user_name','gs.title as goods_name','gs.cash as goods_price');
            //查询作品的所有评价
            if(!empty($data['from']) &&  $data['from']==1)
            {
                $comments = $comments->where('goods_comment.uid',$uid);
            }else{
                $comments = $comments->whereIn('goods_comment.goods_id',$service_ids);
            }
            $comments = $comments->join('users as us','us.id','=','goods_comment.uid')
                ->leftjoin('user_detail as ud','ud.uid','=','goods_comment.uid')
                ->leftjoin('goods as gs','gs.id','=','goods_comment.goods_id')
                ->paginate(5);
            $comments_toArray = $comments->toArray();
        }else{
            $comments = '';
            $comments_toArray = '';
        }

        $this->theme->set('TYPE',3);
        $view['comments'] = $comments;
        $view['comments_toArray'] = $comments_toArray;
        $view['is_shop_open'] = $is_open_shop;
        return $this->theme->scope('user.shopcommentowner',$view)->render();
    }

    /**
     * 验证服务的价格
     * @param Request $request
     * @return string
     */
    public function serviceCashValid(Request $request)
    {
        $data = $request->except('_token');
        //检测赏金额度是否在后台设置的范围之内
        $employ_bounty_min_limit = \CommonClass::getConfig('employ_bounty_min_limit');

        //判断赏金必须大于最小限定
        if ($employ_bounty_min_limit > $data['param']) {
            $data['info'] = '服务价格应该大于' . $employ_bounty_min_limit ;
            $data['status'] = 'n';
            return json_encode($data);
        }

        $data['status'] = 'y';

        return json_encode($data);
    }
    
    
    /**
     * 发布服务流程
     *
     * @param Request $request
     * @return mixed
     */
    public function publicServiceStep(Request $request) {
    	$step = $_GET ['step'];
    	$uid = Auth::id ();
    	// 获取文件夹列表
    	if ($step == 1) {
    			
    		// 默认文件夹的作品总数
    		$defaultFolderCount = ModelsContentModel::where ( 'uid', '=', $uid )->where ( 'folder_id', 0 )->count ();
    
    		// 获取用户的文件夹
    		$folderList = ModelsFolderModel::select ( 'id', 'name', 'cover_img', 'auth_type', 'update_time', 'create_time' )->where ( 'uid', '=', $uid )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();
    		foreach ( $folderList as &$v ) {
    			$v ["count"] = ModelsContentModel::where ( 'uid', '=', $uid )->where ( 'folder_id', $v ["id"] )->where('is_goods',0)->count ();
    		}
    		$view = [
    				'folder' => $folderList,
    				'defaultFolderCount' => $defaultFolderCount
    		];
    		$this->initTheme ( 'ajaxpage' ); // 主题初始化
    		$this->theme->set ( 'TYPE', 1 );
    		return $this->theme->scope ( 'ajax.publicservice_1', $view )->render ();
    		// 获取文件夹所有作品
    	} else if ($step == 2) {
    			
    		$id = $_GET ["id"];
    		if ($id != 0) {
    			$models = ModelsContentModel::select ( 'id', 'title', 'content', 'cover_img', 'create_time' )->where('is_goods',0)->where ( 'folder_id', '=', $id )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();
    		} else {
    			// 获取默认文件夹下的所有作品
    			$models = ModelsContentModel::select ( 'id', 'title', 'content', 'cover_img', 'create_time' )->where('is_goods',0)->where ( 'uid', '=', $uid )->where ( 'folder_id', '=', $id )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();
    			$folder = null;
    		}
    		// 获取用户的所有文件夹
    		$folderList = ModelsFolderModel::select ( 'id', 'name', 'cover_img', 'update_time', 'create_time' )->where ( 'id', '!=', $id )->where ( 'uid', '=', $uid )->orderBy ( 'create_time', 'desc' )->limit ( 10 )->get ();
    		$folderCount = ModelsContentModel::where ( 'uid', '=', $uid )->where ( 'id', '!=', $id )->count ();
    		$view = [
    				'models' => $models,
    				'folderList' => $folderList
    		];
    		$this->initTheme ( 'ajaxpage' ); // 主题初始化
    			
    		return $this->theme->scope ( 'ajax.publicservice_2', $view )->render ();
    	} else if ($step == 3) {
    			
    		$id = $_GET ["id"];
    			
    		$models = ModelsContentModel::where ( 'id', $id )->first ();
    			
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
    			
    		$this->initTheme ( 'ajaxpage' ); // 主题初始化
    		$view = [
    				'id' => $id,
    				'list' => $cate,
    				"list1" => $category,
    				'content' => $models,
    				'paramaters' => $param
    		];
    			
    		return $this->theme->scope ( 'ajax.publicservice_3', $view )->render ();
    	}
    }
    
    
    /**
     * 添加服务
     *
     * @param Request $request
     * @return mixed
     */
    public function addService(Request $request) {
    	$id = intval ( $request->get ( 'id' ) );
    	if (! $id) {
    		return response ()->json ( [
    				'errMsg' => '参数错误！'
    		] );
    	}
    	
    	$uid = Auth::user()['id'];
    	//验证用户是否开店
    	$is_shop = ShopModel::where('uid',$uid)->where('status',1)->first();
    	if(!$is_shop){
    		
    		$data = array (
    				'result' => false,
    				'message' => '您还未开店，或店铺未激活，不能发布'
    		);
    		return json_encode ( $data );
    		
    	}
    	
    	//查询是否需要审核
    	$service_switch = \CommonClass::getConfig('service_check');   
    	$data = array (
    			'update_time' => time (),
    			'price' => $_POST ['sell_price'],
    			'title' => $_POST ['title'],
    			'content' => $_POST ['content'],
    			'paramaters' => $_POST ['paramater'],
    			'models_id' => $_POST ['models_id'],
    			'is_goods'=>1
    	);
    	$result = ModelsContentModel::where('id', $id )->update ( $data );
    	if($result){	
    		//将服务写入到数据表
    		$service = [
    				'uid'=>$uid,
    				'shop_id'=>$is_shop['id'],
    				'title'=>e($_POST['title']),
    				'desc'=> $_POST ['content'],//描述
    				'cate_id'=>intval($_POST['models_id']),//分类iD
    				'type'=>2,
    				'cash'=>$_POST['sell_price'],// 售价
    				'is_recommend'=>0,
    				'created_at'=>date('Y-m-d H:i:s',time()),
    				'mid' => $_POST ['id'],//关联作品ID
    		];
    		$goodsId = GoodsModel::insertGetId ($service);
    		if ($goodsId > 0) {
    				
    			$data = array (
    					'result' => true,
    					'message' => '新增成功',
    					'goodsId'=>$goodsId
    			);
    			return json_encode ( $data );
    		}
    	}
    	$data = array (
    			'result' => true,
    			'message' => '新增失败'
    	);
    	return json_encode ( $data );
    }
    
    
    
    
    
}
