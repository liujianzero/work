<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model
{
    protected $table = 'task';

    /**
     * 获取执行任务时所需的信息
     *
     */
    static function getTaskInfo( $id ){
        $data = TaskModel::where( 'id', $id )->first()->toArray();
        $data = [
            'id'         => $data['id'],
            'title'      => $data['title'],
            'experience' => round( $data['show_cash'] / 100 )
        ];
        return $data;
    }
}