<?php

namespace App\Modules\Task\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaskTypeModel extends Model
{

    
    protected $table = 'task_type';
    public  $timestamps = false;  
    public $fillable = ['id','name','status','desc','created_at','alias', 'sort_order'];

    /**
     * 获取列表
     */
    public static function getList()
    {
        $key = "task_type_all_list";
        if (Cache::has($key)) {
            $data = Cache::get($key);
        } else {
            $data = self::where('status', 1)
                ->orderBy('sort_order')
                ->get();
            Cache::put($key, $data, 24 * 60);
        }
        return $data;
    }

    /**
     * 获取该类型下所有任务
     */
    public function tasks()
    {
        return $this->hasMany('App\Modules\Task\Model\TaskModel', 'type_id');
    }
}
