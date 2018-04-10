<?php
namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use App\Modules\Shop\Models\ShopFocusModel;
use App\Modules\Task\Model\SuccessCaseModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\EnterpriseAuthModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\Shop\Models\ShopModel;
use App\Modules\Shop\Models\ShopTagsModel;
use App\Modules\User\Model\RealnameAuthModel;
use App\Modules\User\Model\TagsModel;
use Illuminate\Http\Request;
use Auth;
use Crypt;

class ShopController extends UserCenterController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('accepttask');//主题初始化
    }

    /**
     * 店铺设置视图
     * @param Request $request
     * @return mixed
     */
    public function getShop(Request $request)
    {
        $uid = Auth::User()->id;
        //判断用户是否实名认证
        $realName = RealnameAuthModel::where('uid',$uid)->where('status',1)->first();
        if(empty($realName)){
            return $this->theme->scope('user.usershopbefore')->render();
        }
        //判断店铺是否进行企业认证
        $companyAuth = EnterpriseAuthModel::isEnterpriseAuth($uid);
        //查询所有标签
        $hotTag = TagsModel::findAll();
        //查询地区一级数据
        $province = DistrictModel::findTree(0);
        //查询店铺详情
        $shopInfo = ShopModel::getShopInfoByUid($uid);
        if(!empty($shopInfo)){
            //查询店铺原有的标签id
            $tag = ShopTagsModel::shopTag($shopInfo['id']);
            $tags = array_flatten($tag);
            //查询地区二级信息
            $city = DistrictModel::findTree($shopInfo['province']);
            $data = array(
                'shop_info'       => $shopInfo,
                'is_company_auth' => $companyAuth,
                'all_tag'         => $hotTag,
                'tags'            => $tags,
                'province'        => $province,
                'city'            => $city
            );
        }else{
            //查询地区二级信息
            $city = DistrictModel::findTree($province[0]['id']);
            $data = array(
                'is_company_auth' => $companyAuth,
                'all_tag'         => $hotTag,
                'province'        => $province,
                'city'            => $city
            );
        }
        $this->theme->setTitle('店铺设置');
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.usershop',$data)->render();

    }

    /**
     * 保存店铺设置
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postShopInfo(Request $request)
    {
        $data = $request->except('_token','shop_pic');
        $uid = Auth::User()->id;
        $data['uid'] = $uid;
        $data['shop_desc'] = trim($data['shop_desc']);
        if($request->get('id') && $request->get('id') != ''){
           //编辑店铺设置
           $shop = ShopModel::where('id',$data['id'])->first();
           $file = $request->file('shop_pic');
           if ($file) {
               $result = \FileClass::uploadFile($file, 'user');
               $result = json_decode($result, true);
               $data['shop_pic'] = $result['data']['url'];
           }else{
               $data['shop_pic'] = $shop->shop_pic;
           }
           $res = ShopModel::updateShopInfo($data);
        }else{
           //新建店铺设置
           $file = $request->file('shop_pic');
           if ($file) {
               $result = \FileClass::uploadFile($file, 'user');
               $result = json_decode($result, true);
               $data['shop_pic'] = $result['data']['url'];
           }else{
               $data['shop_pic'] = '';
           }
           $res = ShopModel::createShopInfo($data);
        }
        if($res){
            return redirect('/user/shop')->with(array('message' => '保存成功'));
        }else{
            return redirect('/user/shop')->with(array('message' => '保存失败'));
        }


    }


    /**
     * ajax获取城市、地区数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetCity(Request $request)
    {
        $id = intval($request->get('id'));
        if (!$id) {
            return response()->json(['errMsg' => '参数错误！']);
        }
        $province = DistrictModel::findTree($id);
        if($province){
            $area = DistrictModel::findTree($province[0]['id']);
        }else{
            $area = array();
        }
        $data = [
            'province' => $province,
            'area' => $area
        ];
        return response()->json($data);
    }

    /**
     * ajax获取地区数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetArea(Request $request)
    {
        $id = intval($request->get('id'));
        if (!$id) {
            return response()->json(['errMsg' => '参数错误！']);
        }
        $area = DistrictModel::findTree($id);
        $data = [
            'area' => $area,
        ];
        return response()->json($data);
    }

    /**
     * 企业认证文件上传控制
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileUpload(Request $request)
    {
        $file = $request->file('file');
        //将文件上传的数据存入到attachment表中
        $attachment = \FileClass::uploadFile($file, 'user');
        $attachment = json_decode($attachment, true);
        //判断文件是否上传
        if($attachment['code']!=200)
        {
            return response()->json(['errCode' => 0, 'errMsg' => $attachment['message']]);
        }
        $attachment_data = array_add($attachment['data'], 'status', 1);
        $attachment_data['created_at'] = date('Y-m-d H:i:s', time());
        //将记录写入到attchement表中
        $result = AttachmentModel::create($attachment_data);
        $result = json_decode($result, true);
        if (!$result) {
            return response()->json(['errCode' => 0, 'errMsg' => '文件上传失败！']);
        }
        //回传附件id
        return response()->json(['id' => $result['id']]);
    }

    /**
     * 附件删除
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileDelete(Request $request)
    {
        $id = $request->get('id');
        //查询当前的附件
        $file = AttachmentModel::where('id',$id)->first()->toArray();
        if(!$file)
        {
            return response()->json(['errCode' => 0, 'errMsg' => '附件没有上传成功！']);
        }
        //删除附件
        unlink($file['url']);
        $result = AttachmentModel::destroy($id);
        if (!$result) {
            return response()->json(['errCode' => 0, 'errMsg' => '删除失败！']);
        }
        return response()->json(['errCode' => 1, 'errMsg' => '删除成功！']);
    }

    /**
     * 企业认证视图(根据认证状态视图不同) 0=>待验证  1=>成功  2=>失败
     * @return mixed
     */
    public function getEnterpriseAuth()
    {
        $user = Auth::User();
        $companyInfo = EnterpriseAuthModel::where('uid', $user->id)->orderBy('created_at', 'desc')->first();

        //一级行业
        $cateFirst = TaskCateModel::findByPid([0],['id','name']);
        if(!empty($cateFirst)){
            //二级行业
            $cateSecond = TaskCateModel::findByPid([$cateFirst[0]['id']],['id','name']);
        }else{
            $cateSecond = array();
        }
        //查询地区一级数据
        $province = DistrictModel::findTree(0);
        if(!empty($province)){
            //查询地区二级信息
            $city = DistrictModel::findTree($province[0]['id']);
            if(!empty($city)){
                //查询地区三级信息
                $area = DistrictModel::findTree($city[0]['id']);
            }else{
                $area = array();
            }
        }else{
            $city = array();
            $area = array();
        }
        $view = '';
        if (isset($companyInfo->status)) {
            $cateInfo = TaskCateModel::findById($companyInfo->cate_id);
            if($cateInfo){
                $cateName = $cateInfo['name'];
            }else{
                $cateName = '';
            }
            $data = array(
                'company_info' => $companyInfo,
                'cate_name' => $cateName,
            );
            switch ($companyInfo->status) {
                case 0:
                    $view = 'user.waitusershopauth';
                    break;
                case 1:
                    return redirect('/user/shop');
                    break;
                case 2:
                    $view = 'user.usershopauthfail';
                    break;
            }
        } else {
            $data = array(
                'cate_first'  => $cateFirst,
                'cate_second' => $cateSecond,
                'province'    => $province,
                'city'        => $city,
                'area'        => $area
            );
            $view = 'user.usershopqy';
        }
        $this->theme->setTitle('企业认证');
        $this->theme->set('TYPE',3);
        return $this->theme->scope($view, $data)->render();
    }

    /**
     * 保存企业认证信息
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postEnterpriseAuth(Request $request)
    {
        $data = $request->except('_token');
        $uid = Auth::id();
        $companyInfo = array(
            'uid'              => $uid,
            'company_name'     => $data['company_name'] ? $data['company_name'] : '',
            'cate_id'          => $data['cate_second'] ? $data['cate_second'] : '' ,
            'employee_num'     => $data['employee_num'] ? $data['employee_num'] : '',
            'business_license' => $data['business_license'] ? $data['business_license'] : '',
            'begin_at'         => $data['start'] ? date('Y-m-d H:i:s',strtotime($data['start'])) : '',
            'website'          => $data['website'] ? $data['website'] : '',
            'province'         => $data['province'] ? $data['province'] : '',
            'city'             => $data['city'] ? $data['city'] : '',
            'area'             => $data['area'] ? $data['area'] : '',
            'address'          => $data['address'] ? $data['address'] : '',
            'status'           => 0,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        );
        $authRecordInfo = array(
            'uid'       => $uid,
            'auth_code' => 'enterprise',
            'status'    => 0
        );
        $fileId = !empty($data['file_id']) ? $data['file_id'] : '';
        EnterpriseAuthModel::createEnterpriseAuth($companyInfo,$authRecordInfo,$fileId);
        return redirect('/user/enterpriseAuth');
    }

    /**
     * ajax获取二级行业分类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetSecondCate(Request $request)
    {
        $id = intval($request->get('id'));
        if (!$id) {
            return response()->json(['errMsg' => '参数错误！']);
        }
        $cate = TaskCateModel::findByPid([$id]);
        $data = [
            'cate' => $cate
        ];
        return response()->json($data);
    }

    /**
     * 重新认证
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function enterpriseAuthAgain()
    {
        $uid = Auth::user()->id;
        $status = EnterpriseAuthModel::getEnterpriseAuthStatus($uid);
        if($status == 2){
            $res = EnterpriseAuthModel::removeEnterpriseAuth();
            if($res){
                return redirect('/user/enterpriseAuth');
            }
        }
    }


    /**
     * 店铺的案例管理
     * @param Request $request
     * @return mixed
     */
    public function shopSuccessCase(Request $request)
    {
        $uid = Auth::id();
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($uid);
        //店铺id
        $shopId = ShopModel::getShopIdByUid($uid);
        $merge = $request->all();
        $uid = Auth::id();
        $successCase = SuccessCaseModel::getSuccessCaseListByUid($uid,$merge);
        $data = array(
            'success_list' => $successCase,
            'merge' => $merge,
            'is_open_shop' => $isOpenShop,
            'shop_id' => $shopId
        );
        $this->theme->setTitle('案例列表');
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.usershopalgl',$data)->render();
    }

    /**
     * 添加案例视图
     * @return mixed
     */
    public function addShopSuccess()
    {
        $uid = Auth::id();
        //查询店铺id
        $shopId = ShopModel::getShopIdByUid($uid);
        //判断店铺是否开启
        $isOpenShop = ShopModel::isOpenShop($uid);
        //一级行业
        $cateFirst = TaskCateModel::findByPid([0],['id','name']);
        if(!empty($cateFirst)){
            //二级行业
            $cateSecond = TaskCateModel::findByPid([$cateFirst[0]['id']],['id','name']);
        }else{
            $cateSecond = array();
        }
        $data = array(
            'cate_first'  => $cateFirst,
            'cate_second' => $cateSecond,
            'is_open_shop' => $isOpenShop,
            'shop_id' => $shopId
        );
        $this->theme->setTitle('添加案例');
        $this->theme->set('TYPE',3);
        return $this->theme->scope('user.usershopal',$data)->render();
    }

    /**
     * 保存添加案例信息
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAddShopSuccess(Request $request)
    {
        $user = Auth::User();
        $file = $request->file('success_pic');
        if (!$file) {
            return redirect()->back()->with('error', '作品封面不能为空');
        }else{
            $result = \FileClass::uploadFile($file, 'sys');
            $result = json_decode($result, true);
            //判断文件是否上传
            if($result['code']!=200)
            {
                return redirect()->back()->with('error', $result['message']);
            }else{
                $pic = $result['data']['url'];
            }
        }
        if (!$request->cate_id) {
            return redirect()->back()->with('error', '作品分类不能为空');
        }

        $data = array(
            'pic'        => $pic,
            'uid'        => $user->id,
            'username'   => $user->name,
            'title'      => $request->title,
            'desc'       => \CommonClass::removeXss($request->description),
            'type'       => 1, //店铺添加类型为1
            'url'        => $request->url,
            'cate_id'    => $request->cate_id,
            'created_at' => date('Y-m-d H:i:s'),
        );
        $res = SuccessCaseModel::insert($data);
        if (!$res){
            return redirect()->back()->with('error', '成功案例添加失败！');
        }else{
            return redirect('/user/myShopSuccessCase')->with('massage', '成功案例添加成功！');
        }

    }

    /**
     * 编辑案例视图
     * @param $id 案例id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editShopSuccess($id)
    {
        $id = intval($id);
        $uid = Auth::id();
        //查询该案例案例是否属于该用户
        $successInfo = SuccessCaseModel::getSuccessInfoById($id);
        //一级行业
        $cateFirst = TaskCateModel::findByPid([0],['id','name']);
        if(!empty($successInfo->cate_pid)){
            //二级行业
            $cateSecond = TaskCateModel::findByPid([$successInfo->cate_pid],['id','name']);
        }else{
            $cateSecond = TaskCateModel::findByPid([$cateFirst[0]['id']],['id','name']);
        }
        if($successInfo->uid == $uid){
            $data = array(
                'success_info' => $successInfo,
                'cate_first'   => $cateFirst,
                'cate_second'  => $cateSecond
            );
            $this->theme->setTitle('编辑案例');
            $this->theme->set('TYPE',3);
            return $this->theme->scope('user.usershopaledit',$data)->render();
        }else{
            return redirect()->back()->with('error', '案例错误！');
        }
    }

    /**
     * 编辑案例
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postEditShopSuccess(Request $request)
    {
        $data = $request->except('_token');
        $successInfo = SuccessCaseModel::getSuccessInfoById($data['id']);
        $file = $request->file('success_pic');
        if (!$file) {
            $pic = $successInfo->pic;
        }else{
            $result = \FileClass::uploadFile($file, 'sys');
            $result = json_decode($result, true);
            //判断文件是否上传
            if($result['code']!=200)
            {
                return redirect()->back()->with('error', $result['message']);
            }else{
                $pic = $result['data']['url'];
            }
        }
        if (!$request->cate_id) {
            return redirect()->back()->with('error', '作品分类不能为空');
        }

        $arr = array(
            'pic'        => $pic,
            'title'      => $request->title,
            'desc'       => \CommonClass::removeXss($request->description),
            'url'        => $request->url,
            'cate_id'    => $request->cate_id,
        );
        $res = SuccessCaseModel::where('id',$data['id'])->update($arr);
        if (!$res){
            return redirect()->back()->with('error', '成功案例编辑失败！');
        }else{
            return redirect('/user/myShopSuccessCase')->with('massage', '成功案例编辑成功！');
        }
    }

    /**
     * 删除店铺案例
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteShopSuccess(Request $request)
    {
        $id = $request->get('id');
        $uid = Auth::id();
        //查询该案例案例是否属于该用户
        $successInfo = SuccessCaseModel::getSuccessInfoById($id);
        if($successInfo->uid == $uid){
            $res = SuccessCaseModel::where('id',$id)->delete();
            if($res){
                $data = array(
                    'code' => 1,
                    'msg' => '删除成功'
                );
            }else{
                $data = array(
                    'code' => 0,
                    'msg' => '删除失败'
                );
            }
        }else{
            $data = array(
                'code' => 0,
                'msg' => '参数错误'
            );
        }
        return  response()->json($data);
    }

    /**
     * 我收藏的店铺
     * @param Request $request
     * @return mixed
     */
    public function myCollectShop(Request $request)
    {
        $uid = Auth::id();
        $merge = $request->all();
        $collectArr = ShopFocusModel::where('uid',$uid)->orderby('created_at','DESC')->get()->toArray();
        $shopList = array();
        if(!empty($collectArr))
        {
            $shopIds = array();
            foreach($collectArr as $k => $v){
                $shopIds[] = $v['shop_id'];
            }
            if(!empty($shopIds)){
                $shopIds = array_unique($shopIds);
                $shopList = ShopModel::getShopListByShopIds($shopIds,$merge);
            }
        }
        $data = array(
            'shop_list' => $shopList,
            'merge' => $merge
        );

        $this->initTheme('usercenter');//主题初始化
        $this->theme->setTitle('我收藏的店铺');
        return $this->theme->scope('user.myshop',$data)->render();
    }

    /**
     * 取消收藏店铺
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelCollect(Request $request)
    {
        $ShopId = $request->get('id');
        $uid = Auth::id();
        $res = ShopFocusModel::where('uid',$uid)->where('shop_id',$ShopId)->delete();
        if($res){
            $data = array(
                'code' => 1,
                'msg' => '操作成功'
            );
        }else{
            $data = array(
                'code' => 0,
                'msg' => '操作失败'
            );
        }
        return  response()->json($data);
    }




    /*我的店铺*/
    public function myShopHint(Request $request)
    {
        return $this->theme->scope('user.myshophint')->render();
    }



    /*进入我的店铺中转链接*/
    public function switchUrl()
    {
        $uid = Auth::id();
        $this->theme->setUserId($uid);
        //判断用户是否实名认证
        $realName = RealnameAuthModel::where('uid',$uid)->where('status',1)->first();
        if(empty($realName)){
            return $this->theme->scope('user.usershopbefore')->render();
        }else{
            $shopInfo = ShopModel::where('uid',$uid)->first();
            if(empty($shopInfo)){
                return $this->theme->scope('user.myshophint')->render();
            }
            return redirect()->to('/shop/manage/'.$shopInfo['id']);
        }
    }


    /*实名认证提示*/
    public function userShopBefore(Request $request)
    {
        return $this->theme->scope('user.usershopbefore')->render();
    }



}