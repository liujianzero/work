<?php

namespace App\Modules\Finance\Model;

use App\Modules\Manage\Model\ConfigModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class FinancialModel extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'financial';

    protected $fillable = [
        'order_code', 'action', 'pay_type', 'pay_account', 'pay_code', 'cash', 'uid', 'created_at','title'
    ];

    public $timestamps = false;

    /**
     * 创建一条用户的财务
     */
    static function createOne($data)
    {
        $model = new FinancialModel();
        $model->action = $data['action'];
        $model->pay_type = isset($data['pay_type'])?$data['pay_type']:'';
        $model->pay_account = isset($data['pay_account'])?$data['pay_account']:'';
        $model->pay_code = isset($data['pay_code'])?$data['pay_code']:'';
        $model->cash = $data['cash'];
        $model->uid = $data['uid'];
        $model->title = $data['title'];
        $model->created_at = date('Y-m-d H:i:s', time());
        $result = $model->save();

        return $result;
    }

    /**
     * Use:针对余额支付创建一条纪录
     * @param $action
     * @param $money
     * @param $uid
     * @param $title
     * @return bool
     */
    static function createOneRecord($action,$money,$uid,$title,$pay_type = 1){
        $model = new FinancialModel();
        $model->action = $action;
        $model->pay_type = $pay_type;
        $model->cash = $money;
        $model->uid = $uid;
        $model->title = $title;
        $model->created_at = date('Y-m-d H:i:s', time());
        $result = $model->save();
        return $result;
    }

    /**
     * 手续费计算
     *
     * @param $cash
     * @return mixed
     */
    static function getFees($cash)
    {
        $config = ConfigModel::getConfigByAlias('cash');
        $config->rule = json_decode($config->rule, true);

        if ($cash <= 200){
            $fee = $config->rule['per_low'];
        } elseif ($cash > 200){
            $fee = $cash * ($config->rule['per_charge'] / 100);
            if ($fee > $config->rule['per_high']){
                $fee = $config->rule['per_high'];
            }
        }
        return $fee;
    }

}