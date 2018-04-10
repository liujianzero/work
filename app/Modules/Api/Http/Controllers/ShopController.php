<?php
/**
 * Created by PhpStorm.
 * User: KEKE-1003
 * Date: 2016/10/19
 * Time: 10:08
 */
namespace App\Modules\Api\Http\Controllers;

use App\Http\Requests;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\Shop\Models\GoodsCommentModel;
use App\Modules\Shop\Models\GoodsModel;
use App\Modules\Shop\Models\ShopModel;
use App\Modules\Shop\Models\ShopTagsModel;
use App\Modules\Task\Model\SuccessCaseModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\SkillTagsModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiBaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use App\Modules\Shop\Models\ShopFocusModel;

class ShopController extends ApiBaseController
{
    /**
     * 收藏店铺
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function collectShop(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $shopId = $request->get('shop_id');
        $uid = $tokenInfo['uid'];
        $shopInfo = ShopModel::where(['uid' => $uid,'id' => $shopId,'status' => 1])->first();
        if(!empty($shopInfo)){
            return $this->formateResponse(1007,'不能收藏自己的店铺');
        }
        $data = [
            'uid' => $uid,
            'shop_id' => $shopId,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $res = ShopFocusModel::create($data);
        if($res){
            return $this->formateResponse(1000,'收藏成功',$res);
        }else{
            return $this->formateResponse(1008,'收藏失败');
        }
    }


    /**
     * 取消收藏店铺
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function cancelCollect(Request $request){
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $shopId = $request->get('shop_id');
        $uid = $tokenInfo['uid'];
        $res = ShopFocusModel::where(['uid' => $uid,'shop_id' => $shopId])->delete();
        if($res){
            return $this->formateResponse(1000,'取消成功');
        }else{
            return $this->formateResponse(1009,'取消失败');
        }
    }


    /**
     * 查看店铺被收藏的状态
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function collectStatus(Request $request){
        if(!$request->get('token')){
            $status = 0;
        }else{
            $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
            $uid = $tokenInfo['uid'];
            $shopId = $request->get('shop_id');
            $shopFocusInfo = ShopFocusModel::where(['uid' => $uid,'shop_id' => $shopId])->first();
            if(empty($shopFocusInfo)){
                $status = 0;
            }else{
                $status = 1;
            }
        }
        return $this->formateResponse(1000,'获取店铺被收藏状态成功',['status' => $status]);
    }


    /**
     * 查看是否可以进入雇佣页面
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function isEmploy(Request $request){
        if(!$request->get('token')){
            return $this->formateResponse(1010,'请先登录');
        }
        $tokenInfo = Crypt::decrypt(urldecode($request->get('token')));
        $uid = $tokenInfo['uid'];
        if($uid == $request->get('id')){
            return $this->formateResponse(1011,'您不能雇佣你自己');
        }
        return $this->formateResponse(1000,'success');
    }

    /**
     * 获取店铺信息
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function shopInfo(Request $request){
        $shopId = intval($request->get('shop_id'));
        $shopInfo = ShopModel::where(['id' => $shopId,'status' => 1])->first();
        if(empty($shopInfo)){
            return $this->formateResponse(1012,'传送数据错误');
        }
        $userInfo = UserModel::where('id',$shopInfo->uid)->where('status','<>',2)->select('name')->first();
        if(empty($userInfo)){
            return $this->formateResponse(1013,'用户id不存在');
        }
        $userDetail = UserDetailModel::where('uid',$shopInfo->uid)->select('avatar')->first();
        if(empty($userDetail)){
            return $this->formateResponse(1014,'用户信息不存在');
        }
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        $shopInfo->name = $userInfo->name;
        $shopInfo->avatar = $userDetail->avatar?$domain->rule.'/'.$userDetail->avatar:$userDetail->avatar;
        $shopInfo->shop_desc = htmlspecialchars_decode($shopInfo->shop_desc);
        $shopInfo->shop_pic = $shopInfo->shop_pic?$domain->rule.'/'.$shopInfo->shop_pic:$shopInfo->shop_pic;
        $shopInfo->tags = [];
        $shopTags = ShopTagsModel::where('shop_id',$shopId)->select('tag_id')->get()->toArray();
        if(!empty($shopTags)){
            $tagIds = array_unique(array_flatten($shopTags));
            $tags = SkillTagsModel::whereIn('id',$tagIds)->select('tag_name')->get()->toArray();
            if(!empty($tags)){
                $shopInfo->tags = array_unique(array_flatten($tags));
            }
        }
        //查询地址
        if($shopInfo->province){
            $province = DistrictModel::where('id',$shopInfo->province)->select('id','name')->first();
            $provinceName = $province->name;
        }else{
            $provinceName = '';
        }
        if($shopInfo->city){
            $city = DistrictModel::where('id',$shopInfo->city)->select('id','name')->first();
            $cityName = $city->name;
        }else{
            $cityName = '';
        }
        $shopInfo->address = $provinceName.$cityName;
        //上架作品
        $shopInfo->workNum = GoodsModel::where(['shop_id' => $shopId,'status' => 1,'type' => 1])->count();
        //上架服务
        $shopInfo->serviceNum = GoodsModel::where(['shop_id' => $shopId,'status' => 1,'type' => 2])->count();
        return $this->formateResponse(1000,'获取威客店铺信息成功',$shopInfo);


    }


    /**
     * 获取店铺商品信息
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function goodsList(Request $request){
        $type = $request->get('type');
        $shopId = $request->get('shop_id');
        $shopInfo = ShopModel::where(['id' => $shopId,'status' => 1])->select('shop_name','shop_pic')->first();
        if(empty($shopInfo)){
            return $this->formateResponse(1015,'传送参数错误');
        }
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        $shop = [];
        $shop['shop_name'] = $shopInfo->shop_name;
        $shop['shop_pic'] = $shopInfo->shop_pic?$domain->rule.'/'.$shopInfo->shop_pic:$shopInfo->shop_pic;
        if($type){
            $goodsList = GoodsModel::where(['shop_id' => $shopId,'type' => $type,'status' => 1,'is_delete' => 0])
                ->orderBy('created_at','desc')
                ->select('id','title','cash','cover')
                ->paginate(4)
                ->toArray();
        }else{
            $goodsList = GoodsModel::where(['shop_id' => $shopId,'type' => 1,'status' => 1,'is_delete' => 0])
                ->orderBy('created_at','desc')
                ->select('id','title','cash','cover')
                ->paginate(4)
                ->toArray();
        }
        if($goodsList['total']){
           foreach($goodsList['data'] as $k=>$v){
               $goodsList['data'][$k]['cover'] = $v['cover']?$domain->rule.'/'.$v['cover']:$v['cover'];
           }
        }
        $shop['goods'] = $goodsList;
        return $this->formateResponse(1000,'获取商品信息成功',$shop);

    }


    /**
     * 获取店铺成功案例信息
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function successList(Request $request){
        $shopId = $request->get('shop_id');
        $shopInfo = ShopModel::where(['id' => $shopId,'status' => 1])->select('shop_name','shop_pic','uid')->first();
        if(empty($shopInfo)){
            return $this->formateResponse(1016,'传送参数错误');
        }
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        $shop = [];
        $shop['shop_name'] = $shopInfo['shop_name'];
        $shop['shop_pic'] = $shopInfo['shop_pic']?$domain->rule.'/'.$shopInfo['shop_pic']:$shopInfo['shop_pic'];
        $caseInfo = SuccessCaseModel::where('uid',$shopInfo['uid'])->select('id','pic')->orderBy('created_at','desc')->paginate(3)->toArray();
        if($caseInfo['total']){
            foreach($caseInfo['data'] as $k=>$v){
                $caseInfo['data'][$k]['pic'] = $v['pic']?$domain->rule.'/'.$v['pic']:$v['pic'];
            }
        }
        $shop['caseInfo'] = $caseInfo;
        return $this->formateResponse(1000,'获取成功案例信息成功',$shop);
    }


    /**
     * 获取商品详情
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function goodDetail(Request $request){
        $type = $request->get('type');
        $id = $request->get('id');
        $goodDetail = GoodsModel::where(['id' => $id,'type' => $type,'status' => 1,'is_delete' => 0])->select('desc')->first();
        if(empty($goodDetail)){
            return $this->formateResponse(1017,'传送参数错误');
        }
        $desc = htmlspecialchars_decode($goodDetail->desc);
        return $this->formateResponse(1000,'获取商品详情信息成功',['desc' => $desc]);
    }


    /**
     * 获取商品评价
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function goodComment(Request $request){
        $type = $request->get('type');
        $id = $request->get('id');
        if(!$id or !$type){
            return $this->formateResponse(1017,'传送参数不能为空');
        }
        $goodDetail = GoodsModel::where(['id' => $id,'type' => $type,'status' => 1,'is_delete' => 0])->select('cash')->first();
        if(empty($goodDetail)){
            return $this->formateResponse(1018,'传送参数错误');
        }
        $commentInfo = [];
        $comment = GoodsCommentModel::where('goods_id',$id);
        if($request->get('sorts')){
            $sorts = $request->get('sorts');
            switch($sorts){
                case '1':
                    $classify = 0;
                    $comment = $comment->where('type',$classify);
                    break;
                case '2':
                    $classify = 1;
                    $comment = $comment->where('type',$classify);
                    break;
                case '3':
                    $classify = 2;
                    $comment = $comment->where('type',$classify);
                    break;
            }

        }
        $comment = $comment->select('*')->paginate(3)->toArray();
        if($comment['total']){
            $uids = array_pluck($comment['data'],'uid');
            $userInfo = UserModel::whereIn('id',$uids)->where('status',1)->select('id','name')->get()->toArray();
            if(empty($userInfo)){
                return $this->formateResponse(1019,'找不到相关的用户信息');
            }
            $userInfo = collect($userInfo)->pluck('name','id')->all();
            $userDetail = UserDetailModel::whereIn('uid',$uids)->select('uid','avatar')->get()->toArray();
            if(empty($userDetail)){
                return $this->formateResponse(1020,'找不到用户详情信息');
            }
            $userDetail = collect($userDetail)->pluck('avatar','uid')->all();
            $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
            foreach($comment['data'] as $k=>$v){
                $comment['data'][$k]['name'] = $userInfo[$v['uid']];
                $comment['data'][$k]['avatar'] = $userDetail[$v['uid']]?$domain->rule.'/'.$userDetail[$v['uid']]:$userDetail[$v['uid']];
                $comment['data'][$k]['comment_desc'] = htmlspecialchars_decode($v['comment_desc']);
                $comment['data'][$k]['total_score'] = number_format(($v['speed_score']+$v['quality_score']+$v['attitude_score'])/3,1);
            }
            $commentInfo = $comment;

        }
        return $this->formateResponse(1000,'获取商品评价信息成功',$commentInfo);
    }


    /**
     * 获取商品内容
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function goodContent(Request $request){
        $id = $request->get('id');
        $type = $request->get('type');
        if(!$id or !$type){
            return $this->formateResponse(1021,'传送参数不能为空');
        }
        $goodInfo = GoodsModel::where(['id' => $id,'type' => $type,'status' => 1,'is_delete' => 0])->select('*')->first();
        if(empty($goodInfo)){
            return $this->formateResponse(1022,'传送参数错误');
        }
        $shopInfo = ShopModel::where(['id' => $goodInfo->shop_id,'status' => 1])->first();
        if(empty($shopInfo)){
            return $this->formateResponse(1023,'店铺信息不存在');
        }
        //查询地址
        if($shopInfo->province){
            $province = DistrictModel::where('id',$shopInfo->province)->select('id','name')->first();
            $provinceName = $province->name;
        }else{
            $provinceName = '';
        }
        if($shopInfo->city){
            $city = DistrictModel::where('id',$shopInfo->city)->select('id','name')->first();
            $cityName = $city->name;
        }else{
            $cityName = '';
        }
        $goodInfo->desc = htmlspecialchars_decode($goodInfo->desc);
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        $goodInfo->cover = $goodInfo->cover?$domain->rule.'/'.$goodInfo->cover:$goodInfo->cover;
        $goodInfo->address = $provinceName.$cityName;
        return $this->formateResponse(1000,'获取商品内容成功',$goodInfo);
    }


    /**
     * 威客商城
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function shopList(Request $request){
        $type = $request->get('type');
        $name = $request->get('name');
        $cate_id = $request->get('cate_id');
        $new = $request->get('new');
        $domain = ConfigModel::where('alias','site_url')->where('type','site')->select('rule')->first();
        switch($type){
            case '1':
                $shopList = GoodsModel::where(['status' => 1,'is_delete' => 0]);
                if($name){
                    $shopList = $shopList->where('title','like','%'.$name.'%');
                }
                if($cate_id){
                    $shopList = $shopList->where('cate_id',$cate_id);
                }
                if($new){
                    $shopList = $shopList->orderBy('created_at','desc');
                }
                $shopList = $shopList->paginate()->toArray();
                if($shopList['total']){
                    $shop_ids = array_pluck($shopList,'shop_id');
                    $cate_ids = array_pluck($shopList['data'],'cate_id');
                    $cateInfo = TaskCateModel::whereIn('id',$cate_ids)->select('id','name')->get()->toArray();
                    if(empty($cateInfo)){
                        return $this->formateResponse(1024,'商品二级分类信息不存在');
                    }
                    $cateInfo = collect($cateInfo)->pluck('name','id')->all();
                    $cityInfo = ShopModel::join('district', 'shop.city', '=', 'district.id')
                        ->select('shop.id','district.name')
                        ->whereIn('shop.id', $shop_ids)
                        ->where('shop.status',1)
                        ->get()->toArray();
                    if(empty($cityInfo)){
                        return $this->formateResponse(1025,'城市信息不存在');
                    }
                    $cityInfo = collect($cityInfo)->pluck('name','id')->all();
                    foreach($shopList['data'] as $k=>$v){
                        $shopList['data'][$k]['cover'] = $v['cover']?$domain->rule.'/'.$v['cover']:$v['cover'];
                        $shopList['data'][$k]['city_name'] = $cityInfo[$v['shop_id']];
                        $shopList['data'][$k]['cate_name'] = $cateInfo[$v['cate_id']];
                    }
                }
            return $this->formateResponse(1000,'获取威客商城信息成功',$shopList);
            default:
                $shopList = ShopModel::where('status',1);
                if($name){
                    $shopList = $shopList->where('shop_name','like','%'.$name.'%');
                }
                /*if($cate_id){
                }*/    //行业暂不做
                if($new){
                    $shopList = $shopList->orderBy('created_at','desc');
                }
                $shopList = $shopList->paginate()->toArray();
                if($shopList['total']){
                    $shop_ids = array_pluck($shopList,'id');
                    $shopInfoTags = ShopTagsModel::whereIn('shop_id',$shop_ids)->get()->toArray();
                    if(!empty($shopInfoTags)){
                        $tagIds = array_pluck($shopInfoTags,'tag_id');
                        $shopTag = collect($shopInfoTags)->pluck('tag_id','shop_id')->all();
                        //查询技能详情
                        $tags = SkillTagsModel::whereIn('id',$tagIds)->select('id','tag_name')->get()->toArray();
                        $skillTags = collect($tags)->pluck('tag_name','id')->all();

                    }
                    $cityInfo = ShopModel::join('district', 'shop.city', '=', 'district.id')
                        ->select('shop.id','district.name')
                        ->whereIn('shop.id', $shop_ids)
                        ->where('shop.status',1)
                        ->get()->toArray();
                    if(empty($cityInfo)){
                        return $this->formateResponse(1025,'城市信息不存在');
                    }
                    $cityInfo = collect($cityInfo)->pluck('name','id')->all();
                    foreach($shopList['data'] as $k=>$v){
                        $shopList['data'][$k]['shop_pic'] = $v['shop_pic']?$domain->rule.'/'.$v['shop_pic']:$v['shop_pic'];
                        $shopList['data'][$k]['city_name'] = $cityInfo[$v['id']];

                    }
                }
            return $this->formateResponse(1000,'获取商城信息成功',$shopList);

        }
    }
}