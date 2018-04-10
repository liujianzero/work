<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Manage\Model\RegistrationFormModel;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationFormController extends ManageController
{
    public function __construct()
    {
        parent::__construct();

        $this->initTheme('manage');
        $this->theme->setTitle('报名表信息');
        $this->theme->set('manageType', 'auth');
    }

    public function Registration(Request $request)
    {
//        $merge = $request->all();
        $registration = RegistrationFormModel::whereRaw('1 = 1');
        //姓名筛选
        if ($request->get('name')) {
            $registration = $registration->where('name',  'like', '%' . $request->get('name') . '%');//模糊查询
        }
        //电话号码筛选
        if ($request->get('mobile')) {
            $registration = $registration->where('mobile', 'like', '%' . $request->get('mobile') . '%');
        }
        //学习课程筛选
        if ($request->get('course')) {
            switch ($request->get('course')) {
                case '1':
                    $status = 0;
                    break;
                case '2':
                    $status = 1;
                    break;
                default:
                    $status = 2;
            }
            $registration = $registration->where('course', $status);
        }
        //学习地点筛选
        if ($request->get('address')) {
            switch ($request->get('address')) {
                case '1':
                    $status = 0;
                    break;
                case '2':
                    $status = 1;
                    break;
                case '3':
                    $status = 2;
                    break;
                default:
                    $status = 3;
            }
            $registration = $registration->where('address', $status);
        }
        //报名来源筛选
        if ($request->get('uid')) {
            switch ($request->get('uid')) {
                case '1':
                    $status = 1;
                    break;
                case '2':
                    $status = 2;
                    break;
                case '3':
                    $status = 3;
                    break;
                default:
                    $status = 4;
            }
            $registration = $registration->where('uid', $status);
        }
        //分页设置
//        $by = $request->get('by') ? $request->get('by') : 'registration_form.id';
//        $order = $request->get('order') ? $request->get('order') : 'desc';
//        $paginate = $request->get('paginate') ? $request->get('paginate') : 10;
//        $registration = $registration->orderBy($by, $order)->paginate($paginate);

        $registration = $registration->orderBy('id', 'desc')->paginate(10);
        //数据
        $view = [
            'merge' => $request->all(),
            'reg'   => $registration,
        ];
        $this->theme->setTitle('报名列表');
        return $this->theme->scope('manage.registrationForm.registrationform', $view)->render();
    }

}
