<?php


namespace App\Modules\Bre\Model;

use Illuminate\Database\Eloquent\Model;
use RegistrationFormMessagesClass;
use Mail;

class RegistrationFormModel extends Model
{
    protected $table = 'registration_form';

    public $timestamps = true;

    protected $fillable = [
        'id','uid','name','mobile','course','address','remark'
    ];

    static function sendMsg($data, $type)
    {
//        dd($data);exit;
        $parameter = $type;
        switch ($parameter) {
            case '1':
                Mail::send('registrationForm.test',[
                    'name'   => $data['name'],
                    'address'=> $data['address'],
                    'course' => $data['course'],
                    'mobile' => $data['mobile'],
                    'remark' => $data['remark'],
                ], function($message){
                    $to = '1420234944@qq.com';
                    $message ->to($to)->subject('报名信息1');
                });
                break;
            case '2':
                Mail::send('registrationForm.test',[
                      'name'   => $data['name'],
                      'address'=> $data['address'],
                      'course' => $data['course'],
                      'mobile' => $data['mobile'],
                      'remark' => $data['remark'],
                  ], function($message){
                      $to = '1420234944@qq.com';
                      $message ->to($to)->subject('报名信息2');
                  });
                break;
            case '3':
                Mail::send('registrationForm.test',[
                    'name'   => $data['name'],
                    'address'=> $data['address'],
                    'course' => $data['course'],
                    'mobile' => $data['mobile'],
                    'remark' => $data['remark'],
                ], function($message){
                    $to = '1420234944@qq.com';
                    $message ->to($to)->subject('报名信息3');
                });
                break;
            default:
                echo "No number between 1 and 3";
        };
    }

}
