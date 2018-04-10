<?php

namespace App\Console\Commands;

use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\TaskModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TaskDelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskDelivery';

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
        // 扫描当前处于交付验收期的任务
        $task = TaskModel::where('status', 6)->get()->toArray();

        // 判断当前任务是否有稿件交付如果没有就直接将任务失败
        $filled_tasks = self::filledTasks($task);

        // 处理交付期过期但是没有交付任何稿件的
        $time = date('Y-m-d H:i:s');
        if (count($filled_tasks)) {
            foreach($filled_tasks as $v) {
                DB::transaction(function () use($v, $time) {
                    // 修改当前任务状态
                    $update = [
                        'status' => 9,
                        'end_at' => $time
                    ];
                    TaskModel::where('id', $v['id'])->update($update);
                    // 查询当前的任务失败抽成比
                    $task_fail_percentage = TaskModel::where('id', $v['id'])->value('task_fail_draw_ratio');
                    if ($task_fail_percentage != 0) {
                        $balance = $v['bounty'] * (1 - $task_fail_percentage / 100);
                    } else {
                        $balance = $v['bounty'];
                    }
                    UserDetailModel::where('uid', $v['uid'])->increment('balance', $balance);
                    // 产生一条财务记录 任务失败产生一条财务记录
                    $finance_data = [
                        'action' => 7,
                        'pay_type' => 1,
                        'cash' => $balance,
                        'uid' => $v['uid'],
                        'title' => "任务失败，退还赏金【{$v['title']}】"
                    ];
                    FinancialModel::create($finance_data);
                });
            }
        }

        // 查找需要交付的稿件且过期没有交付的
        $successed_tasks = self::filledTasks($task,2);

        // 直接将稿件作废掉
        $woker_expired = self::expireTaskWorker($successed_tasks);
        foreach ($woker_expired as $k => $v) {
            WorkModel::where('task_id', $k)->whereIn('uid', $v)->update(['status' => 5]);
        }

        // 查找需要验收通过的稿件
        $onwer_expired = self::expireTaskOwner($successed_tasks);
        $onwer_expired = array_flatten($onwer_expired);
        // 直接将稿件验收通过
        foreach($onwer_expired as $v)
        {
            $work_data = WorkModel::where('id', $v)->first();
            // 查询任务需要的人数
            $worker_num = TaskModel::where('id', $work_data['task_id'])->first();
            $worker_num = $worker_num['worker_num'];
            // 任务验收通过人数
            $win_check = WorkModel::where('work.task_id', $work_data['task_id'])->where('status', '>', 3)->count();
            $data['worker_num'] = $worker_num;
            $data['win_check'] = $win_check;
            $data['task_id'] = $work_data['task_id'];
            $data['uid'] = $work_data['uid'];
            $data['work_id'] = $v;
            WorkModel::workCheck($data);
        }
    }

    // 判断任务没有交付也没有维权的稿件
    private function expireTaskWorker($data)
    {
        $task_delivery_max_time = \CommonClass::getConfig('task_delivery_max_time');
        $expired_works = [];
        foreach ($data as $v) {
            if (strtotime("{$v['checked_at']} +$task_delivery_max_time day") <= time()) {
                // 查询任务所有需要交付的稿件
                $works = WorkModel::where('task_id', $v['id'])
                    ->whereIn('status', [0, 1])
                    ->lists('uid')
                    ->toArray();
                // 查询任务所有已经交付的稿件
                $works_delivery = WorkModel::where('task_id', $v['id'])
                    ->where('status', '>', 1)
                    ->where('forbidden', 0)
                    ->lists('uid')
                    ->toArray();
                $works_diff = array_diff($works, $works_delivery);
                $expired_works[$v['id']][] = $works_diff;
            }
        }
        return $expired_works;
    }

    private function expireTaskOwner($data)
    {
        $task_check_time_limit = \CommonClass::getConfig('task_check_time_limit');
        $expired_works = [];
        foreach ($data as $v) {
            // 查询任务所有需要验收的稿件
            $works = WorkModel::where('task_id', $v['id'])->where('status', 2)->get()->toArray();
            $works_expired = [];
            foreach ($works as $v1) {
                if (strtotime("{$v1['created_at']} +$task_check_time_limit day") <= time()) {
                    $works_expired[] = $v['id'];
                }
            }
            // 查询任务所有已经验收通过的稿件，以及维权中的稿件
            $works_delivery = WorkModel::where('task_id', $v['id'])->where('status', '>', 2)->lists('id')->toArray();
            $works_diff = array_diff($works_expired,$works_delivery);
            if (count($works_diff) > 0) {
                $expired_works[] = $works_diff;
            }
        }
        return $expired_works;
    }

    // 查询当前的任务是否有交付的work
    private function filledTasks($data, $type = 1)
    {
        $task_delivery_max_time = \CommonClass::getConfig('task_delivery_max_time');
        $filled = [];
        $successed = [];
        foreach ($data as $k => $v) {
            if (strtotime("{$v['checked_at']} +$task_delivery_max_time day") <= time()) {
                $work = WorkModel::where('task_id', $v['id'])
                    ->whereIn('status', [2, 3, 4])
                    ->count();
                if ($work == 0) {
                    $filled[] = $v;
                } else {
                    $successed[] = $v;
                }
            }
        }
        if ($type==1) {
            return $filled;
        } else {
            return $successed;
        }
    }
}
