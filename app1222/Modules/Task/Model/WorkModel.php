<?php

namespace App\Modules\Task\Model;

use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;




class WorkModel extends Model
{
    protected $table = 'work';
    public  $timestamps = false;  
    public $fillable = ['desc','task_id','status','uid','bid_at','created_at'];

    
    public function childrenAttachment()
    {
        return $this->hasMany('App\Modules\Task\Model\WorkAttachmentModel', 'work_id', 'id');
    }

    
    public function childrenComment()
    {
        return $this->hasMany('App\Modules\Task\Model\WorkCommentModel', 'work_id', 'id');
    }
    
    static function isWorker($uid,$task_id)
    {
        $query = Self::where('uid','=',$uid);
        $query = $query->where(function($query) use($task_id){
            $query->where('task_id',$task_id);
        });
        $result = $query->first();
        if($result) return true;

        return false;
    }

    
    static function isWinBid($task_id,$uid)
    {
        $query = Self::where('task_id',$task_id)->where('status',1)->where('uid',$uid);

        $result = $query->first();

        if($result) return $result['status'];

        return false;
    }

    
    static function findAll($id,$data=array())
    {
        $query = Self::select('work.*','us.name as nickname','a.avatar')
            ->where('work.task_id',$id)->where('work.status','<=',1)->where('forbidden',0);
        
        if(isset($data['work_type'])){
            switch($data['work_type'])
            {
                case 1:
                    $query->where('work.status','=',0);
                    break;
                case 2:
                    $query->where('work.status','=',1);
                    break;
            }
        }
        $data = $query->with('childrenAttachment')
            ->with('childrenComment')
            ->join('user_detail as a','a.uid','=','work.uid')
            ->join('users as us','us.id','=','work.uid')
            ->paginate(5)->setPageName('work_page')->toArray();
        return $data;
    }

    
    static function countWorker($task_id,$status)
    {
        $query = Self::where('status',$status);
        $data = $query->where(function($query) use($task_id){
            $query->where('task_id',$task_id);
        })->count();

        return $data;
    }

    
    public function workCreate($data)
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
            
            UserDetailModel::where('uid',$data['uid'])->increment('receive_task_num',1);
            
            TaskModel::where('id',$data['task_id'])->increment('delivery_count',1);
            
            $work = WorkModel::where('task_id',$data['task_id'])->count();
            if($work==1)
            {
                TaskModel::where('id',$data['task_id'])->update(['status'=>4]);
            }
        });

        return is_null($status)?true:false;
    }

    
    public function winBid($data)
    {
        $status = DB::transaction(function() use($data){
            
            Self::where('id',$data['work_id'])->update(['status'=>1]);
            





            
            if(($data['win_bid_num']+1)== $data['worker_num'])
            {
                
                
                
                $task_publicity_day = \CommonClass::getConfig('task_publicity_day');
                if($task_publicity_day==0)
                {
                    TaskModel::where('id',$data['task_id'])->update(['status'=>7,'publicity_at'=>date('Y-m-d H:i:s',time()),'checked_at'=>date('Y-m-d H:i:s',time())]);
                }else{
                    TaskModel::where('id',$data['task_id'])->update(['status'=>6,'publicity_at'=>date('Y-m-d H:i:s',time())]);
                }

            }
        });
        
        if(is_null($status))
        {
            
            $task_win = MessageTemplateModel::where('code_name','task_win')->where('is_open',1)->where('is_on_site',1)->first();
            if($task_win)
            {
                $task = TaskModel::where('id',$data['task_id'])->first();
                $work = WorkModel::where('id',$data['work_id'])->first();
                $user = UserModel::where('id',$work['uid'])->first();
                $site_name = \CommonClass::getConfig('site_name');
                
                $messageVariableArr = [
                    'username'=>$user['name'],
                    'website'=>$site_name,
                    'task_number'=>$task['id'],
                    'task_title'=>$task['title'],
                    'win_price'=>$task['bounty']/$task['worker_num'],
                ];
                $message = MessageTemplateModel::sendMessage('task_win',$messageVariableArr);
                $data = [
                    'message_title'=>'任务中标通知',
                    'message_content'=>$message,
                    'js_id'=>$user['id'],
                    'message_type'=>2,
                    'receive_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>0,
                ];
                MessageReceiveModel::create($data);
            }
        }
        return is_null($status)?true:false;
    }

    
    static public function findDelivery($id,$data)
    {
        $query = Self::select('work.*','us.name as nickname','a.avatar')
            ->where('work.task_id',$id)->where('work.status','>=',2);
        
        if(isset($data['evaluate'])){
            switch($data['evaluate'])
            {
                case 1:
                    $query->where('status','>=',0);
                    break;
                case 2:
                    $query->where('status','>=',1);
                    break;
                case 3:
                    $query->where('status','>=',2);
            }
        }
        $data = $query->with('childrenAttachment')
            ->join('user_detail as a','a.uid','=','work.uid')
            ->leftjoin('users as us','us.id','=','work.uid')
            ->paginate(5)->setPageName('delivery_page')->toArray();
        return $data;
    }

    
    static public function findRights($id)
    {
        $data = Self::select('work.*','us.name as nickname','ud.avatar')
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

    
    static public function workCheck($data)
    {
        $status = DB::transaction(function() use($data) {
            
            Self::where('id', $data['work_id'])->update(['status' => 3, 'bid_at' => date('Y-m-d H:i:s', time())]);
            
            TaskModel::distributeBounty($data['task_id'],$data['uid']);

            
            if(($data['win_check']+1)==$data['worker_num'])
            {
                TaskModel::where('id',$data['task_id'])->update(['status'=>8,'comment_at'=>date('Y-m-d H:i:s',time())]);
            }
        });
        
        if(is_null($status))
        {
            
            $manuscript_settlement = MessageTemplateModel::where('code_name','manuscript_settlement')->where('is_open',1)->where('is_on_site',1)->first();
            if($manuscript_settlement)
            {
                $task = TaskModel::where('id',$data['task_id'])->first();
                $work = WorkModel::where('id',$data['work_id'])->first();
                $user = UserModel::where('id',$work['uid'])->first();
                $site_name = \CommonClass::getConfig('site_name');
                $domain = \CommonClass::getDomain();
                
                
                $messageVariableArr = [
                    'username'=>$user['name'],
                    'task_number'=>$task['id'],
                    'task_link'=>$domain.'/task/'.$task['id'],
                    'website'=>$site_name,
                ];
                $message = MessageTemplateModel::sendMessage('manuscript_settlement',$messageVariableArr);
                $data = [
                    'message_title'=>'任务验收通知',
                    'message_content'=>$message,
                    'js_id'=>$user['id'],
                    'message_type'=>2,
                    'receive_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>0,
                ];
                MessageReceiveModel::create($data);
            }
        }
        return is_null($status)?true:false;
    }


}
