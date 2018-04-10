<?php
/**
 * ------------------------
 * Created by PhpStorm.
 * ------------------------
 *
 * ------------------------
 * Author: frank
 * Date: 16-4-12
 * Desc:
 * ------------------------
 *
 */

namespace App\Modules\Bre\Http\Controllers;

use App\Modules\Bre\Model\ChallengeRaceModel;
use App\Modules\Bre\Model\ChallengeVideoContentModel;
use App\Modules\Bre\Model\ChallengeVideoTypeModel;
use App\Modules\Bre\Model\MatchModel;
use App\Modules\Bre\Model\MatchEnrollModel;
use App\Modules\Bre\Model\MoocChapterModel;
use App\Modules\Bre\Model\MoocContentModel;
use App\Modules\Bre\Model\MooContentModel;
use App\Modules\Bre\Model\MoocOrderModel;
use App\Modules\Bre\Model\MoocPriceModel;
use App\Modules\Bre\Model\UserRandModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\FeedbackModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\OauthBindModel;
use App\Modules\User\Model\TagsModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use App\Modules\User\Model\UserTagsModel;
use Illuminate\Http\Request;
use App\Modules\Advertisement\Model\AdTargetModel;
use App\Modules\Advertisement\Model\AdModel;
use Auth;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Modules\Advertisement\Model\RePositionModel;
use App\Modules\Advertisement\Model\RecommendModel;
use App\Modules\User\Model\AuthRecordModel;
use App\Modules\User\Model\CommentModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\Shop\Models\ShopModel;
use App\Modules\User\Model\RealnameAuthModel;
use App\Modules\User\Model\DistrictModel;
use Cache;


