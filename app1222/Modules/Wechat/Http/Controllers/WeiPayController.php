<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/9/1
 * Time: 2:20
 */

namespace App\Modules\Wechat\Http\Controllers;


use App\Modules\Manage\Model\ConfigModel;
use App\Modules\User\Http\Controllers\UserCenterController;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\ModelsOrderGoodsModel;
use App\Modules\User\Model\ModelsOrderModel;
use Illuminate\Support\Facades\Request;

class WeiPayController extends UserCenterController
{
    private $notify='http://www.11dom.com/wePay/wePayNotify';
    private $makesign = '31f176fe59f89cdb497c20a525a12da3';
    private $appid = 'wx825e7ddfe28fe8d0';
    private $key = 'a24d8e4bae2960bc93c2b702b3d7fa34';
    public $orderid = null;

    public function __construct()
    {
        parent::__construct();
        $this->initTheme('orderCenter');//主题初始化
    }

    /**
     * 获取openid
     */
    public function getOpenId($id)
    {
        $notify_url = "http://www.11dom.com/wePay/wxpay/".$id;
        $RequestUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri=$notify_url&response_type=code&scope=snsapi_base&state=1#wechat_redirect";
        header("Location:$RequestUrl");
    }

    /**
     * 支付成功-更改订单状态
     */
    public function wePaySuccess(Request $request)
    {
        $order_id = $_POST['order_id'];
        $update = [
            'pay_status' => 2,
            'pay_at' => date('Y-m-d h:i:s')
        ];
        ModelsOrderModel::where('id', intval($order_id))->update($update);
        return response()->json(['code' => 'success']);
    }

    //进行微信支付
    public function wxpay($id){
        $this->initTheme('myShop.order.orderInfo');
        $data = ModelsOrderModel::find($id);
        $orderData = ModelsOrderGoodsModel::where('order_id',$id)->first();
        $province = DistrictModel::where('upid','=',0)->where('id',$data['province'])->value('name');
        $address = $province.'****'.$data['address'];
        $orderGoods = '购买'.$orderData['goods_number'].'件'.$orderData['goods_name'];

        $reannumb = $this->randomkeys(6);  //生成随机数 以后可以当做 订单号
        $pays = $data['total_price'];                       //获取需要支付的价格
        #插入语句书写的地方
        $conf = $this->payconfig($reannumb,$pays * 100, '十一维度商品支付');
        if (!$conf || $conf['return_code'] == 'FAIL') exit("<script>alert('对不起，微信支付接口调用错误!" . $conf['return_msg'] . "');history.go(-1);</script>");
        $this->orderid = $conf['prepay_id'];
        //生成页面调用参数
        $jsApiObj["appId"] = $conf['appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=" . $conf['prepay_id'];
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->MakeSign($jsApiObj);
        $view = [
            'consignee'     => $data['consignee'],
            'mobile'        => $data['mobile'],
            'address'       => $address,
            'user_desc'     => $data['user_desc'],
            'total_price'   => $data['total_price'],
            'orderGoods'    => $orderGoods,
            'parameters'    => $jsApiObj,
            'order_id'      => $id
        ];
        $this->theme->setTitle('确认订单信息');
        return $this->theme->scope('user.myShop.order.orderConfirm',$view)->render();
    }

    //订单管理
    #微信JS支付参数获取#
    protected function payconfig($no,$fee,$body)
    {
        $oauthConfig = ConfigModel::getOauthConfig('wechatpay');
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data['appid']=$oauthConfig['appId'];
        $data['mch_id']=$oauthConfig['mchId'];
        $data['device_info']='WEB';
        $data['body']=$body;
        $data['out_trade_no'] =$no;
        $data['total_fee'] = $fee;
        $data['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
        $data['notify_url'] = $this->notify;
        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $this->Wxcallback();
        $data['nonce_str'] = $this->createNoncestr();
        $data['sign'] = $this->MakeSign($data);
        $xml = $this->ToXml($data);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL,$url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS,$xml); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        $arr = $this->FromXml($tmpInfo);
        return $arr;

    }

    /**
     *    作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for($i = 0; $i < $length; $i++){
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *    作用：产生随机字符串，不长于32位
     */
    public function randomkeys($length)
    {
        $pattern = '1234567890123456789012345678905678901234';
        $key = null;
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern{mt_rand(0, 30)};    //生成php随机数
        }
        return $key;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public function FromXml($xml)
    {
        //将XML转为array
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function ToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    protected function MakeSign($arr)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->makesign;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    protected function ToUrlParams($arr)
    {
        $buff = "";
        foreach ($arr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }


    public function callback(){
        $xml = file_get_contents("php://input");
        $log = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $id = $log['out_trade_no'];  //获取订单号


        exit('SUCCESS');
    }


    public function Wxcallback()
    {
        $oauthConfig = ConfigModel::getOauthConfig('wechatpay');
        $direct = $this->get_page_url(); //当前访问URL
        //$code =Yii::app()->request->getParam('code');  //获取code码号
        $state = md5(time());
        $code = $_GET['code'];  //获取code码号
        if($code==null){
            header("Location:"."https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$oauthConfig['appId']."&redirect_uri=".urlencode($direct)."&response_type=code&scope=snsapi_base&state=".$state."#wechat_redirect");
        }else{
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$oauthConfig['appId']."&secret=".$this->key."&code={$code}&grant_type=authorization_code";
            $res = $this->request_get($url);
            if($res)
            {
                $data = json_decode($res, true);
                return $data['openid'];
            }else{
                echo json_encode(array('status'=>0,'msg'=>'获取openid出错','v'=>4));
                die();
            }
        }

    }
    public function request_get($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    #获取当前访问完整URL#
    public function get_page_url($site=false){
        $url = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ? 'https://' : 'http://';
        $url .= $_SERVER['HTTP_HOST'];
        if($site) return $this->seldir().'/'; //访问域名网址
        $url .= isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : urlencode($_SERVER['PHP_SELF']) . '?' . urlencode($_SERVER['QUERY_STRING']);
        return $url;
    }
    //返回访问目录
    public function seldir(){
        $baseUrl = str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']));
        //保证为空时能返回可以使用的正常值
        $baseUrl = empty($baseUrl) ? '/' : '/'.trim($baseUrl,'/');
        return 'http://'.$_SERVER['HTTP_HOST'].$baseUrl;
    }

}