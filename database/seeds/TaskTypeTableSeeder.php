<?php

use Illuminate\Database\Seeder;

class TaskTypeTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('task_type')->delete();
        
        \DB::table('task_type')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => '悬赏任务',
                'status' => 1,
                'desc' => '悬赏任务',
                'alias' => 'xuanshang',
                'created_at' => '2016-07-05 18:01:48',
            ),
        ));
        
        
    }
}
