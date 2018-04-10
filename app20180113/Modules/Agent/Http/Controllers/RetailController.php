<?php

/**
 * Created by PhpStorm
 * User: phpEr校长
 * Date: 2017/10/9
 * Time: 10:31
 * Email: 7708720@qq.com
 */
namespace App\Modules\Agent\Http\Controllers;


use App\Http\Controllers\AgentController;
use App\Http\Controllers\UserCenterController;
use App\Modules\Agent\Model\RetailIndexModel;
use Illuminate\Support\Facades\Auth;

use App\Modules\Agent\Model\NavBasicModel;
use App\Modules\Agent\Model\NavTitleModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\UserUrlModel;
use Illuminate\Http\Request;
use App\Http\Requests;


class RetailController extends AgentController
{

    private $scope = 'agent.retail.';

    public function __construct() {
        parent::__construct ();
        $this->user = Auth::user ();
        $this->initTheme( 'agent.agent' );
    }

    /**
     * Use:代理分销首页
     * @return mixed
     */
    public function index(){
        //获取所有为商品并且是商品的数据并展示
        $isRetailGoodsData = RetailIndexModel::RetailHasGoodsData();
//        $retailNavData = NavTitleModel::get();
//        var_dump($data->toArray());exit;

        $view = [
            'goodsData' => $isRetailGoodsData,
        ];

        return $this->theme->scope( $this->scope.'index', $view )->render();

    }

    /**
     * Use:
     * @param $id
     * @return mixed
     */
    public function models($id){
        $view = [
            'id' => $id,
        ];
        return $this->theme->scope( $this->scope.'models', $view )->render();
    }

    /**
     * Use:分销商品上架处（点击之后弹出的内容）
     * @param $id
     * @return mixed
     */
    public function putUpPop($id){
        $this->initTheme ( 'blank' );
        return $this->theme->scope( $this->scope.'putUpPop' )->render();
    }

}