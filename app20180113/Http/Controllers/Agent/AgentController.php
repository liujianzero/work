<?php
namespace App\Http\Controllers\Agent;

use App\Http\Controllers\BasicController;
use App\Modules\Agent\Model\NavBasicModel;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\UserUrlModel;

/**
 * Created by PhpStorm
 * User: phpEr校长
 * Date: 2017/10/19
 * Time: 15:27
 * Email: 7708720@qq.com
 */
class AgentController extends BasicController
{
    public $store_id = 0;

    public function __construct()
    {
        parent::__construct();

        $url = 'http://'.$_SERVER['HTTP_HOST'];
        if( $url != ConfigModel::getConfigByAlias('site_url')->rule ){
            $uid = UserUrlModel::getUidForUrl($_SERVER['HTTP_HOST'])['uid'];
            if(!$uid){
                abort('404');exit;
            }else{
                $this->store_id = $uid;
//                var_dump($uid);exit;
                $navData  = NavBasicModel::getNavBasicData($uid);
                if(!empty($navData)){
                    $this->theme->set( 'agent_nav', $navData['basic'] );
                    $this->theme->set( 'agent_title', $navData['title'] );
                }

            }
        }
    }
    public function index(){
        echo  1;
    }

    public function agent(){
        echo 'agent1';
    }
}