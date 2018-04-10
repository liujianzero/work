<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\TaskWork::class,
        \App\Console\Commands\TaskSelectWork::class,
        \App\Console\Commands\TaskPublicity::class,
        \App\Console\Commands\TaskDelivery::class,
        \App\Console\Commands\TaskComment::class,
        \App\Console\Commands\TaskNoStick::class,
        //Install kppw
        \App\Modules\Install\Console\Commands\InstallKPPW::class,

        //Update KPPW2.7database
        \App\Console\Commands\VersionMigration::class,
        //KPPW update engine
        \App\Console\Commands\UpdateKPPW::class,

        \App\Console\Commands\EmployAccept::class,
        \App\Console\Commands\EmployDelivery::class,
        \App\Console\Commands\EmployComment::class,
        \App\Console\Commands\EmployDeadline::class,

        \App\Console\Commands\BuyGoods::class,
        \App\Console\Commands\GoodsComment::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
                 ->everyMinute();
        $schedule->command('taskWork')
            ->everyMinute();
        $schedule->command('taskSelectWork')
            ->everyMinute();
        $schedule->command('taskPublicity')
            ->everyMinute();
        $schedule->command('taskDelivery')
            ->everyMinute();
        $schedule->command('taskComment')
            ->everyMinute();
        $schedule->command('taskNoStick')
            ->everyMinute();
        $schedule->command('EmployAccept')
            ->everyMinute();
        $schedule->command('EmployComment')
            ->everyMinute();
        $schedule->command('EmployDeadline')
            ->everyMinute();
        $schedule->command('EmployDelivery')
            ->everyMinute();
        $schedule->command('BuyGoods')
            ->everyMinute();
        $schedule->command('GoodsComment')
            ->everyMinute();
    }
}
