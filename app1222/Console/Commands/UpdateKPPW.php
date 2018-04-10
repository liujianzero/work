<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use File;

class UpdateKPPW extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:kppw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this is kppw update engine';

    //更新文件目录位置
    protected $updatePath;
    //更新时间
    protected $updateTime;
    //迁移文件路径
    protected $migrationPath;
    //填充数据文件目录
    protected $seederPath;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
		
		$this->updateTime = config('kppw.kppw_update_time');

        $this->updatePath = base_path('update');

        $this->seederPath = database_path('seeds/' . $this->updateTime);

        $this->migrationPath = 'database/migrations/' . $this->updateTime;


    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $start = $this->confirm('Please back up the database and the program before you upgrade!!!');

        if ($start){

//            $status = File::copyDirectory($this->updatePath, base_path());

            


//            if ($status){
                //执行字段修改
                $this->call('migrate', [
                    '--path' => $this->migrationPath
                ]);

                //执行数据填充
                $files = File::files($this->seederPath);

                foreach ($files as $file){
                    $filename[] = basename($file, '.' . File::extension($file));
                }
				
                foreach ($filename as $seed){
                    Artisan::call('db:seed', [
                        '--class' => $seed
                    ]);
                }


                //执行完毕清理安装文件
//                File::deleteDirectory($this->updatePath);
//            }
			$this->info('update success');
        }

        
    }
}
