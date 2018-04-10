<?php

namespace App\Modules\Agent\Http\Controllers\Museum;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\AgentCustomer;
use Illuminate\Http\Request;

class CustomerController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'customer');
    }

    protected $vip = [
        'Y' => '会员',
        'N' => '非会员',
    ];

    protected $from_at = [
        'WeChat' => '关注公众号',
        'Unknown' => '未知',
        'CommonOrder' => '普通下单',
        'Cashier' => '收银台下单',
        'ManualEntry' => '手工录入',
        'MemberSavings' => '会员储蓄',
    ];

    // 客户
    public function index(Request $request)
    {
        $allow = [
            'mobile',
            'vip',
            'from_at',
        ];
        $merge = $request->only($allow);
        $list = AgentCustomer::where('store_id', $this->store->id);
        if ($mobile = $request->input('mobile')) {
            $list->where('mobile', 'like', "%{$mobile}%");
        }
        if ($vip = $request->input('vip')) {
            $list->where('vip', $vip);
        }
        if ($from_at = $request->input('from_at')) {
            $list->where('from_at', $from_at);
        }
        $per_page = $request->input('per_page', 10);
        $list = $list->latest()->paginate($per_page);
        $vip = $this->vip;
        $from_at = $this->from_at;
        $view = [
            'list' => $list,
            'merge' => $merge,
            'vip' => $vip,
            'from_at' => $from_at,
        ];
        $this->theme->setTitle('客户');
        return $this->theme->scope($this->prefix . '.customer.index', $view)->render();
    }

    // 客户-添加客户处理@ajax
    public function addCustomer(Request $request)
    {
        $allow = [
            'name',
            'vip',
            'mobile',
            'wechat',
            'remark',
        ];
        $data = $request->only($allow);
        $reg = '/^1[34578]\d{9}$/';
        if (! preg_match($reg, $data['mobile'])) {
            return response()->json(['code' => '1100', 'msg' => '手机号码格式不正确']);
        }
        $reg = '/(^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-z]{2,10}$)/ui';
        if (! preg_match($reg, $data['name'])) {
            return response()->json(['code' => '1100', 'msg' => '只允许2-10位中文或英文名字']);
        }
        if (! in_array($data['vip'], ['Y', 'N'])) {
            return response()->json(['code' => '1100', 'msg' => '请选择客户身份']);
        }
        $data['store_id'] = $this->store->id;
        $data['from_at'] = 'ManualEntry';
        $result = AgentCustomer::create($data);
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '新建成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '新建失败']);
        }
    }

    // 客户-编辑客户界面@ajax
    public function editPage($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = AgentCustomer::where('id', $id)->where('store_id', $uid)->first();
        if (! $info) {
            return response()->json(['code' => '1002', 'msg' => '参数错误']);
        }
        $vip = $this->vip;
        $from_at = $this->from_at;
        $view = [
            'info' => $info,
            'vip'  => $vip,
            'from_at'  => $from_at,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.customer.edit', $view)->render(),
        ]);
    }

    // 客户-编辑客户处理@ajax
    public function update(Request $request)
    {
        $id = $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $allow = [
            'name',
            'vip',
            'mobile',
            'wechat',
            'remark',
        ];
        $data = $request->only($allow);
        $reg = '/^1[34578]\d{9}$/';
        if (! preg_match($reg, $data['mobile'])) {
            return response()->json(['code' => '1100', 'msg' => '手机号码格式不正确']);
        }
        $reg = '/(^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-z]{2,10}$)/ui';
        if (! preg_match($reg, $data['name'])) {
            return response()->json(['code' => '1100', 'msg' => '只允许2-10位中文或英文名字']);
        }
        if (! in_array($data['vip'], ['Y', 'N'])) {
            return response()->json(['code' => '1100', 'msg' => '请选择客户身份']);
        }
        $result = AgentCustomer::where('id', $id)->where('store_id', $this->store->id)->update($data);
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '修改成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '修改失败']);
        }
    }

    //客户-删除客户@ajax
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $result = AgentCustomer::where('id', $id)->where('store_id', $this->store->id)->delete();
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '删除成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '删除失败']);
        }
    }

}
