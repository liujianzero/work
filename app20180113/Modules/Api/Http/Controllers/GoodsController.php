<?php
/**
 * Created by PhpStorm.
 * User: KEKE-1003
 * Date: 2016/10/10
 * Time: 10:54
 */
namespace App\Modules\Api\Http\Controllers;

use App\Http\Requests;
use App\Modules\Task\Model\TaskCateModel;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiBaseController;
use Validator;
use Illuminate\Support\Facades\Crypt;
use App\Modules\Employ\Models\UnionAttachmentModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\Shop\Models\ShopModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\Shop\Models\ShopFocusModel;
use App\Modules\Task\Model\ServiceModel;
use DB;

class GoodsController extends ApiBaseController
{
    /**
     * 是否可以发布商品
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function isPub(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($tokenInfo['uid']);

        if($isOpenShop != 1){
            if($isOpenShop == 2){
                return $this->formateResponse(1002,'您的店铺已关闭');
            }else{
                return $this->formateResponse(1003,'您的店铺还没设置');
            }
        }
        return $this->formateResponse(1000,'success');
    }

    /**
     * 附件上传
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function fileUpload(Request $request)
    {
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $file = $request->file('file');
        //将文件上传的数据存入到attachment表中
        $attachment = \FileClass::uploadFile($file,'user');
        $attachment = json_decode($attachment, true);
        //判断文件是否上传
        if($attachment['code']!=200)
        {
            return $this->formateResponse(2001,$attachment['message']);
        }
        $attachment_data = array_add($attachment['data'], 'status', 1);
        $attachment_data['created_at'] = date('Y-m-d H:i:s', time());
        $attachment_data['user_id'] = $tokenInfo['uid'];
        //将记录写入attachment表中
        $result = AttachmentModel::create($attachment_data);
        $data = AttachmentModel::where('id',$result['id'])->first();
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        if(isset($data)){
            $data->url = $data->url?$domain->rule.'/'.$data->url:$data->url;
        }
        if($result){
            return $this->formateResponse(1000,'success',$data);
        }else{
            return $this->formateResponse(2002,'文件上传失败');
        }
    }

    /**
     * 发布作品
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pubGoods(Request $request){
        $validator = Validator::make($request->all(),[
            'title'        => 'required|string|max:50',
            'desc'         => 'required|string',
            'first_cate'   => 'required',
            'second_cate'  => 'required',
            'cash'         => 'required|numeric',
            'cover'        => 'required'

        ],[
            'title.required'       => '请输入作品标题',
            'title.string'         => '请输入正确的标题格式',
            'title.max'            => '标题长度不得超过50个字符',

            'desc.required'        => '请输入作品描述',
            'desc.string'          => '请输入描述正确的格式',

            'first_cate.required'  => '请选择作品分类',
            'second_cate.required' => '请选择作品子分类',

            'cash.required'        => '请输入作品金额',
            'cash.numeric'         => '请输入正确的金额格式',

            'cover.required'       => '请上传作品封面'
        ]);
        //获取验证错误信息
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,'输入信息有误',$error);
        }
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        //根据用户id获取店铺id
        $shopId = ShopModel::getShopIdByUid($tokenInfo['uid']);

        $data = $request->all();
        $data['cate_id'] = $data['second_cate'];

        //查询商品最小金额
        $minPriceArr = ConfigModel::getConfigByAlias('min_price');
        if(!empty($minPriceArr)){
            $minPrice = $minPriceArr->rule;
        }else{
            $minPrice = 0;
        }
        if($minPrice > 0 && $data['cash'] < $minPrice){
            return $this->formateResponse(1004,'作品金额不能小于最低配置值');
        }
        isset($data['is_recommend']) ? $is_service = true : $is_service = false;
        //处理封面
        $cover = $request->file('cover');
        $result = \FileClass::uploadFile($cover,'sys');
        if ($result){
            $result = json_decode($result, true);
            $data['cover'] = $result['data']['url'];
        }
        //判断配置项商品上架是否需要审核
        $config = ConfigModel::getConfigByAlias('goods_check');
        if(!empty($config) && $config->rule == 1){
            $goodsCheck = 0;
        }else{
            $goodsCheck = 1;
        }
        $data['status'] = $goodsCheck;
        $data['is_recommend'] = 0;
        $data['uid'] = $tokenInfo['uid'];
        $data['shop_id'] = $shopId;
        $res = DB::transaction(function() use($data){
            $goods = GoodsModel::create($data);
            //处理附件
            //$data['file_id'] = json_decode($data['file_id'],true);//[{"0":3516}]
            if (!empty($data['file_id'])){
                //查询用户的附件记录，排除掉用户删除的附件记录
                $file_able_ids = AttachmentModel::fileAble($data['file_id']);
                $data['file_id'] = array_flatten($file_able_ids);
                $arrAttachment = array();
                foreach ($data['file_id'] as $v){
                    $arrAttachment[] = [
                        'object_id' => $goods->id,
                        'object_type' => 4,
                        'attachment_id' => $v,
                        'created_at' => date('Y-m-d H:i:s', time())
                    ];
                }
                UnionAttachmentModel::insert($arrAttachment);
            }
            return $goods;
        });
        if(!isset($res)){
            return $this->formateResponse(1005,'作品发布失败');
        }
        return $this->formateResponse(1000,'作品发布成功',$res);

    }

    /**
     * 发布服务
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pubService(Request $request){
        $validator = Validator::make($request->all(),[
            'title'        => 'required|string|max:50',
            'desc'         => 'required|string',
            'first_cate'   => 'required',
            'second_cate'  => 'required',
            'cash'         => 'required|numeric',
            'cover'        => 'required'

        ],[
            'title.required'       => '请输入服务标题',
            'title.string'         => '请输入正确的标题格式',
            'title.max'            => '标题长度不得超过50个字符',

            'desc.required'        => '请输入服务描述',
            'desc.string'          => '请输入描述正确的格式',

            'first_cate.required'  => '请选择服务分类',
            'second_cate.required' => '请选择服务子分类',

            'cash.required'        => '请输入服务金额',
            'cash.numeric'         => '请输入正确的金额格式',
            'cover.required'       => '请上传服务封面'
        ]);
        //获取验证错误信息
        $error = $validator->errors()->all();
        if(count($error)){
            return $this->formateResponse(1001,'输入信息有误',$error);
        }
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        //根据用户id获取店铺id
        $shopId = ShopModel::getShopIdByUid($tokenInfo['uid']);

        $data = $request->all();
        $data['cate_id'] = $data['second_cate'];

        //查询服务最小金额
        $minPriceArr = \CommonClass::getConfig('employ_bounty_min_limit');
        if(!$minPriceArr){
            $minPrice = $minPriceArr;
        }else{
            $minPrice = 0;
        }
        if($minPrice > 0 && $data['cash'] < $minPrice){
            return $this->formateResponse(1004,'服务金额不能小于最低配置值');
        }
        //处理封面
        $cover = $request->file('cover');
        $result = \FileClass::uploadFile($cover,'sys');
        if ($result){
            $result = json_decode($result, true);
            $data['cover'] = $result['data']['url'];
        }
        //判断配置项商品上架是否需要审核
        $config = ConfigModel::getConfigByAlias('service_check');
        if(!empty($config) && $config->rule == 1){
            $goodsCheck = 0;
        }else{
            $goodsCheck = 1;
        }
        $data['status'] = $goodsCheck;
        $data['is_recommend'] = 0;
        $data['uid'] = $tokenInfo['uid'];
        $data['shop_id'] = $shopId;
        $goods = GoodsModel::create($data);
        if(!isset($goods)){
            return $this->formateResponse(1005,'服务发布失败');
        }
        return $this->formateResponse(1000,'服务发布成功',$goods);

    }


    /**
     * 我收藏的店铺列表及筛选
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myCollectShop(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $merge = $request->all();
        $collectArr = ShopFocusModel::where('uid',$tokenInfo['uid'])->orderby('created_at','DESC')->get()->toArray();
        $shopList = array();
        if(!empty($collectArr))
        {
            $shopIds = array_unique(array_pluck($collectArr,'shop_id'));
            $shopList = ShopModel::getShopListByShopIds($shopIds,$merge)->toArray();
            if($shopList['total']){
                $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
                foreach($shopList['data'] as $k => $v){
                    $shopList['data'][$k]['shop_pic'] = $v['shop_pic']?$domain->rule.'/'.$v['shop_pic']:$v['shop_pic'];
                    $shopList['data'][$k]['employ_num'] = count($v['employ_data']);
                    $shopList['data'][$k]['address'] = $v['province_name'].$v['city_name'];
                    $shopList['data'][$k] = array_except($shopList['data'][$k],['employ_data','province_name','city_name']);
                    $shopList['data'][$k]['shop_desc'] = htmlspecialchars_decode($v['shop_desc']);
                }
            }
        }
        return $this->formateResponse(1000,'获取我收藏的店铺列表信息成功',$shopList);

    }


    /**
     * 获取作品平台抽佣
     *
     * @return \Illuminate\Http\Response
     */
    public function workRateInfo()
    {
        $workRate = ConfigModel::where('alias','trade_rate')->first();
        $percent = $workRate->rule;
        return $this->formateResponse(1000,'获取作品平台抽佣信息成功',['percent' => $percent]);

    }

