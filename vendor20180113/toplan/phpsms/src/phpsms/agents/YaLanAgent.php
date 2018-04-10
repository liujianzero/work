<?php

namespace Toplan\PhpSms;

/**
 * Class YunPianAgent
 *
 * @property string $apikey
 */
class YaLanAgent extends Agent
{
    public function sendSms($to, $content, $tempId, array $data)
    {
        $this->sendContentSms($to, $content);
    }

    public function sendContentSms($to, $content)
    {
        $account = $this->account;
        $password = $this->password;
        $postArr = array (
        		'account'  =>  $this->account,
        		'password' => $this->password,
        		'msg' => urlencode($content),
        		'phone' => $to,
        		'report' => 'true'
        );
        
       $response = $this->curl($this->url, $postArr,true);

        $this->setResult($response['response']);
    }

    public function voiceVerify($to, $code, $tempId, array $data)
    {
        $url = 'http://voice.yunpian.com/v1/voice/send.json';
        $apikey = $this->apikey;
        $postString = "apikey=$apikey&code=$code&mobile=$to";
        $response = $this->sockPost($url, $postString);
        $this->setResult($response);
    }

    protected function setResult($result)
    {
	
        $this->result(Agent::INFO, $result);
        $result = json_decode($result, true);
        $this->result(Agent::SUCCESS, $result['code'] === '0');
        $this->result(Agent::CODE, $result['code']);
    }

    public function sendTemplateSms($to, $tempId, array $data)
    {
    }
}
