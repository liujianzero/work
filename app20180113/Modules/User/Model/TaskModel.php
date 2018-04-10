<?php

namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model
{
    protected $table = 'task';

    /**
     * ��ȡִ������ʱ�������Ϣ
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