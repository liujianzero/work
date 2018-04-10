<?php

namespace App\Modules\Bre\Http\Controllers;


use App\Http\Controllers\ManageController;
use App\Modules\Bre\Model\RegistrationFormModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationFormController extends ManageController
{
    public function __construct()
    {
        parent::__construct();
        $this->initTheme('blank');
    }

    public function registrationForm($id)
    {
        if(intval($id) <= 3 && intval($id) != 0 ) {
            $course_name = [
                [
                    'name' => '3DMAX',
                    'value' => '0'
                ],
                [
                    'name' => '3DMAXWEBVR',
                    'value' => '1'
                ]
            ];
            $add_name = [
                [
                    'name'    => '劳动局',
                    'value'   => '0',
                    'address' => '厦门市长青路191号劳动力大厦3楼312、313室',
                    'class'   => 'address pull-left',
                    'style'   => 'block'
                ],
                [
                    'name'    => '软件园二期',
                    'value'   => '1',
                    'address' => '软件园二期望海路47号302',
                    'class'   => 'address',
                    'style'   => 'none'
                ],
                [
                    'name'    => '集美',
                    'value'   => '2',
                    'address' => '杏林湾商业运营中心9号楼裙楼创星谷2楼',
                    'class'   => 'address',
                    'style'   => 'none'
                ],
            ];
            //数据
            $view =[
                'uid'         => $id,
                'course_name' => $course_name,
                'add_name'    => $add_name,
            ];
            return $this->theme->scope('bre.registrationform.registrationForm',$view)->render();
        } else {
            abort(404);
        }
    }

    public function postForm(Request $request)
    {
        $data = $request->except('_token');
        $uid = intval($data['uid']);
        $status = DB::transaction(function () use($data,$uid) {
            RegistrationFormModel::create([
                'uid'     => $uid,
                'name'    => $data['name'],
                'mobile'  => $data['mobile'],
                'course'  => $data['course'],
                'address' => $data['address'],
                'remark'  => $data['remark'],
            ]);
        });
        $outcome = is_null($status) ? true : false;
        if ($outcome) {
            $type = intval($data['uid']);
            RegistrationFormModel::sendMsg($data, $type);
            return response()->json(['code' => '1000', 'msg' => '提交成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '提交失败']);
        }
    }

}