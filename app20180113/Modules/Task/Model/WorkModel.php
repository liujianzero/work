<?php

namespace App\Modules\Task\Model;

use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;




class WorkModel extends Model
{
    protected $table = 'work';
    public  $timestamps = false;  
    public $fillable = [
        'desc', 'task_id', 'status',
        'uid', 'bid_at', 'created_at',
        'bidding_price', 'work_time',
        'action_id'
    ];

    
    public function childrenAttachment()
    {
        return $this->hasMany('App\Modules\Task\Model\WorkAttachmentModel', 'work_id', 'id');
    }

    
    public function childrenComment()
    {
        return $this->hasMany('App\Modules\Task\Model\WorkCommentModel', 'work_id', 'id');
    }
    
    static function isWorker($uid, $task_id)
    {
        $result = self::where('uid', $uid)
            ->where('task_id', $task_id)
            ->first();
        return $result;
    }

    
    static function isWinBid($task_id, $uid)
    {
        $result = self::where('task_id', $task_id)
            ->where('status', 1)
            ->where('uid', $uid)
            ->first();
        if ($result) {
            return $result['status'];
        } else {
            return false;
        }
    }

    
    static function findAll($id, $data = [])
    {
        $query = self::select('work.*', 'us.name as username', 'a.avatar', 'a.nickname')
            ->where('work.task_id', $id)
            ->where('work.status', '<=',1)
            ->where('forbidden', 0);
        if (isset($data['work_type'])) {
            switch ($data['work_type']) {
                case 1:
                    $query->where('work.status', 0);
                    break;
                case 2:
                    $query->where('work.status', 1);
                    break;
            }
        }
        $data = $query->with('childrenAttachment')
            ->with('childrenComment')
            ->join('user_detail as a','a.uid','=','work.uid')
            ->join('users as us','us.id','=','work.uid')
            ->paginate(5)
            ->setPageName('work_page')
            ->toArray();
        return $data;
    }

