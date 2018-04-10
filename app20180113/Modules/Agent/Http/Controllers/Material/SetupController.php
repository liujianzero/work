<?php

namespace App\Modules\Agent\Http\Controllers\Material;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\NationalityModel;
use App\Modules\User\Model\OrganizationAuthModel;
use App\Modules\User\Model\StoreConfig;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SetupController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'setup');
    }

    // 设置
    public function index()
    {
        $info = UserModel::from('users as u')
            ->select([
                'u.id',
                'sc.store_name',
                'sc.store_thumb_logo as store_logo',
                'sc.store_auth',
                'sc.store_status',
                'sc.assure_status',
                'sc.auth_status',
                'sc.open_status',
                'sc.expire_at',
                'sc.store_desc',
                'sc.qq',
                'sc.mobile_register',
                'sc.created_at',
                'sc.province',
                'sc.city',
                'sc.area',
                'sc.address',
                'st.name as store_type_name',
                'c.name as store_cat_name',
            ])
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'u.id')
            ->leftJoin('store_configs as sc', 'sc.store_id', '=', 'u.id')
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->leftJoin('cate as c', 'c.id', '=', 'sc.major_business')
            ->where('u.id', $this->store->id)
            ->first();
        $province = DistrictModel::getRegionList();
        $city = DistrictModel::getRegionList($info->province);
        $area = DistrictModel::getRegionList($info->city);
        $view = [
            'info' => $info,
            'province' => $province,
            'city' => $city,
            'area' => $area,
        ];
        $this->theme->setTitle('设置');
        return $this->theme->scope($this->prefix . '.setup.index', $view)->render();
    }

    // 设置-保存设置信息
    public function edit(Request $request)
    {
        $this->validate($request, [
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'address' => 'required',
        ], [
            'province.required' => '请选择省份',
            'city.required' => '请选择城市',
            'area.required' => '请选择地区',
            'address.required' => '请填写详细地址',
        ]);
        $update['store_status'] = $request->input('store_status', 'off');
        $update['mobile_register'] = $request->input('mobile_register', 'off');
        $update['store_desc'] = $request->input('store_desc', null);
        $update['qq'] = $request->input('qq', null);
        $update['province'] = $request->input('province', null);
        $update['city'] = $request->input('city', null);
        $update['area'] = $request->input('area', null);
        $update['address'] = $request->input('address', null);
        $uid = $this->store->id;
        $result = DB::transaction(function () use ($update, $uid) {
            StoreConfig::where('store_id', $uid)->update($update);
            $update = [
                'qq' => $update['qq'],
                'introduce' => $update['store_desc'],
                'province' => $update['province'],
                'city' => $update['city'],
                'area' => $update['area'],
                'road' => $update['address'],
            ];
            UserDetailModel::where('uid', $uid)->update($update);
        });
        $result = is_null($result) ? true : false;
        if ($result) {
            return back()->with(['suc' => '修改成功']);
        } else {
            return back()->with(['err' => '修改失败']);
        }
    }

    // 设置-保存店名@ajax
    public function update(Request $request)
    {
        $allow = ['store_name'];
        $data = $request->only($allow);
        if (! $data['store_name']) {
            return response()->json(['code' => '1100', 'msg' => '请输入店铺名称']);
        }
        $uid = $this->store->id;
        $result = DB::transaction(function () use ($data, $uid) {
            StoreConfig::where('store_id', $uid)->update($data);
            $data = [
                'nickname' => $data['store_name']
            ];
            UserDetailModel::where('uid', $uid)->update($data);
        });
        $result = is_null($result) ? true : false;
        if ($result) {
            return response()->json(['code' => '1000', 'msg' => '修改成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '修改失败']);
        }
    }

    // 设置-上传LOGO@ajax
    public function webUpload(Request $request)
    {
        $file = $request->file('file');
        $path  = ucfirst($this->module) . "/$this->flag/setup/logo/";
        $ret = upload_file($file, $path);
        if ($ret['code']) {
            $thumb = img_resize($ret, $path);
            if ($thumb['code']) {
                $data = [
                    'store_logo' => $ret['filePath'],
                    'store_thumb_logo' => $thumb['filePath'],
                ];
                $uid = $this->store->id;
                $info = StoreConfig::where('store_id', $uid)->first();
                $result = DB::transaction(function () use ($data, $uid) {
                    StoreConfig::where('store_id', $uid)->update($data);
                    $data = [
                        'avatar' => $data['store_thumb_logo']
                    ];
                    UserDetailModel::where('uid', $uid)->update($data);
                });
                $result = is_null($result) ? true : false;
                if ($result) {
                    if (file_exists($info->store_logo)) {
                        @unlink($info->store_logo);
                    }
                    if (file_exists($info->store_thumb_logo)) {
                        @unlink($info->store_thumb_logo);
                    }
                    return response()->json(['code' => 1000, 'msg' => '上传LOGO成功']);
                } else {
                    return response()->json(['code' => 1004, 'msg' => '上传LOGO失败']);
                }
            } else {
                return response()->json(['code' => 1100, 'msg' => $thumb['msg']]);
            }
        } else {
            return response()->json(['code' => 1100, 'msg' => $ret['msg']]);
        }
    }

    // 设置-修改登录/支付密码@ajax
    public function password(Request $request)
    {
        $minutes = 10;
        $times = 3;
        $prefix = $request->input('type', '');
        $key_times = "{$prefix}password_error_times@{$this->store->id}";
        $key_time = "{$prefix}password_error_time@{$this->store->id}";
        $error_times = Cache::get($key_times, '0');
        $error_time = Cache::get($key_time, date('Y-m-d H:i:s'));
        if ($error_times >= $times) {
            return response()->json(['code' => 1100, 'msg' => '连续输错 ' . $times . ' 次密码，请于 ' . how_time($error_time) . ' 修改']);
        }
        $allow = [
            "old_{$prefix}password",
            "{$prefix}password",
            "{$prefix}password_confirm",
        ];
        $data = $request->only($allow);
        if ($data["{$prefix}password"] != $data["{$prefix}password_confirm"]) {
            return response()->json(['code' => 1100, 'msg' => '两次密码不一致']);
        }
        $password = UserModel::where('id', $this->store->id)->value("{$prefix}password");
        $password_confirmation = UserModel::encryptPassword($data["old_{$prefix}password"], $this->store->salt);
        if ($password != $password_confirmation) {
            $error_times++;
            $time = Carbon::now()->addMinutes($minutes);
            Cache::put($key_times, $error_times, $time);
            Cache::put($key_time, $time, $time);
            return response()->json(['code' => 1100, 'msg' => '旧密码错误']);
        }
        $password = UserModel::encryptPassword($data["{$prefix}password"], $this->store->salt);
        if ($password == $password_confirmation) {
            return response()->json(['code' => 1100, 'msg' => '新密码不能和旧密码一样']);
        }
        $update = [
            "{$prefix}password" => $password,
        ];
        $status = UserModel::where('id', $this->store->id)->update($update);
        $prefix = $prefix ? '支付' : '登录';
        if ($status) {
            return response()->json(['code' => 1000, 'msg' => "{$prefix}密码修改成功"]);
        } else {
            return response()->json(['code' => 1000, 'msg' => "{$prefix}密码修改失败"]);
        }
    }

    // 设置-店铺认证
    public function auth($action = '')
    {
        $info = OrganizationAuthModel::from('organization_auth as oa')
            ->select([
                'oa.*',
                'n.name_chinese',
                'l.lose_cause',
            ])
            ->leftJoin('nationality as n', 'n.nationality_id', '=', 'oa.nationality_id')
            ->leftJoin('lose as l', 'l.lose_id', '=', 'oa.id')
            ->where('oa.uid', $this->store->id)
            ->orderBy('oa.created_at', 'desc')
            ->first();
        switch ($action) {
            case 'apply':
                if ($info && $info->status == 1) {
                    return back()->with(['error' => '您的店铺已经通过认证']);
                }
                $view = [
                    'nationality_list' => NationalityModel::getNationalityList(),
                    'step' => 'apply',
                ];
                $this->theme->setTitle('店铺认证');
                return $this->theme->scope($this->prefix . '.setup.authorize', $view)->render();
                break;
            case 'progress':
                if (! $info) {
                    return back()->with(['error' => '非法操作']);
                }
                switch ($info->status) {
                    case 0:
                        $step = 'wait';
                        break;
                    case 1:
                        $step = 'success';
                        break;
                    case 2:
                        $step = 'fail';
                        break;
                    default:
                        $step = 'unknown';
                        break;
                }
                $view = [
                    'info' => $info,
                    'step' => $step,
                ];
                $this->theme->setTitle('店铺认证进度');
                return $this->theme->scope($this->prefix . '.setup.authorize', $view)->render();
                break;
            default:
                return back()->with(['error' => '非法操作']);
                break;
        }
    }

    // 设置-店铺认证处理
    public function authHandle(Request $request)
    {
        $this->validate($request, [
            'nationality_id' => 'required',
            'company_name' => 'required',
            'registration_number' => 'required',
            'legal_representative' => 'required',
            'registration_time' => [
                'required',
                'date_format:Y-m-d',
            ],
            'registration_address' => 'required',
            'promise' => 'accepted',
        ], [
            'nationality_id.required' => '请选择国籍',
            'company_name.required' => '请输入公司名称',
            'registration_number.required' => '请输入企业注册号',
            'legal_representative.required' => '请输入法人代表名',
            'registration_time.required' => '请选择公司成立日期',
            'registration_time.date_format' => '公司成立日期格式错误',
            'registration_address.date_format' => '请输入注册地址',
            'promise.accepted' => '您必须勾选承诺',
        ]);
        $file = $request->file('business_license');
        $path  = ucfirst($this->module) . "/$this->flag/setup/authorize/";
        $file = upload_file($file, $path);
        if (! $file['code']) {
            return back()->withErrors(['error' => $file['msg']])->withInput();
        }
        $allow = [
            'nationality_id',
            'company_name',
            'registration_number',
            'legal_representative',
            'registration_time',
            'registration_address',
        ];
        $data = $request->only($allow);
        $data['business_license'] = $file['filePath'];
        $data['uid'] = $this->store->id;
        $data['username'] = $this->store->name;
        $time = date('Y-m-d H:i:s');
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $record = [
            'uid' => $this->store->id,
            'username' => $this->store->name,
            'auth_code' => 'organization',
        ];
        $OrganizationAuthModel = new OrganizationAuthModel();
        $status = $OrganizationAuthModel->createOrganizationAuth($data, $record);
        if ($status) {
            StoreConfig::where('store_id', $this->store->id)->update(['auth_status' => 2]);
            return redirect()->route($this->prefix . '.setup.authorize', ['action' => 'progress'])->with(['suc' => '申请成功']);
        } else {
            return back()->withInput()->withErrors(['error' => '申请失败']);
        }
    }
}