class IndexController extends \App\Http\Controllers\IndexController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('main');
        $this->user = Auth::user ();
    }


    public function index()
    {
        $view =[];
        $adTargetID = AdTargetModel::where('code','HOME_TOP_SLIDE')->select('target_id')->first();
        if($adTargetID['target_id']){
            $adPicInfo = AdModel::where('target_id',$adTargetID['target_id'])->select('ad_file','ad_url')->get();
            if(count($adPicInfo)){
                $view['adPicInfo'] = $adPicInfo;
            }else{
                $view['adPicInfo'] = [];
            }
        }
        $adTarget = AdTargetModel::where('code','HOME_BOTTOM')->select('target_id')->first();
        if($adTarget['target_id']){
            $buttomPicInfo = AdModel::where('target_id',$adTarget['target_id'])->select('ad_file','ad_url')->get();
            if(count($adPicInfo)){
                $view['buttomPicInfo'] = $buttomPicInfo;
            }else{
                $view['buttomPicInfo'] = [];
            }
        }
        $useDetail = [];
        $user = Auth::User();
        if($user){
            $useDetail = UserDetailModel::where('uid',$user->id)->select('uid','mobile')->first();
        }
        $view['useDetail'] = $useDetail;
        return $this->theme->scope('bre.index',$view)->render();
    }

    public function breDetail($id)
    {
        echo $id;
        return $this->theme->scope('bre.index')->render();
    }

    /**
     * 服务商列表
     *
     * @param Request $request
     * @return mixed
     */
    public function getService(Request $request)
    {
        //seo配置信息
        $seoConfig = ConfigModel::getConfigByType('seo');
        if(!empty($seoConfig['seo_service']) && is_array($seoConfig['seo_service'])){
            $this->theme->setTitle($seoConfig['seo_service']['title']);
            $this->theme->set('keywords',$seoConfig['seo_service']['keywords']);
            $this->theme->set('description',$seoConfig['seo_service']['description']);
        }else{
            $this->theme->setTitle('服务商大厅');
        }

        if($request->get('employee_praise_rate')){
            $merge = $request->except('employee_praise_rate');
        }elseif($request->get('receive_task_num')){
            $merge = $request->except('receive_task_num');
        }else{
            $merge = $request->all();
        }

        if($request->get('service_name') || $request->get('keywords')){
            $searchName = $request->get('service_name') ? $request->get('service_name') : $request->get('keywords');
            $list = UserModel::select('user_detail.sign', 'users.name', 'user_detail.avatar', 'users.id','users.email_status','user_detail.employee_praise_rate','user_detail.shop_status','shop.is_recommend','shop.id as shopId')
                ->leftJoin('user_detail', 'users.id', '=', 'user_detail.uid')
                ->leftJoin('shop','user_detail.uid','=','shop.uid')->where('users.status', '<>',2)->where('users.name','like',"%".$searchName."%");
        }else{
            $list = UserModel::select('user_detail.sign', 'users.name', 'user_detail.avatar', 'users.id','users.email_status','user_detail.employee_praise_rate','user_detail.shop_status','shop.is_recommend','shop.id as shopId')
                ->leftJoin('user_detail', 'users.id', '=', 'user_detail.uid')
                ->leftJoin('shop','user_detail.uid','=','shop.uid')->where('users.status','<>', 2);
        }
        //服务商筛选
        if ($request->get('category')) {
            $category = TaskCateModel::findByPid([$request->get('category')]);

            if (empty($category)) {
                $category_data = TaskCateModel::findById($request->get('category'));
                $category = TaskCateModel::findByPid([$category_data['pid']]);
                $pid = $category_data['pid'];
                $arrTag = TagsModel::where('cate_id', $request->get('category'))->lists('id')->toArray();
                $dataUid = UserTagsModel::whereIn('tag_id', $arrTag)->lists('uid')->toArray();
                $list = $list->whereIn('users.id', $dataUid);
            } else {
                foreach ($category as $item){
                    $arrCateId[] = $item['id'];
                }
                $arrTag = TagsModel::whereIn('cate_id', $arrCateId)->lists('id')->toArray();
                $dataUid = UserTagsModel::whereIn('tag_id', $arrTag)->lists('uid')->toArray();
                $list = $list->whereIn('users.id', $dataUid);
                $pid = $request->get('category');
            }
        } else {
            //查询一级的分类,默认的是一级分类
            $category = TaskCateModel::findByPid([0]);
            $pid = 0;
        }

        if ($request->get('province')) {
            $area_data = DistrictModel::findTree(intval($request->get('province')));
            $area_pid = $request->get('province');
        } elseif ($request->get('city')) {
            $area_data = DistrictModel::findTree(intval($request->get('city')));
            $area_pid = $request->get('city');
        } elseif ($request->get('area')) {
            $area = DistrictModel::where('id', '=', intval($request->get('area')))->first();
            $area_data = DistrictModel::findTree(intval($area['upid']));
            $area_pid = $area['upid'];
        } else {
            $area_data = DistrictModel::findTree(0);
            $area_pid = 0;
        }

        //地区筛选
        if ($request->get('province')) {
            $list = $list->where('user_detail.province', intval($request->get('province')));
        }
        if ($request->get('city')) {
            $list = $list->where('user_detail.city', intval($request->get('city')));
        }
        if ($request->get('area')) {
            $list = $list->where('user_detail.area', intval($request->get('area')));
        }

        //好评数降序排列
        if($request->get('employee_praise_rate') && $request->get('employee_praise_rate') == 1){
            $list = $list->orderby('user_detail.employee_praise_rate','DESC');
        }
        /*//承接任务数量降序排列
        if($request->get('receive_task_num') && $request->get('receive_task_num') == 1){
            $list = $list->orderby('user_detail.receive_task_num','DESC');
        }*/

        $paginate = 10;
        $list = $list->orderBy('shop.is_recommend','DESC')->paginate($paginate);
        if (!empty($list->toArray()['data'])){

            foreach ($list as $k => $v){
                $arrUid[] = $v->id;
            }
        } else {
            $arrUid = 0;
        }

        //查询所有评价数组
        $comment = CommentModel::whereIn('to_uid',$arrUid)->get()->toArray();
        if(!empty($comment)){
            //根据uid重组数组
            $newComment = array_reduce($comment,function(&$newComment,$v){
                $newComment[$v['to_uid']][] = $v;
                return $newComment;
            });
            $commentCount = array();
            if(!empty($newComment)){
                foreach($newComment as $c => $d){
                    $commentCount[$c]['to_uid'] = $c;
                    $commentCount[$c]['count'] = count($d);
                }
            }
            //查询好评评价数组
            $goodComment = CommentModel::whereIn('to_uid',$arrUid)->where('type',1)->get()->toArray();
            //根据uid重组数组
            $newGoodsComment = array_reduce($goodComment,function(&$newGoodsComment,$v){
                $newGoodsComment[$v['to_uid']][] = $v;
                return $newGoodsComment;
            });
            $goodCommentCount = array();
            if(!empty($newGoodsComment)){
                foreach($newGoodsComment as $a => $b){
                    $goodCommentCount[$a]['to_uid'] = $a;
                    $goodCommentCount[$a]['count'] = count($b);
                }
            }
            //把好评数和评价数拼入$list数组
            foreach($list as $key => $value){
                foreach($goodCommentCount as $a => $b){
                    if($value['id'] == $b['to_uid']){
                        $list[$key]['good_comment_count'] = $b['count'];
                    }
                }
                foreach($commentCount as $c => $d){
                    if($value['id'] == $d['to_uid']){
                        $list[$key]['comment_count'] = $d['count'];
                    }
                }
            }
            foreach ($list as $key => $item) {

                /*//根据用户id查询店铺id
                $item->shopId = ShopModel::getShopIdByUid($item->id);*/
                //计算好评率
                if($item->comment_count > 0){
                    $item->percent = ceil($item->good_comment_count/$item->comment_count*100);
                    //$item->percent = $item->percent?$item->percent:100;
                }
                else{
                    $item->percent = 100;
                }
            }
        }else{
            foreach ($list as $key => $item) {
                //计算好评率
                $item->percent = 100;
            }
        }

        //查询行业标签
        $arrSkill = UserTagsModel::getTagsByUserId($arrUid);

        if(!empty($arrSkill) && is_array($arrSkill)){
            foreach ($arrSkill as $item){
                $arrTagId[] = $item['tag_id'];
            }

            $arrTagName = TagsModel::select('id', 'tag_name')->whereIn('id', $arrTagId)->get()->toArray();
            foreach ($arrSkill as $item){
                foreach ($arrTagName as $value){
                    if ($item['tag_id'] == $value['id']){
                        $arrUserTag[$item['uid']][] = $value['tag_name'];
                    }
                }
            }
            foreach ($list as $key => $item){
                foreach ($arrUserTag as $k => $v){
                    if ($item->id == $k){
                        $list[$key]['skill'] = $v;
                    }
                }
            }
        }

        //查询地区标签
        $preArr = UserDetailModel::join('district', 'user_detail.province', '=', 'district.id')->select('district.name','user_detail.uid')->whereIn('user_detail.uid', $arrUid)->get()->toArray();
        $cityArr = UserDetailModel::join('district', 'user_detail.city', '=', 'district.id')->select('district.name','user_detail.uid')->whereIn('user_detail.uid', $arrUid)->get()->toArray();
        foreach($list as $key => $value){
            if(!empty($preArr) && is_array($preArr)){
                foreach($preArr as $g => $h){
                    if($value['id'] == $h['uid']){
                        $list[$key]['pre'] = $h['name'];
                    }
                }
            }
            if(!empty($cityArr) && is_array($cityArr)){
                foreach($cityArr as $i => $j){
                    if($value['id'] == $j['uid']){
                        $list[$key]['city'] = $j['name'];
                    }
                }
            }
        }
        //查询服务商的认证情况
        $userAuthOne = AuthRecordModel::whereIn('uid', $arrUid)->where('status', 2)->where('auth_code','!=','realname')->get()->toArray();
        $userAuthTwo = AuthRecordModel::whereIn('uid', $arrUid)->where('status', 1)
            ->whereIn('auth_code',['realname','enterprise'])->get()->toArray();
        $userAuth = array_merge($userAuthOne,$userAuthTwo);
        $auth = array();
        if(!empty($userAuth) && is_array($userAuth)){
            foreach($userAuth as $a => $b){
                foreach($userAuth as $c => $d){
                    if($b['uid'] = $d['uid']){
                        $auth[$b['uid']][] = $d['auth_code'];
                    }
                }
            }
        }
        if(!empty($auth) && is_array($auth)){
            foreach($auth as $e => $f){
                $auth[$e]['uid'] = $e;
                if(in_array('realname',$f)){
                    $auth[$e]['realname'] = true;
                }else{
                    $auth[$e]['realname'] = false;
                }
                if(in_array('bank',$f)){
                    $auth[$e]['bank'] = true;
                }else{
                    $auth[$e]['bank'] = false;
                }
                if(in_array('alipay',$f)){
                    $auth[$e]['alipay'] = true;
                }else{
                    $auth[$e]['alipay'] = false;
                }
                if(in_array('enterprise',$f)){
                    $auth[$e]['enterprise'] = true;
                }else{
                    $auth[$e]['enterprise'] = false;
                }
            }
            foreach ($list as $key => $item) {
                //拼接认证信息
                foreach ($auth as $a => $b) {
                    if ($item->id == $b['uid']) {
                        $list[$key]['auth'] = $b;
                    }
                }
            }

         }


        //服务商底部广告
        $ad = AdTargetModel::getAdInfo('SELLERLIST_BOTTOM');

        //服务商右上方广告
        $rightAd = AdTargetModel::getAdInfo('SELLERLIST_RIGHT_TOP');

        //服务商右侧推荐位
        $reTarget = RePositionModel::where('code','SERVICE_SIDE')->where('is_open','1')->select('id','name')->first();
        if($reTarget->id){
            $recommend = RecommendModel::getRecommendInfo($reTarget->id)->select('*')->orderBy('recommend.sort','ASC')->get();
            if(count($recommend)){
                foreach($recommend as $k=>$v){
                    $comment = CommentModel::where('to_uid',$v['recommend_id'])->count();
                    $goodComment = CommentModel::where('to_uid',$v['recommend_id'])->where('type',1)->count();
                    if($comment){
                        $v['percent'] = $goodComment?$goodComment/$comment : 1;
                    }
                    else{
                        $v['percent'] = 1;
                    }
                    $recommend[$k] = $v;
                }
                $hotList = $recommend;
            }
            else{
                $hotList = [];
            }
        }


        $data = array(
            'pid' => $pid,
            'category' => $category,
            'list' => $list,
            'merge' => $merge,
            'paginate' => $paginate,
            'page' => $request->get('page') ? $request->get('page') : '',
            'skillId' => $request->get('skillId') ? $request->get('skillId') : '',
            'type' => $request->get('type') ? $request->get('type') : 0,
            'ad' => $ad,
            'rightAd' => $rightAd,
            'hotList' => $hotList,
            'targetName' => $reTarget->name,
            'area' => $area_data,
            'area_pid' => $area_pid
        );
        $this->theme->set('now_menu','/bre/service');
        return $this->theme->scope('bre.servicelist', $data)->render();
    }

    //添加投诉建议
    public function creatInfo(Request $request){
        $data = $request->except('_token');
        $validator = Validator::make($data,[
            'desc' => 'required|max:255'
        ],
        [
            'desc.required' => '请输入投诉建议',
            'desc.max'      => '投诉建议字数超过限制'


        ]);
        $error = $validator->errors()->all();
        if(count($error)){
            return redirect()->to(\CommonClass::homePage())->with(['error'=>$validator->errors()->first()]);
        }
        if($data['phone']){
            $validator = Validator::make($data,[
                'phone' => 'mobile_phone'
            ],
            [
                'phone.mobile_phone' => '请输入正确的手机格式'


            ]);

            $error = $validator->errors()->all();
            if(count($error)){
                return redirect()->to(\CommonClass::homePage())->with(['error'=>$validator->errors()->first()]);
            }
        }
        $newdata = [
            'desc'          => $data['desc'],
            'created_time'  => date('Y-m-d h:i:s',time())
        ];
        if($data['uid']){
            $newdata['uid'] = $data['uid'];
        }
        if($data['phone']){
            $newdata['phone'] = $data['phone'];
        }
        $res = FeedbackModel::create($newdata);
        if($res){
            return redirect()->to(\CommonClass::homePage())->with(['message'=>'投诉建议提交成功！']);
        }
        return redirect()->to(\CommonClass::homePage())->with(['error'=>'投诉建议提交失败！']);
    }


    /*商城*/
    public function shop(Request $request)
    {
        $this->theme->setTitle('威客商城');
        $data = $request->all();
        $workInfo = $uid = [];
        //作品和服务的信息
        $goodsInfo = GoodsModel::where('status',1)
            ->select('id','uid','shop_id','title','type','cash','cover','sales_num','good_comment');
        if(isset($data['type'])){
            $goodsInfo = $goodsInfo->where('type',$data['type']);
        }
        if(isset($data['title'])){
            $goodsInfo = $goodsInfo->where('title','like','%'.$data['title'].'%');
        }
        if(isset($data['desc'])){
            switch($data['desc']){
                case 'cash':
                    $goodsInfo = $goodsInfo->orderBy('cash','desc');
                    break;
                case 'sales_num':
                    $goodsInfo = $goodsInfo->orderBy('sales_num','desc');
                    break;
                case 'good_comment':
                    $goodsInfo = $goodsInfo->orderBy('good_comment','desc');
                    break;
            }

        }

        $goodsInfo = $goodsInfo->where(function($goodsInfo){
            $goodsInfo->where('is_recommend',0)
                ->orWhere(function($goodsInfo){
                    $goodsInfo->where('is_recommend',1)
                        ->where('recommend_end','>',date('Y-m-d H:i:s',time()));
                });
        })

            ->orderBy('is_recommend','desc')->orderBy('created_at','desc')->paginate(16);
        foreach($goodsInfo as $k => $v){
            $uid[] = $v->uid;
        }
        $cityInfo = ShopModel::join('district', 'shop.city', '=', 'district.id')
            ->select('shop.uid','district.name')->whereIn('shop.uid', $uid)->get();
        if(!empty($cityInfo)){
            foreach($cityInfo as $ck => $cv){
                $cityInfo[$cv->uid] = $cv->name;
            }
            foreach($goodsInfo as $gk => $gv){
                $goodsInfo[$gk]->addr = $cityInfo[$gv->uid];
            }
        }
        //人气商品信息
        $workInfo = GoodsModel::where(['status' => 1,'type' => 1])
            ->select('id','title','cash','cover','shop_id')
            ->orderBy('sales_num','desc')
            ->limit(5)->get()->toArray();
        $domain = \CommonClass::getDomain();
        $data = [
            'goodsInfo' => $goodsInfo,
            'domain' => $domain,
            'workInfo' => $workInfo,
            'merge' => $data,
            'uid' => Auth::User() ? Auth::User()['id'] : 0
        ];
        $this->theme->set('now_menu','/bre/shop');
        return $this->theme->scope('bre.shoplist',$data)->render();
    }


    /**
     * 商城发布商品链接切换
     *
     * @param Request $request
     * @return json
     */
    public function changeUrl(Request $request){
        $url = '';
        $uid = intval($request->get('uid'));
        if($uid){
            $type = $request->get('type');
            //判断用户是否实名认证
            $realName = RealnameAuthModel::where('uid',$uid)->where('status',1)->first();
            if(empty($realName)){
                $url = '/user/userShopBefore';
            }else{
                $shopInfo = ShopModel::where('uid',$uid)->first();
                if(empty($shopInfo)){
                    $url = '/user/myShopHint';
                }else{
                    if($type == '2'){
                        $url = '/user/serviceCreate';
                    }elseif($type == '1'){
                        $url = '/user/pubGoods';
                    }
                }
            }

        }else{
            $url = '/login';
        }

        return $url;
    }
    
    
    /**
     * 轻课堂
     *
     * @param Request $request
     * @return json
     */
    public function study(Request $request){
    	
    	$this->initTheme('study');
    	$this->theme->setTitle('轻课堂');
        $view = [
            'randnum' => 0, //0表示红包不存在
            'is_show' => 0, //0表示红包不显示
        ];
    	return $this->theme->scope('study.index',$view)->render();
    	
    }


    //用户同意之后就获取code  通过获取code可以获取一切东西了  机智如我
    public function getCode(){
//        header("Content-type:text/html;charset=utf-8");
        //获取accse_token
        $oauthConfig = ConfigModel::getOauthConfig('wechat_api');
        $code = $_GET["code"];
        //用code获取access_yoken
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$oauthConfig['appId']."&secret=".$oauthConfig['appSecret']."&code=".$code."&grant_type=authorization_code";
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

        //查询微信是否有绑定
        $oauth = OauthBindModel::where('oauth_id','=',$res['unionid'])->first();
        //查询登录的用户是否已经存在红包
        $randNum = UserRandModel::select('randnum')->where('uid', $this->user['id'])->first();

        if($oauth){ //判断微信是否绑定--绑定
            /**
             * 在有全部一致的情况下，查看是否有红包
             * 1，创建一个微信红包（并写入创建时间，防止作弊好查账）
             * 3，创建一条资金明细记录（让客户知道红包的去向）
             * 4，重写页面上的红包金额
             */
            if($randNum == null){
                $rand = UserRandModel::create(['randnum'=>$this->randomFloat(0,99),'uid'=>$this->user['id'],'created_at' => date('Y-m-d H:i:s')]);
                $redNum = $rand['randnum'];
                $financial = [
                    'action'      => 12,
                    'pay_type'    => 12,
                    'pay_account' => 'admin',
                    'cash'        => $rand['randnum'],
                    'uid'         => $this->user['id'],
                ];
                FinancialModel::createOne($financial);
            }else{
                $redNum = $randNum['randnum'];
            }
        }else{
            /**
             * 在没有绑定微信的情况下，
             * 1,查询登录用户是否有绑定扫描用户的微信
             */
            $uidBind = OauthBindModel::where('uid',$this->user['id'])->first();
            if($uidBind == null){
                /**
                 * 在没有绑定微信的情况下，
                 * 1，创建一个微信红包（并写入创建时间，防止作弊好查账）
                 * 2, 用微信绑定和用户绑定
                 * 3，创建一条资金明细记录（让客户知道红包的去向）
                 * 4，重写页面上的红包金额
                 */
                $randNum = UserRandModel::create(['randnum'=>$this->randomFloat(0,99),'uid'=>$this->user['id'],'created_at' => date('Y-m-d H:i:s')]);
                $oauthData = [
                    'oauth_id'       => $res['unionid'],
                    'oauth_nickname' => $res['nickname'],
                    'oauth_type'     => 2,
                    'uid'            => $this->user['id'],
                    'created_at'     => date('Y-m-d H:i:s')
                ];
                OauthBindModel::create($oauthData);
                $financial = [
                    'action'      => 12,
                    'pay_type'    => 12,
                    'pay_account' => 'admin',
                    'cash'        => $randNum['randnum'],
                    'uid'         => $this->user['id'],
                ];
                FinancialModel::createOne($financial);
            }else{
                /**
                 * 在有绑定微信的情况下，查看是否有红包
                 * 1，创建一个微信红包（并写入创建时间，防止作弊好查账）
                 * 3，创建一条资金明细记录（让客户知道红包的去向）
                 * 4，重写页面上的红包金额
                 */
                $randNum = UserRandModel::select('randnum')->where('uid', $this->user['id'])->first();
                if($randNum == null){
                    $rand = UserRandModel::create(['randnum'=>$this->randomFloat(0,99),'uid'=>$this->user['id'],'created_at' => date('Y-m-d H:i:s')]);
                    $redNum = $rand['randnum'];
                    $financial = [
                        'action'      => 12,
                        'pay_type'    => 12,
                        'pay_account' => 'admin',
                        'cash'        => $rand['randnum'],
                        'uid'         => $this->user['id'],
                    ];
                    FinancialModel::createOne($financial);
                }else{
                    $redNum = $randNum['randnum'];
                }
            }
        }
        $view = [
            'randnum' => $redNum,
            'is_show' => 1,
        ];
        return $this->theme->scope('study.index',$view)->render();
    }

    /**
     * @param $url
     * @param null $data
     * @return mixed
     */
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
     * 获取随机的金额
     * @param int $min
     * @param int $max
     * @return string
     */
    function randomFloat($min = 0, $max = 10){
        $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return sprintf("%.2f", $num);
    }


    /**
     * 课程目录详情页
     * @return mixed
     *
     */
    public function studyCase($id){
        $this->initTheme('study');
        $mPriceData = MoocPriceModel::find($id);
        $this->theme->setTitle($mPriceData['type']);
        $contentData = MoocContentModel::where('mooc_content.type_id',$id)->get();
        if(count($contentData) == 0)  return redirect ( '/' );
        $contentCount = MoocChapterModel::where('mooc_content.type_id',$id)->join('mooc_content','mooc_content.id','=','mooc_chapter.type_id')->count();
        $orderData     = OrderModel::getUserIsBuy( $id, $this->user['id'], 'study', 1);
        $countOrder    = OrderModel::getUserIsBuy( $id, null, 'study', 1);
        $countOrderNum = count($countOrder)+1000;
        $view = [
            'is_buy'      => isset($orderData) ? 1 : 0 ,
            'contentData' => $contentData,
            'contentCount'=> $contentCount,
            'price'       => $mPriceData['price'],
            'content'     => $mPriceData['content'],
            'title'       => $mPriceData['type'],
            'id'          => $id,
            'buy_num'     => $countOrderNum
        ];
        return $this->theme->scope('study.case',$view)->render();
    }


    public function getVideo($id){
        if($id){
            if(Auth::check()){
                $num = MoocPriceModel::count();
                if(intval($id) <= $num && intval($id) != 0 ){
                    $orderData = OrderModel::getUserIsBuy( $id, $this->user['id'], 'study', 1);
                    if(!empty($orderData)){
        $this->initTheme('study');
        $this->theme->setTitle('课程视频');
        $contentData = MoocContentModel::where('mooc_content.type_id',$id)->get();
        foreach($contentData as $k => $v) $arr[] = MoocChapterModel::where('mooc_chapter.type_id',$v['id'])->get();
        $type = MoocPriceModel::where('id',$id)->value('type');
        $url = MoocChapterModel::where('mooc_content.type_id',$id)
            ->join('mooc_content','mooc_content.id','=','mooc_chapter.type_id')
            ->pluck('url');
        $view =[
            'arr'         => $arr,
            'contentData' => $contentData,
            'type'       => $type,
            'url'         => $url,
            'id'          => $id ,
        ];
        return $this->theme->scope('study.video',$view)->render();
                    }else{
                        return redirect()->to('member/bounty/'.$id.'/study/0');
                    }
                }else{
                    abort(404);
                }
            }else{
                return redirect()->to('login')->with('请先登录！！！');
            }
        }

    }

    /**
     * 挑战大赛首页
     * @param Request $request
     * @return mixed
     */
    public function challenge(){
        $this->initTheme('challenge');
        $this->theme->setTitle('挑战大赛');
        return $this->theme->scope('challenge.index')->render();

//        $this->initTheme('challenge');
//        $this->theme->setTitle('挑战大赛');
//
//        $data = ModelsContentModel::select('user_detail.avatar','user_detail.nickname','models_content.id','models_content.title','models_content.cover_img','models_content.uid')
//            ->where ( 'models_content.enroll_status', '=',1)
//            ->join("user_detail","user_detail.uid","=","models_content.uid")
//            ->get();
//        //获取投票数
//        $num = [];
//        foreach($data as $v){
//            $num[] = array(
//                'vote_num' => MatchModel::where('models_id','=',$v['id'])->count(),
//            );
//        };
//        $view = [
//            'content'  => $data,
//            'num'      => $num,
//        ];
//        return $this->theme->scope('challenge.index', $view)->render();

    }


    /**
     * 获取参赛报名
     * @return string
     *
     */
    public function match(){
        $address = $_POST ['address'] ; // 参赛地址
        if (! $address) {
            $data = array (
                'result' => false,
                'message' => '参数错误！'
            );
            return json_encode ( $data );
        }
        //获取参赛作品的ID
        $id = mb_substr(strstr($address,'-'),1,5);
        //获取参赛作品对应用户的UID
        $uid = DB::table('models_content')->where('id','=',$id)->pluck('uid');
        if($uid != $this->user['id']){
            $data = array (
                'result' => false,
                'message' => '必须上传本人作品！'
            );
            return json_encode( $data );
        }else{
        //查询参赛作品的参赛状态
        $status = DB::table('models_content')->where('id','=',$id)->pluck('enroll_status');
        //判断参赛，如果为1，则返回，告知参赛者已经参赛
        if($status == 1){
            $data = array (
                'result' => false,
                'message' => '此作品已经属于参赛状态！'
            );
            return json_encode ( $data );
        }elseif($status == null){
            $data = array (
                'result' => false,
                'message' => '此作品不存在！不可以参赛'
            );
            return json_encode ( $data );
        }else{
            //将状态更新为参赛状态
            ModelsContentModel::where('id','=',$id)->update(['enroll_status' => 1]);
            //新增一条参赛纪录，如果存在则不增加
            MatchEnrollModel::firstOrCreate(['address' => $address]);
            $data = array (
                'result' => true,
                'message' => '参赛成功！静候佳音！！！'
            );
            return json_encode ( $data );
        }
    }
    }

    /**
     * 获取投票数
     * @return string
     *
     */
    public function voteNum(){
        $models_id = $_POST['models_id'];
        $uid = $this->user['id'];
        $is_vote = MatchModel::where('uid','=',$uid)->where('models_id','=',$models_id)->first();
        if ($is_vote) { //存在数据
            $data = array(
                'result' => false,
                'message' => '您已经投过票了哦！！！'
            );
            return json_encode($data);
        }
        $postData = array (
            'models_id' => $models_id,
            'uid'       => $uid,
            'vote_num'  => 1
        );
        $insert_id = MatchModel::insertGetId ( $postData );
        if ($insert_id > 0) {
            $data = array (
                'result' => true,
                'message' => '投票成功！'
            );
            return json_encode ( $data );
        }
        $data = array (
            'result' => false,
            'message' => '投票失败！'
        );
        return json_encode ( $data );
    }

    /**
     * Use:测试用的
     * @return mixed
     */
	public function gif(){
        $this->initTheme('study');
        $this->theme->setTitle('让虚拟照进现实');
        return $this->theme->scope('study.gif')->render();
    }


    public function race_hx(Request $request){
        $this->initTheme('challenge');
        $this->theme->setTitle('海峡工业设计大赛');
        $time = date('Y-m-d H:i:s',time());
        $contest_time = ChallengeRaceModel::where('id',2)->pluck('data');
        if($time >= $contest_time){
            $data = ModelsContentModel::select('user_detail.avatar','user_detail.nickname','models_content.id','models_content.title','models_content.cover_img','models_content.uid')
                ->where ( 'models_content.enroll_status', '=',1) //报名状态：0为未报名，1为报名
                ->join("user_detail","user_detail.uid","=","models_content.uid")
                ->get();
            //获取投票数
            $num = [];
            foreach($data as $v){
                $num[] = array(
                    'vote_num' => MatchModel::where('models_id','=',$v['id'])->count(),
                );
            };
        }
        //判断是否登录
        $login = isset(Auth::user()->id) ? 1: 0 ;
        $view = [
            'content'  => isset($data) ? $data : null,
            'num'      => isset($num) ? $num : '',
            'login'    => $login,
        ];
        return $this->theme->scope('challenge.race_hx', $view)->render();
    }


    public function challengeCase($id){
        $this->initTheme('challenge');
        $typeData = ChallengeVideoTypeModel::find($id);
        $this->theme->setTitle($typeData['type_title']);
        $contentData = ChallengeVideoContentModel::where('type_id',$id)->get();
        if(count($contentData) == 0)  return redirect ( '/' );
        $view =[
            'contentData' => $contentData,
            'typeData'   => $typeData,
            'title'       => $typeData['type_title'],
            'id'          => $id
        ];
        return $this->theme->scope('challenge.case',$view)->render();
    }

    public function challengeVideo($id,$type){
        $this->initTheme('challenge');
        $this->theme->setTitle('教学视频');
        $typeData = ChallengeVideoTypeModel::where('id',$id)->get();
        $contentData = ChallengeVideoContentModel::where('type_id',$id)->get();
        $pcType = ChallengeVideoContentModel::where('id',$id)->pluck('type_title');
        $url = ChallengeVideoContentModel::where(['type_id' => $id,'type_chapter' => $type])->pluck('url');
        $login = isset(Auth::user()->id) ? 1: 0 ;
        $view =[
            'typeData'    => $typeData,
            'contentData' => $contentData,
            'pcType'      => $pcType,
            'url'         => $url,
            'chapterCount' => count($contentData),
            'login'       => $login ,
            'id'          => $id,
            'type'        => $type
        ];
        return $this->theme->scope('challenge.video',$view)->render();
    }
}