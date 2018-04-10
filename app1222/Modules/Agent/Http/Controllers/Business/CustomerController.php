<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\AddCustomerModel;
use Illuminate\Http\Request;

class CustomerController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'customer');
    }

    /**
     * 客户
     */
    public function index(Request $request)
    {
//        $this->store;//用户店铺id
        $tableData = AddCustomerModel::where('store_id', $this->store->id);
//        dd($tableData);exit;
        //电话筛选
        if ($mobile = $request->get('mobile')) {
            $tableData->where('mobile', $mobile);
        }
        //会员筛选
        if ($vip = $request->get('vip')) {
            $tableData->where('vip', $vip);
        }
        //分页设置
        $tableData = $tableData->latest()->paginate(10);

        $vip = [
            [
                'name' => '会员',
                'val' => 'Y'
            ],
            [
                'name' => '非会员',
                'val' => 'N'
            ]
        ];

        //数据赋值
        $view = [
            'tableData' => $tableData,
            'vip' => $vip,
            'merge' => $request->all(),
        ];
        $this->theme->setTitle('客户');
        return $this->theme->scope($this->prefix . '.customer.index', $view)->render();
    }

    /**
     * 客户-添加客户界面@ajax
     */
    public function addCustomer(Request $request)
    {
        //表单验证
        $this->validate($request, [
            'mobile' => [
                'required',
                'regex:/^(13[0-9]|15[^4]|18[0-9]|14[57])[0-9]{8}$/'
            ],
            'name' => [
                'required',
                'regex:/(^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-z]{2,10}$)/ui',
            ],
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.regex' => '手机号码格式不正确',
            'name.required' => '请输入姓名',
            'name.regex' => '仅支持2~7位中、英文',
        ]);
        //数据处理
        $data = $request->all();
//        dd($data);exit;
        $data['store_id'] = $this->store->id;
        $result = AddCustomerModel::create($data);
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '新建成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '新建失败']);
        }
    }

    /**
     * 客户-编辑客户界面@ajax
     */
    public function editPage($id = 0)
    {
        if ($this->store) {
            $uid = $this->store->id;
            if ($id <= 0) {
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
            }
            $info = AddCustomerModel::where('id',$id)
                ->where('store_id', $uid)
                ->first();
            if (! $info) {
                return response()->json(['code' => '1002', 'msg' => '参数错误']);
            }
            $vip = [
                [
                    'name' => '会员',
                    'val' => 'Y'
                ],
                [
                    'name' => '非会员',
                    'val' => 'N'
                ]
            ];
            $view = [
                'info' => $info,
                'vip'  => $vip
            ];
            return response()->json([
                'code' => '1000',
                'data' => view('agent.edit', $view)->render(),
//                'data' => view($this->prefix . '.customer.edit', $view)->render()
            ]);
        } else {
            return response()->json(['code' => '1004', 'msg' => '未登录或登录过期']);
        }
    }

    /**
     * 客户-保存编辑的客户信息@ajax
     */
    public function update(Request $request, $id)
    {
        $data = $request->except('_token');
        $result = AddCustomerModel::where('id',$id)->first();
        if ($result) {
            AddCustomerModel::where('id',$id)->update($data);
            return response()->json(['code' => '1000', 'msg' => '修改成功']);
//            return redirect($this->module . '/' . $this->flag . '/customer')->with('success','删除成功');
        } else {
            return response()->json(['code' => '1004', 'msg' => '修改失败']);
//            return redirect($this->module . '/' . $this->flag . '/customer')->with('error','删除失败');
        }
    }

    /**
     * 客户-删除客户信息
     */
    public function delete($id)
    {
        $data = AddCustomerModel::find($id);
        if ($data->delete()) {
            return redirect($this->module . '/' . $this->flag . '/customer')->with('success','删除成功');
        } else {
            return redirect($this->module . '/' . $this->flag . '/customer')->with('error','删除失败');
        }
    }

}

