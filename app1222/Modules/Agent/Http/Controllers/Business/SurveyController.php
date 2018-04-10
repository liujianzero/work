<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\EditSetUpModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SurveyController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'survey');
    }

    /**
     * 概况
     */
    public function index()
    {
        $logo = EditSetUpModel::where('store_id',$this->store->id)->first();//获取店铺LOGO
//        dd($logo['logo']);exit;
        //Session::forget('agentAdmin');
        //数据赋值
        $view = [
            'logo' => $logo['logo'],
        ];
        $this->theme->setTitle('概况');
        return $this->theme->scope($this->prefix . '.survey.index', $view)->render();
    }
}
