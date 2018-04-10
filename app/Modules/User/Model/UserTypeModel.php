<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/7/14
 * Time: 14:45
 */

namespace App\Modules\User\Model;

use App\Modules\Order\Model\OrderModel;
use App\Modules\User\Http\Controllers\UserCenterController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserTypeModel extends Model
{
    protected $table = 'user_type';

    public $timestamps = false;

    protected $fillable = [
        'type', 'storage','price','type_id','outside','cdn','is_download'
    ];

    /**
     * Use:通过id获取全局数据
     * @param $id
     * @return mixed
     */
    static function getUserTypeData($id){
        $data = UserTypeModel::where("id",intval($id))->first();
        return $data;
    }

    /**
     * Use:通过type_id获取全局数据
     * @param $uid
     * @return mixed
     */
    static function getUserTypeDataForTypeId($uid){
        $data = UserTypeModel::where("type_id",intval($uid))->first();
        return $data;
    }

    /**
     * Use:获取升级套餐最终的时间
     * @param $uid
     * @param $id
     * @return int
     */
    static function getUpgradeTime($uid,$id){
        $nowTime         =  time();
        $userTypeData    = self::getUserTypeDataForTypeId($uid);
        $everyDayPrice   = $userTypeData['price'] / 365;                  //计算每天需要多少钱
        $expireTime      = strtotime( Auth::user()->member_expire_date );
        $leftOverTime    = ($expireTime - $nowTime) / 3600 / 24;           //计算剩下天数
        $leftOverMoney   = $everyDayPrice * $leftOverTime;                 //还剩下的钱
        $userTypeIdData  = self::getUserTypeData(intval($id));
        $presentDayPrice = $userTypeIdData['price'] / 365;                 //升级套餐每天需要多少钱
        $finaLeftOverDay = floor(( $leftOverMoney / $presentDayPrice ) * 3600 * 24); //最终还剩下多少天
        $finalTime       = date('Y-m-d H:i:s',$nowTime + 3600*24*365 + $finaLeftOverDay);
        return $finalTime;
    }

    /**
     * Use:根据会员类型和购买情况获取使用百分比
     * @param $typeId
     * @return string
     */
    static function getCapacityPercentage($typeId){
        $dirName = 'Uploads/Models/'.Auth::User()->id;
        if(file_exists($dirName)){
            $countDir = self::dirSize($dirName); //计算的是b
        }else{
            $countDir = 0;
        }
        $userTypeCapacity = self::getUserTypeDataForTypeId($typeId);
        //转成b字节
        $sto = substr($userTypeCapacity['storage'], -2);
        if($sto == "MB"){
            $storageGetB = intval($userTypeCapacity['storage']) * 1024 * 1024;
        }elseif($sto == "GB"){
            $storageGetB = intval($userTypeCapacity['storage']) * 1024 * 1024  * 1024;
        }
        $userBuyCapacity = OrderModel::getUserCapacity(Auth::User()->id);
        if($userBuyCapacity){
            $storageGetB = $storageGetB + $userBuyCapacity * 1024 * 1024  * 1024;
        }
        $percentage = round(min($countDir / $storageGetB,1) * 100 , 2).'%';
        return $percentage;
    }

    /**
     * Use:根据会员类型和购买情况获取容量
     * @param $typeId
     * @return string
     */
    static function getCapacity($typeId){
        $capacity = self::getUserTypeDataForTypeId($typeId)['storage'];
        $userBuyCapacity = OrderModel::getUserCapacity(Auth::User()->id);
        if($userBuyCapacity){
            $sto = substr($capacity, -2);
            if($sto == "MB"){
                $capacity = ( round( intval($capacity) / 1024 , 2) + $userBuyCapacity ).'GB';
            }else{
                $capacity = ( intval($capacity) + $userBuyCapacity ).'GB';
            }
        }
        return $capacity;
    }

    /**
     * @param $directory
     * @return int
     */
    static function dirSize($directory){
        $dir_size = 0; //用来累加各个文件大小
        if($dir_handle = @opendir($directory)){      //打开目录，并判断是否能成功打开
            while($filename = readdir($dir_handle)){     //循环遍历目录下的所有文件
                if($filename != "."&& $filename != ".."){     //一定要排除两个特殊的目录
                    $subFile = $directory."/".$filename;     //将目录下的子文件和当前目录相连
                    if(is_dir($subFile))     //如果为目录
                        $dir_size += self::dirSize($subFile);     //递归地调用自身函数，求子目录的大小
                    else    //如果是文件
                        $dir_size += filesize($subFile);     //求出文件的大小并累加
                }
            }
            closedir($dir_handle);      //关闭文件资源
            return $dir_size;     //返回计算后的目录大小
        }
    }
}