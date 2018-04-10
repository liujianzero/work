<?php

namespace App\Modules\User\Model;

use App\Modules\Task\Model\WorkModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommentModel extends Model
{

    protected $table = 'comments';
    public $timestamps = false;
    protected $fillable = [
        'task_id', 'from_uid', 'to_uid', 'comment','comment_by','speed_score','quality_score','attitude_score','created_at','type'
    ];



    static public function taskComment($id, $data = [])
    {
        $query = self::select([
            'comments.*',
            'ud.avatar', 'ud.nickname', 'us.name as username',
            'ud_f.nickname as f_nickname', 'us_f.name as f_username'
        ])
            ->where('task_id', $id);
        if (!empty($data['evaluate_type'])) {
            $query->where('type', $data['evaluate_type']);
        }
        if(!empty($data['evaluate_from'])) {
            switch ($data['evaluate_from']) {
                case 1:
                    $query->where('ud.uid', '<>', $data['task_user_id']);
                    break;
                case 2:
                    $query->where('ud.uid', $data['task_user_id']);
            }
        }
        $data = $query->leftjoin('user_detail as ud', 'comments.to_uid', '=', 'ud.uid')
            ->leftjoin('users as us', 'us.id', '=', 'comments.to_uid')
            ->leftjoin('user_detail as ud_f', 'comments.from_uid', '=', 'ud_f.uid')
            ->leftjoin('users as us_f', 'us_f.id', '=', 'comments.from_uid')
            ->paginate(5)
            ->setPageName('comment_page')
            ->toArray();
        return $data;
    }

    // 获取评论数据
    public static function getCommentData($id = 0)
    {
        $list = self::from('comments as c')
            ->select([
                'c.*',
                'u.name as username',
                'ud.nickname',
                'u_f.name as f_username',
                'ud_f.nickname as f_nickname',
                'ud_f.avatar',
            ])
            ->leftjoin('users as u', 'u.id', '=', 'c.to_uid')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 'c.to_uid')
            ->leftjoin('users as u_f', 'u_f.id', '=', 'c.from_uid')
            ->leftjoin('user_detail as ud_f', 'ud_f.uid', '=', 'c.from_uid')
            ->where('c.task_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(3);

        return $list;
    }
    
    static public function applauseRate($id)
    {
        
        $comments = self::where('to_uid',$id)->count();
        $good_comments = self::where('to_uid',$id)->where('type',1)->count();
        if($comments==0){
            $applause_rate = 100;
        }else{
            $applause_rate = ($good_comments/$comments)*100;
        }

        return floor($applause_rate);
    }

    public static function commentCreate($data)
    {
        $status = DB::transaction(function() use($data){
            self::create($data);
            $worker_num = TaskModel::find($data['task_id']);
            $comment_count = self::where('task_id', $data['task_id'])->count();
            if (!empty($worker_num['worker_num'])
                && $worker_num['worker_num'] * 2 == $comment_count) {
                $update = [
                    'status' => 8,
                    'end_at' => date('Y-m-d H:i:s')
                ];
                TaskModel::where('id', $data['task_id'])->update($update);
            }
            if ($data['comment_by'] == 0 && $data['type']==1) {
                UserDetailModel::where('uid', $data['to_uid'])->increment('employer_praise_rate',1);
                UserModel::where('id', $data['to_uid'])->increment('credit_value', 1);
            } elseif ($data['comment_by'] == 1 && $data['type']==1) {
                UserDetailModel::where('uid', $data['to_uid'])->increment('employee_praise_rate',1);
                UserModel::where('id', $data['to_uid'])->increment('credit_value', 1);
            }
        });
        return is_null($status) ? true : false;
    }
}