    /**
     * 获取推荐作品配置信息
     *
     * @return \Illuminate\Http\Response
     */
    public function workRecommendInfo(){
        $configInfo = [];
        //查询是否开启推荐商品增值工具
        $isOpenArr = ServiceModel::where(['identify' => 'ZUOPINTUIJIAN','type' => 2,'status' => 1])->first();
        if(!empty($isOpenArr)){
            $configInfo['isOpen'] = 1;
            $configInfo['price'] = $isOpenArr->price;
            //查询推荐增值服务有效期
            $unitAbout = ConfigModel::getConfigByAlias('recommend_goods_unit');
            $configInfo['unit'] = $unitAbout->rule;
        }else{
            $configInfo['isOpen'] = 0;
        }
        return $this->formateResponse(1000,'获取推荐作品开启信息成功',['configInfo' => $configInfo]);


    }


    /**
     * 获取服务平台抽佣
     *
     * @return \Illuminate\Http\Response
     */
    public function serviceRateInfo()
    {
        $serviceRate = ConfigModel::where('alias','employ_percentage')->first();
        $percent = $serviceRate->rule;
        return $this->formateResponse(1000,'获取服务平台抽佣信息成功',['percent' => $percent]);

    }


    /**
     * 获取推荐服务开启信息
     *
     * @return \Illuminate\Http\Response
     */
    public function serviceRecommendInfo(){
        $configInfo = [];
        //查询发布商品推荐服务上否开启
        $service = ServiceModel::where(['status' => 1,'type' => 2,'identify' => 'FUWUTUIJIAN'])->first();
        if(!empty($service)){
            $configInfo['isOpen'] = 1;
            $configInfo['price'] = $service->price;
            $configInfo['unit'] = \CommonClass::getConfig('recommend_service_unit');
        }else{
            $configInfo['isOpen'] = 0;
        }

        return $this->formateResponse(1000,'获取推荐服务开启信息成功',['configInfo' => $configInfo]);


    }


