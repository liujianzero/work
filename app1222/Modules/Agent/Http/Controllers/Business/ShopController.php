<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\EditSetUpModel;
use App\Modules\Agent\Model\ShopComponentModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Auth;

class ShopController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
//        dd(Auth::user()->id);exit;
        $this->theme->set('menu_active', 'shop');
    }

    /**
     * 店铺
     */
    public function index()
    {
        $store_id = $this->store->id;
        $id = $this->store->id;//1221
        $info = EditSetUpModel::where('store_id', $store_id)->first();
        $uid = UserModel::where('id', $id)->pluck('pid');//1221
        $models = ModelsContentModel::select('id', 'title', 'content','transaction_mode', 'cover_img','upload_cover_image', 'create_time' ,'price' , 'is_goods')->where('uid', $uid);//1221
        $models = $models->where('is_private', 0)->orderBy('transaction_mode', 'desc')->get();//1221
//        dd($info['store_id']);exit;
        //数据赋值
        $view = [
            's_id' => $store_id,
            'id' => $id,//1221
            'info' => $info,
            'models' => $models,//1221
        ];
        $this->theme->setTitle('店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.index', $view)->render();
//        return $this->theme->scope('agent.' . $this->flag . '.shop.index', $view)->render();//1221
    }

    /**
     * 店铺-选择模板
     */
    public function selectTemplate()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('选择模板-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.selectTemplate', $view)->render();
    }

    /**
     * 店铺-自定义模板
     */
    public function custom()
    {
        $uid = $this->store->id;//获取店铺id
        //数据赋值
        $view = [
            'uid' => $uid,
        ];
        $this->theme->setTitle('自定义模板-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.custom', $view)->render();
    }

    /**
     * 店铺-购买模板
     */
    public function buyTemplate()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.buyTemplate', $view)->render();
    }

    /**
    * 店铺-保存发布-首页（默认模板）
     */
    public function defaults($id)
    {
        $uid = UserModel::where('id', $id)->pluck('pid');
        $this->initTheme('agent.default');
        $info = EditSetUpModel::where('store_id', $id)->first();
        //获取用户所有作品
        $models = ModelsContentModel::select('id', 'title', 'content','transaction_mode', 'cover_img','upload_cover_image', 'create_time' ,'price' , 'is_goods')->where('uid', $uid);
        $models = $models->where('is_private', 0)->orderBy('transaction_mode', 'desc')->get();
//        dd($models);exit;

        if (!empty($info)) {
            if ($info['store_status'] == 1) {
                //数据赋值
                $view = [
                    'id'     => $id,
                    'info'   => $info,
                    'models' => $models,
                ];
                $this->theme->setTitle( $info['name'] );
                return $this->theme->scope('agent.' . $this->flag . '.shop.default_model', $view)->render();
            } else {
                //数据赋值
                $view = [
                    'store_name' => $info['name'],
                ];
                return view('agent.closed', $view)->render();
            }
        } else {
            abort('404');
        }
    }

    /**
    * 店铺-保存发布-个人中心（默认模板）
     */
    public function personal($id)
    {
        $this->initTheme('agent.default');
//        $data = UserDetailModel::where('uid', $id)->first();
        $info = EditSetUpModel::where('store_id', $id)->first();
        if ($info && (Session::get('agentAdmin')->id) == $info['store_id']) {
            if ($info['store_status'] == 1) {
                //数据赋值
                $view = [
                    'id' => $id,
                    'info' => $info,
                ];
                $this->theme->setTitle('个人中心');
                return $this->theme->scope('agent.' . $this->flag . '.shop.default_personal', $view)->render();
            } else {
                //数据赋值
                $view = [
                    'store_name' => $info['name'],
                ];
                return view('agent.closed', $view)->render();
            }
        } else {
            abort('404');
        }
    }

    /**
     * 店铺-保存发布-简介（默认模板）
     */
    public function summary($id)
    {
        $this->initTheme('agent.default');
        $info = EditSetUpModel::where('store_id', $id)->first();
        $view = [
            'name' => $info['name'],
            'desc' => $info['desc'],
        ];
        $this->theme->setTitle('简介');
        return $this->theme->scope('agent.' . $this->flag . '.shop.default_summary', $view)->render();
    }

    /**
     * 店铺-保存发布-地址（默认模板）
     */
    public function address($id)
    {
        $this->initTheme('agent.default');
//        $info = EditSetUpModel::where('store_id', $id)->first();
        $view = [];
        $this->theme->setTitle('地址');
        return $this->theme->scope('agent.' . $this->flag . '.shop.default_addr', $view)->render();
    }

    /**
     * 店铺-保存发布-预订（默认模板）
     */
    public function order($id)
    {
        $this->initTheme('agent.default');
//        $info = EditSetUpModel::where('store_id', $id)->first();
        $view = [];
        $this->theme->setTitle('预订');
        return $this->theme->scope('agent.' . $this->flag . '.shop.default_order', $view)->render();
    }

    /**
     * 店铺-保存发布（自定义模板）
     */
    public function issue($id)
    {
        $info = UserDetailModel::where('uid', $id)->first();
        $add_com = ShopComponentModel::where('add', 1)->get();
//        $text = ShopComponentModel::where('id', 1)->pluck('add');
//        dd($add_com);exit;
        //数据赋值
        $view = [
            'id' => $id,
            'component' => $add_com,
            'text'      => $add_com->where('id', 1)->first(),
            'pic'       => $add_com->where('id', 2)->first(),
            'piclist'   => $add_com->where('id', 5)->first(),
            'bottomnav' => $add_com->where('id', 6)->first(),
        ];
        $this->theme->setTitle( $info['nickname'] . '的店铺' );
        return $this->theme->scope('agent.' . $this->flag . '.shop.issue', $view)->render();
    }

    /**
     * 店铺-预览
     */
    public function detail()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('保存发布-店铺');
        return $this->theme->scope('agent.' . $this->flag . '.shop.detail', $view)->render();
    }

//    /**
//    * 接收组件信息1202
//     */
//    public function accept(Request $request)
//    {
//        $data = $request->except('_token');
//        dd($data);exit;
//        if ($data) {
//            return response()->json(['code' => '1000', 'msg' => 'success']);
//        } else {
//            return response()->json(['code' => '1004', 'msg' => 'error']);
//        }
//    }

    /**
     * 更换全景图背景
     */
    public function editPic(Request $request)
    {
        $data = $request->except('_token');
        $uid = $this->store->id;
        $pic = $data['editPic'];
//        $test = EditSetUpModel::where('store_id', $uid)->pluck('pic');
//        var_dump($pic);exit;
        $result = EditSetUpModel::where('id', $uid)->update([ 'pic' => $pic ]);
//        var_dump($result);exit;
        if (! $result) {
            return response()->json(['code' => '1000', 'msg' => '更改成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '更改失败']);
        }

    }

    /**
    * 店铺-商品详情页
     */
    public function goodsContent($id)
    {
//        dd($this->store->id);exit;
        $this->initTheme('agent.default_content');
//        $uid = UserModel::where('id', $this->store->id)->pluck('pid');
//        dd($uid);exit;
        //获取用户所有的作品
        $models = ModelsContentModel::where('id', $id)->first();
//        dd($models);exit;
        $title = EditSetUpModel::where('store_id', $this->store->id)->first();
//        dd($title['name']);exit;

        $view = [
            'id' => $id,
            'models' => $models,
            'title' => $title['name'],
        ];
        $this->theme->setTitle( $models['title'] );
        return $this->theme->scope('agent.' . $this->flag . '.shop.default_content', $view)->render();
    }

}
