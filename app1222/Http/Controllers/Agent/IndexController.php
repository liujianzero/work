<?php
/**
 * Created by PhpStorm
 * User: phpEr校长
 * Date: 2017/10/20
 * Time: 10:24
 * Email: 7708720@qq.com
 */

namespace App\Http\Controllers\Agent;


use App\Modules\Agent\Model\RetailIndexModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class IndexController extends AgentController
{
    private $scope = 'agent.retail.';

    public function __construct() {
        parent::__construct ();
//        $this->user = Auth::user ();
        $this->initTheme( 'agent.agent' );
    }

    public function index(){
        $this->theme->setTitle('产品分销');
//        if (Auth::check()) {
//            $users = Auth::Users();
//        }//1031
        $users = Session::get('users','还没有设置');
        /*$a = Auth::routes();
        var_dump($a);exit;*/
        //获取所有为商品并且是商品的数据并展示
        $isRetailGoodsData = RetailIndexModel::RetailHasGoodsData();
//        dd($isRetailGoodsData);exit;
//        $retailNavData = NavTitleModel::get();
//        var_dump($data->toArray());exit;
        $view = [
            'goodsData' => $isRetailGoodsData,
            'users' => $users,
        ];
        return $this->theme->scope( $this->scope . 'index', $view )->render();
    }

    public function agent(){
        echo 'agent11';
    }
}