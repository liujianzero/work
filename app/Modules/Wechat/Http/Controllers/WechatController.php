<?php

namespace App\Modules\Wechat\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Model\OauthBindModel;
use App\Modules\User\Model\UserLoginModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WechatController extends Controller
{
    public function wechat()
    {
        $wechat = app('wechat');
        $wechatServer = $wechat->server;
        return $wechatServer->serve();
    }

    function accept(){
//        session(['models_id'=>$_GET['id']]);
        $oauthConfig = ConfigModel::getOauthConfig('wechat_api');
        $REDIRECT_URI = url("/wechat/getCode");
//        echo $REDIRECT_URI."<br>";
        $REDIRECT_URI = urlencode($REDIRECT_URI);
//        echo $REDIRECT_URI."<br>";
        $scope = "snsapi_login";
//        echo $scope."<br>";
        $state = md5(time());
//        echo $state."<br>";
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$oauthConfig['appId']."&redirect_uri=".$REDIRECT_URI."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        header("location:$url");
    }
    public function getCode(){
        header("Content-type:text/html;charset=utf-8");
        $oauthConfig = ConfigModel::getOauthConfig('wechat_api');
        $code = $_GET["code"];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$oauthConfig['appId']."&secret=".$oauthConfig['appSecret']."&code=".$code."&grant_type=authorization_code";
        $res = $this->https_request($url);
        $res  = json_decode($res,true);
        $openid = $res["openid"];
        $access_token = $res["access_token"];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->https_request($url);
        $res = json_decode($res,true);
        //查询微信是否有绑定
        $oauth = OauthBindModel::where('oauth_id','=',$res['unionid'])->first();
        if($oauth){ //判断微信是否绑定--绑定
            Auth::loginUsingId($oauth->uid);
            $this->updateLoginInfo($oauth->uid,'127.0.0.1');
        }else{
            $userInfo = [
                'oauth_nickname' => $res['nickname'],
                'oauth_id'       => $res['unionid'],
                'oauth_type'     => 2,
            ];
            $avatar = [
                'avatar'         => $res['headimgurl'],
            ];
            $uid = OauthBindModel::oauthLoginTransaction($userInfo,$avatar);
            Auth::loginUsingId($uid);
            $this->updateLoginInfo($uid,'127.0.0.1');
        }
        $url =  session('url.intended');
        echo $url;
//        $url = url("/view-".$id);
        header("location:$url");
    }

    /**
     * @param $url
     * @param null $data
     * @return mixed
     */
    public function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * 更新用户登录信息
     * @author orh
     * @time   2017-08-02
     *
     * @param  $uid
     * @param  $ip
     * @return void
     */
    public function updateLoginInfo( $uid, $ip ){
        //更新最后登录时间及ip
        $data = ['last_login_time' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip, 'session_id' => session()->getId() ];
        $userData = UserModel::where('id', $uid)->get();
        if($userData != null){
            UserModel::where('id', $uid)->update( $data );
        }

        //追加本次登录信息
        $datai = [
            'uid'        => $uid,
            'login_time' => $data['last_login_time'],
            'login_ip'   => $data['last_login_ip']
        ];
        UserLoginModel::create( $datai );
    }
}
