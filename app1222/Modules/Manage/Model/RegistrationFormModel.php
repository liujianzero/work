<?php


namespace App\Modules\Manage\Model;

use Illuminate\Database\Eloquent\Model;
use RegistrationFormMessagesClass;
use Illuminate\Support\Facades\Mail;

class RegistrationFormModel extends Model
{
    protected $table = 'registration_form';

    public $timestamps = true;

    protected $fillable = [
        'id','name','mobile','course','address','remark'
    ];




    static function sendMsg($data)
    {
//        dd(intval($data['address']));exit;
        $add = intval($data['address']);
        $course = intval($data['course']);
        if ($course = 0) {  //error
            $course = '3DMAX';
        } else {
            $course = '3DMAXWEBVR';
        };
        $remarkDta = $data['remark'];
        if (isset($remarkDta)) {
            $remarkDta = $data['remark'];
        } else {
            $remarkDta = '无';
        };
//        $flag = Mail::send('registrationForm.test',[
        Mail::send('registrationForm.test',[
            'name'   => $data['name'],
            'address'=> $add,
            'course' => $course,
            'mobile' => $data['mobile'],
            'remark' => $remarkDta,
        ], function($message){
            if ($add = 0) { //error
                dd(000);exit;
                $to = '396544421@qq.com';
            } elseif ($add = 1) {
                dd(111);exit;
                $to = '1420234944@qq.com';
            } else {
                dd(222);exit;
                $to = '1420234944@qq.com';
            }
//            $to = '1420234944@qq.com';
            $message ->to($to)->subject('测试邮件');
        });
        /*if($flag){
            echo '发送邮件成功，请查收！';
        }else{
            echo '发送邮件失败，请重试！';
        }*/
    }

}
