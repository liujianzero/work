<?php
namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use App\Modules\User\Model\GoodsCart;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsOrderServiceModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Auth;
use Crypt;
use Cache;
use DB;

class GoodsCarts extends UserCenterController
{

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('myOrder.task');//主题初始化
        $shopCount = ModelsOrderServiceModel::where('shop_id', Auth::user()->id)
            ->whereNotIn('task_status', [0, 5])->count();
        $userCount = ModelsOrderServiceModel::where('user_id', Auth::user()->id)
            ->whereNotIn('task_status', [0, 5])->count();
        $this->theme->set('shopCount', $shopCount >= 100 ? 99 : $shopCount);
        $this->theme->set('userCount', $userCount >= 100 ? 99 : $userCount);
    }

    /* /------------------------------------- 购物车 -------------------------------------\ */

    /**
     * 我的购物车
     */
    public function cart(Request $request)
    {
        //获取列表
        $tmp = GoodsCart::where('user_id', Auth::user()->id)
            ->orderBy('is_effective', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
        $list = [];
        foreach ($tmp as &$v) {
            if ($v['is_effective'] == 'Y') {
                $img = ModelsContentModel::select('upload_cover_image', 'cover_img')
                    ->where('id', $v['goods_id'])
                    ->first();
                if (!empty($img['upload_cover_image']) && file_exists($img['upload_cover_image'])) {
                    $v['image'] = url($img['upload_cover_image']);
                } else {
                    if (!empty($img['cover_img']) && file_exists($img['cover_img'])) {
                        $v['image'] = url($img['cover_img']);
                    } else {
                        $v['image'] = '/themes/default/assets/images/folder_no_cover.png';
                    }
                }
            } else {
                $v['image'] = '';
            }
            $list[$v['shop_id']]['children'][] = $v;
            $list[$v['shop_id']]['shop_id'] = $v['shop_id'];
        }
        foreach ($list as &$v1) {
            $v1['shop_name'] = UserDetailModel::where('uid', $v1['shop_id'])->value('nickname');
        }
        //数据赋值
        $view = [
            'list' => $list
        ];
        $this->theme->setTitle('我的购物车');
        return $this->theme->scope('user.myOrder.cart.cart', $view)->render();
    }

    /**
     * 更新购物车数量
     */
    public function changeNumber(Request $request)
    {
        $data = $request->get('normal');
//        dd($data);exit;
        $ids = [];
        $update = [];
        foreach ($data as $v) {
            if (isset($v['id']) && !empty($v['id'])) {
                $ids[] = $v['id'];
            }
            $update[] = [
                'id' => $v['number_id'],
                'goods_number' => $v['number']
            ];
        }
//        dd($update);exit;
        if (count($ids) <= 0) {
            return back()->with(['err' => '请选择要下单的商品']);
        }
        $uid = Auth::user()->id;
        foreach ($update as $v) {
            $id = $v['id'];
            unset($v['id']);
            GoodsCart::where('user_id', $uid)->where('id', $id)->update($v);
        }
//        dd($ids);exit;
        return redirect()->route('myOrder.myGoodsBuy', ['ids' => implode(',', $ids)]);
    }

    /**
     * ajax-更新购物车数量
     */
    public function cartNumber(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $data = $request->all();
            if ($data['id'] <= 0 || $data['number'] <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $status = GoodsCart::where('user_id', $uid)->where('id', $data['id'])
                ->update(['goods_number' => $data['number']]);
            if ($status) {
                return response()->json(['code' => '1000']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '更改数量失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * ajax-删除商品
     */
    public function delCart($id)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $status = GoodsCart::where('user_id', $uid)->where('id', $id)
                ->delete();
            if ($status) {
                return response()->json(['code' => '1000', 'msg' => '删除成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '删除失败']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    /* /------------------------------------- 价格 -------------------------------------\ */

    /**
     * 格式化价格 *.**
     */
    protected function priceFormat($price)
    {
        return number_format($price, 2, '.', '');
    }

}