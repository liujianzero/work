<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\EchartsDataModel;
use Illuminate\Http\Request;

class DataController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'data');
    }

    /**
     * 数据
     */
    public function index()
    {
        $sum = EchartsDataModel::count();
        $todayData = EchartsDataModel::orderBy('id','desc')->first();
        $yesterday_id = EchartsDataModel::where('id','<',$sum)->max('id');
        $information = EchartsDataModel::where('id',$yesterday_id)->first();
        //数据赋值
        $view = [
            'td' => $todayData,
            'yd' => $information,
        ];
        $this->theme->setTitle('数据');
        return $this->theme->scope($this->prefix . '.data.index', $view)->render();
    }

    public function eCharts(){
    $data = EchartsDataModel::get();
    if ($data) {
        $ech = json_encode($data);
        return $ech;
    } else {
        return response()->json(['code' => '1004', 'msg' => '参数错误']);
    }
}

}