    /**
     * 我发布的作品
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myWorkList(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $uid = $tokenInfo['uid'];
        $merge = $request->all();
        $goodsInfo = GoodsModel::getGoodsListByUid($uid,$merge)->toArray();
        if($goodsInfo['total']){
            $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
            foreach($goodsInfo['data'] as $k=>$v){
               $goodsInfo['data'][$k]['desc'] = htmlspecialchars_decode($v['desc']);
               $goodsInfo['data'][$k]['cover'] = $v['cover']?$domain->rule.'/'.$v['cover']:$v['cover'];
            }
        }
        return $this->formateResponse(1000,'获取我发布的作品成功',$goodsInfo);
    }


    /**
     * 我发布的服务
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myOfferList(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $all_cate = TaskCateModel::findAllCache();
        $all_cate = \CommonClass::keyBy($all_cate,'id');
        $service = GoodsModel::select('*')->where('uid',$tokenInfo['uid'])->where('type',2)->where('is_delete',0);
        //状态筛选
        if($request->get('status'))
        {
            switch($request->get('status')){
                case 1://待审核
                    $status = 0;
                    $service = $service->where('status',$status);
                    break;
                case 2://售卖中
                    $status = 1;
                    $service = $service->where('status',$status);
                    break;
                case 3://下架
                    $status = 2;
                    $service = $service->where('status',$status);
                    break;
                case 4: //审核失败
                    $status = 3;
                    $service = $service->where('status',$status);
                    break;

            }
        }
        //时间筛选
        if($request->get('sometime'))
        {
            $time = date('Y-m-d H:i:s',strtotime("-".intval($request->get('sometime'))." month"));
            $service->where('created_at','>',$time);
        }

        $service = $service->orderBy('created_at','DESC')
            ->paginate(5)->toArray();

        if($service['total']){
            $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
            foreach($service['data'] as $k=>$v){
                $service['data'][$k]['name'] = $all_cate[$v['cate_id']]['name'];
                $service['data'][$k]['cover'] = $v['cover']?$domain->rule.'/'.$v['cover']:$v['cover'];
                $service['data'][$k]['desc'] = htmlspecialchars_decode($v['desc']);
            }
        }
        return $this->formateResponse(1000,'获取我发布的服务信息成功',$service);

    }

}