    // 获取稿件数据
    public static function getWorkData($id = 0)
    {
        $list = self::from('work as w')
            ->select([
                'w.*',
                'u.name as username',
                'ud.avatar',
                'ud.nickname',
                DB::raw('count(`' . config('database.connections.mysql.prefix') . 'c`.`id`) as comments'),
                DB::raw('count(if(`' . config('database.connections.mysql.prefix') . 'c`.`type`=1, true, null)) as good'),
            ])
            ->leftJoin('users as u', 'u.id', '=', 'w.uid')
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'w.uid')
            ->leftJoin('comments as c', 'c.to_uid', '=', 'w.uid')
            ->where('w.task_id', $id)
            ->where('w.status', '<', 2)
            ->where('w.forbidden', 0)
            ->with('childrenAttachment')
            ->groupBy('w.id')
            ->orderBy('created_at', 'desc')
            ->paginate(3);
        return $list;
    }

    // 获取交稿数据
    public static function getDeliveryData($id = 0)
    {
        $list = self::from('work as w')
            ->select([
                'w.*',
                'u.name as username',
                'ud.avatar',
                'ud.nickname',
                DB::raw('count(`' . config('database.connections.mysql.prefix') . 'c`.`id`) as comments'),
                DB::raw('count(if(`' . config('database.connections.mysql.prefix') . 'c`.`type`=1, true, null)) as good'),
            ])
            ->leftJoin('users as u', 'u.id', '=', 'w.uid')
            ->leftJoin('user_detail as ud', 'ud.uid', '=', 'w.uid')
            ->leftJoin('comments as c', 'c.to_uid', '=', 'w.uid')
            ->where('w.task_id', $id)
            ->where('w.status', '>', 1)
            ->where('w.forbidden', 0)
            ->with('childrenAttachment')
            ->groupBy('w.id')
            ->orderBy('created_at', 'desc')
            ->paginate(3);
        return $list;
    }
    
    static function countWorker($task_id,$status)
    {
        $query = self::where('status',$status);
        $data = $query->where(function($query) use($task_id){
            $query->where('task_id',$task_id);
        })->count();

        return $data;
    }

    
    public static function workCreate($data)
    {
        $status = DB::transaction(function() use ($data) {
            $result = WorkModel::create($data);
            if ($data['file_id']) {
                $file_able_ids = AttachmentModel::select('id', 'type')
                    ->whereIn('id', $data['file_id'])
                    ->where('status', 0)
                    ->get();
                $insert = [];
                foreach($file_able_ids as $v){
                    $insert[] = [
                        'task_id' => $data['task_id'],
                        'work_id' => $result->id,
                        'attachment_id' => $v->id,
                        'type' => $v->type,
                        'created_at' => $data['created_at'],
                    ];
                }
                WorkAttachmentModel::insert($insert);
            }
            UserDetailModel::where('uid', $data['uid'])->increment('receive_task_num', 1);
            TaskModel::where('id', $data['task_id'])->increment('delivery_count', 1);
        });
        return is_null($status) ? true : false;
    }

    
    public static function winBid($data)
    {
        $status = DB::transaction(function () use ($data) {
            $task = TaskModel::find($data['task_id']);
            $time = date('Y-m-d H:i:s');
            $update = ['status' => 1];
            self::where('id', $data['work_id'])->update($update);
            if ($task->type_id != 2) {
                $info = self::find($data['work_id']);
                $create = [
                    'task_id' => $info->task_id,
                    'bidding_price' => $info->bidding_price,
                    'work_time' => $info->work_time,
                    'action_id' => $info->action_id,
                    'desc' => $info->desc,
                    'status' => 2,
                    'forbidden' => $info->forbidden,
                    'uid' => $info->uid,
                    'bid_by' => $info->bid_by,
                    'bid_at' => $info->bid_at,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $info = self::create($create);
                $update = [
                    'is_private' => 0
                ];
                ModelsContentModel::where('id', $create['action_id'])->update($update);
                if ($task->type_id == 4) {
                    $new['uid'] = $info->uid;
                    $new['work_id'] = $info->id;
                    $new['worker_num'] = TaskModel::where('id', $info->task_id)->value('worker_num');
                    $new['win_check'] = self::where('task_id', $info->task_id)->where('status', '>', 2)->count();
                    $new['task_id'] = $info->task_id;
                    $new['work_status'] = 3;
                    $new['store_uid'] = $task->uid;
                    $new['action_id'] = $info->action_id;
                    WorkModel::workCheck($new);
                }
            }
            if (($data['win_bid_num'] + 1) == $data['worker_num']) {
                if ($task->type_id == 2) {
                    $work = self::find($data['work_id']);
                    $update = [
                        'status' => 4,
                        'selected_work_at' => $time,
                        'bounty' => $work->bidding_price
                    ];
                } else {
                    $update = [
                        'status' => 7,
                        'selected_work_at' => $time,
                        'work_at' => $time,
                        'checked_at' => $time,
                        'comment_at' => $time,
                    ];
                }
                if (in_array($task->type_id, [1, 3])) {
                    $works = self::where('task_id', $task->id)
                        ->where('status', 2)
                        ->get();
                    foreach ($works as $v) {
                        $new['uid'] = $v->uid;
                        $new['work_id'] = $v->id;
                        $new['worker_num'] = TaskModel::where('id', $v->task_id)->value('worker_num');
                        $new['win_check'] = self::where('task_id', $v->task_id)->where('status', '>', 2)->count();
                        $new['task_id'] = $v->task_id;
                        $new['work_status'] = 3;
                        $new['store_uid'] = $task->uid;
                        $new['action_id'] = $v->action_id;
                        WorkModel::workCheck($new);
                    }
                }
                TaskModel::where('id', $task->id)->update($update);
            }
        });
        $status = is_null($status) ? true : false;
        if ($status) {
            $task_win = MessageTemplateModel::where('code_name', 'task_win')
                ->where('is_open', 1)
                ->where('is_on_site', 1)
                ->first();
            if ($task_win) {
                $task = TaskModel::where('id', $data['task_id'])->first();
                $work = WorkModel::where('id', $data['work_id'])->first();
                $user = UserModel::where('id', $work['uid'])->first();
                $site_name = \CommonClass::getConfig('site_name');
                $messageVariableArr = [
                    'username' => $user['name'],
                    'website' => $site_name,
                    'task_number' => $task['id'],
                    'task_title' => $task['title'],
                    'win_price' => $task['bounty'] / $task['worker_num'],
                ];
                $message = MessageTemplateModel::sendMessage('task_win', $messageVariableArr);
                $data = [
                    'message_title' => '任务中标通知',
                    'message_content' => $message,
                    'js_id' => $user['id'],
                    'message_type' => 2,
                    'receive_time' => date('Y-m-d H:i:s'),
                    'status' => 0
                ];
                MessageReceiveModel::create($data);
            }
        }
        return $status;
    }

    
    static public function findDelivery($id, $data)
    {
        $query = self::select('work.*', 'us.name as username', 'a.avatar', 'a.nickname')
            ->where('work.task_id', $id)
            ->where('work.status', '>=', 2);
        if (isset($data['evaluate'])) {
            switch ($data['evaluate']) {
                case 1:
                    $query->where('status', '>=', 0);
                    break;
                case 2:
                    $query->where('status', '>=', 1);
                    break;
                case 3:
                    $query->where('status', '>=', 2);
            }
        }
        $data = $query->with('childrenAttachment')
            ->join('user_detail as a', 'a.uid','=', 'work.uid')
            ->leftjoin('users as us', 'us.id','=', 'work.uid')
            ->paginate(5)
            ->setPageName('delivery_page')
            ->toArray();
        return $data;
    }

    static public function findRights($id)
    {
        $data = self::select('work.*', 'ud.nickname', 'us.name as username', 'ud.avatar')
            ->where('task_id',$id)->where('work.status',4)
            ->with('childrenAttachment')
            ->join('user_detail as ud','ud.uid','=','work.uid')
            ->leftjoin('users as us','us.id','=','work.uid')
            ->paginate(5)->setPageName('delivery_page')->toArray();
        return $data;
    }
    
    static public function delivery($data)
    {
        $status = DB::transaction(function() use($data){
            
            $result = WorkModel::create($data);

            if(isset($data['file_id'])){
                $file_able_ids = AttachmentModel::select('attachment.id','attachment.type')->whereIn('id',$data['file_id'])->get()->toArray();
                
                foreach($file_able_ids as $v){
                    $work_attachment = [
                        'task_id'=>$data['task_id'],
                        'work_id'=>$result['id'],
                        'attachment_id'=>$v['id'],
                        'type'=>$v['type'],
                        'created_at'=>date('Y-m-d H:i:s',time()),
                    ];
                    WorkAttachmentModel::create($work_attachment);
                }
            }





        });

        return is_null($status)?true:false;
    }


    public static function workCheck($data)
    {
        $status = DB::transaction(function() use($data) {
            $update = [
                'status' => 3,
                'bid_at' => date('Y-m-d H:i:s')
            ];
            self::where('id', $data['work_id'])->update($update);
            TaskModel::distributeBounty($data['task_id'], $data['uid']);
            if (($data['win_check'] + 1) == $data['worker_num']) {
                $update = [
                    'status' => 7,
                    'comment_at' => date('Y-m-d H:i:s')
                ];
                TaskModel::where('id',$data['task_id'])->update($update);
            }
            $update = [
                'uid' => $data['store_uid'],
                'is_private' => 1,
                'folder_id' => 0,
                'is_goods' => 0,
                'price' => 0,
                'transaction_mode' => 0,
                'goods_type_id' => 0,
                'goods_number' => 0,
                'is_on_sale' => 'N',
                'goods_cat_id' => 0,
                'old_uid' => 0
            ];
            ModelsContentModel::where('id', $data['action_id'])
                ->where('uid', $data['uid'])
                ->update($update);
        });
        $status = is_null($status) ? true : false;
        if ($status) {
            $manuscript_settlement = MessageTemplateModel::where('code_name', 'manuscript_settlement')
                ->where('is_open', 1)
                ->where('is_on_site', 1)
                ->first();
            if ($manuscript_settlement) {
                $task = TaskModel::find($data['task_id']);
                $work = WorkModel::find($data['work_id']);
                $username = UserModel::where('id', $work['uid'])->value('name');
                $nickname = UserDetailModel::where('uid', $work['uid'])->value('nickname');
                $site_name = \CommonClass::getConfig('site_name');
                $domain = \CommonClass::getDomain();
                $messageVariableArr = [
                    'username' => $nickname ? $nickname : $username,
                    'task_number' => $task->id,
                    'task_link' => "$domain/task/$task->id",
                    'website' => $site_name,
                ];
                $message = MessageTemplateModel::sendMessage('manuscript_settlement', $messageVariableArr);
                $data = [
                    'message_title' => '任务验收通知',
                    'message_content' => $message,
                    'js_id' => $work['uid'],
                    'message_type' => 2,
                    'receive_time' => date('Y-m-d H:i:s'),
                    'status' =>0
                ];
                MessageReceiveModel::create($data);
            }
        }
        return $status;
    }


}
