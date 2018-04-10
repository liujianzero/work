<?php

namespace App\Console\Commands;

use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TaskNoStick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskNoStick';

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
        $stick_hour = 5; // 小时
        //查询当前时间超过置顶时间的任务
        $stickOffTask = TaskModel::from('task as t')
            ->where('t.top_status',1)
            ->where('t.server_status', 2)
            ->where('o.status', 1)
            ->where('o.created_at', '<', date('Y-m-d H:i:s', strtotime("-$stick_hour hour")))
            ->leftjoin('order as o', 'o.task_id', '=', 't.id')
            ->lists('t.id');
        //处理当前的置顶为不置顶
        TaskModel::whereIn('id', $stickOffTask)->update(['top_status' => 0]);

        $stick_hour = 12; // 小时
        //查询当前时间超过加急时间的任务
        $stickOffTask = TaskModel::from('task as t')
            ->where('t.urgent_status',1)
            ->where('t.server_status', 2)
            ->where('o.status', 1)
            ->where('o.created_at', '<', date('Y-m-d H:i:s', strtotime("-$stick_hour hour")))
            ->leftjoin('order as o', 'o.task_id', '=', 't.id')
            ->lists('t.id');
        //处理当前的加急为不加急
        TaskModel::whereIn('id', $stickOffTask)->update(['urgent_status' => 0]);
    }
}
