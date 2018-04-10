<?php

namespace App\Console\Commands;

use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\CommentModel;
use Illuminate\Console\Command;

class TaskComment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskComment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 查询所有处于双方互评阶段的任务，过滤已经超过评价期间的任务
        $tasks = TaskModel::where('status', 7)->get()->toArray();

        // 过滤超过评价期的任务
        $expired_tasks = self::expireTask($tasks);

        // 过滤出已经超过互评期间威客还没有评价的任务
        $expired_work_worker = self::expiredWorker($expired_tasks);
        foreach ($expired_work_worker as $k=>$v) {
            foreach ($v as $value) {
                if (is_array($value)) {
                    $data = [
                        'task_id' => $k,
                        'from_uid' => $value['uid'],
                        'to_uid' => $v['uid'],
                        'comment' => '系统评价',
                        'comment_by' => 2,
                        'speed_score' => 5,
                        'quality_score' => 5,
                        'attitude_score' => 5,
                        'type' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    CommentModel::commentCreate($data);
                }
            }
        }

        //过滤出已经超过互评期间雇主还没有评价的任务
        $expired_work_owner = self::expiredOwner($expired_tasks);
        foreach($expired_work_owner as $k=>$v) {
            foreach ($v as $value) {
                if (is_array($value)) {
                    $data = [
                        'task_id' => $k,
                        'to_uid' => $value['uid'],
                        'from_uid' => $v['uid'],
                        'comment' => '系统评价',
                        'comment_by' => 2,
                        'speed_score' => 5,
                        'quality_score' => 5,
                        'attitude_score' => 5,
                        'type' => 1,
                        'created_at'=>date('Y-m-d H:i:s'),
                    ];
                    CommentModel::create($data);
                }
            }
        }
    }

    /**
     * @param $data
     */
    private function expiredWorker($data)
    {
        $expired_works_id = [];
        foreach ($data as $v) {
            // 查询出威客已经评价的works
            $worker_comment_id = CommentModel::where('task_id', $v['id'])->where('to_uid', $v['uid'])->lists('from_uid')->toArray();
            $works_data = WorkModel::where('task_id', $v['id'])->whereNotIn('uid', $worker_comment_id)->where('status', 3)->get()->toArray();
            if (!empty($works_data)) {
                $expired_works_id[$v['id']] = $works_data;
                $expired_works_id[$v['id']]['uid'] = $v['uid'];
            }
        }
        return $expired_works_id;
    }
    /**
     * @param $data
     */
    private function expiredOwner($data)
    {
        $expired_works_id = [];
        foreach($data as $v)
        {
            //查询出雇主评价的works
            $owner_comment_id = CommentModel::where('task_id',$v['id'])->where('from_uid',$v['uid'])->lists('to_uid')->toArray();
            //查询出要评价的works
            $works_data = WorkModel::where('task_id',$v['id'])->whereNotIn('uid',$owner_comment_id)->where('status',3)->get()->toArray();

            if(!empty($works_data))
            {
                $expired_works_id[$v['id']] = $works_data;
                $expired_works_id[$v['id']]['uid'] = $v['uid'];
            }
        }
        return $expired_works_id;
    }

    private function expireTask($task)
    {
        // 查询系统配置公示期时间
        $task_comment_time_limit = \CommonClass::getConfig('task_comment_time_limit');
        $expire_task = [];
        foreach ($task as $v) {
            // 判断当前的任务是否公示期到期
            if (strtotime("{$v['comment_at']} +$task_comment_time_limit day") <= time()) {
                $expire_task[] = $v;
            }
        }
        return $expire_task;
    }
}
