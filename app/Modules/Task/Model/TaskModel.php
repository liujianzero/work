<?php

namespace App\Modules\Task\Model;

use App\Modules\Agent\Http\Controllers\HelpsController;
use App\Modules\Employ\Models\EmployUserModel;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\Order\Model\OrderModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use App\Modules\Task\Model\TaskCateModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

//use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
//use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
//use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class TaskModel extends Model
{
    protected $table = 'task';
    protected $fillable = [
        'title', 'desc', 'type_id', 'cate_id', 'phone', 'region_limit', 'status', 'bounty', 'bounty_status', 'created_at', 'updated_at',
        'verified_at', 'begin_at', 'end_at', 'delivery_deadline', 'show_cash', 'real_cash', 'deposit_cash', 'province', 'city', 'area',
        'view_count', 'delivery_count', 'uid', 'username', 'worker_num', 'selected_work_at', 'publicity_at', 'checked_at', 'comment_at',
        'top_status', 'task_success_draw_ratio', 'task_fail_draw_ratio', 'engine_status', 'work_status', 'cate_pid', 'verified_status',
        'urgent_status', 'action_id', 'bid_at', 'work_at'
    ];
    public function province()
    {
        return $this->hasOne('App\Modules\User\Model\DistrictModel','id','province');
    }
    public function city()
    {
        return $this->hasOne('App\Modules\User\Model\DistrictModel','id','city');
    }

    public static function myTasks($data, $perPage = 5)
    {
        $list = TaskModel::from('task as t')
            ->select([
                't.*',
                'tt.name as type_name',
                'dp.name as province_name',
                'dc.name as city_name',
                'u.name as username',
                'ud.nickname', 'ud.avatar',
                'c.name as cate_name',
                'tpr.min_price', 'tpr.max_price'
            ])
            ->where('t.uid', $data['uid']);
        if ($status = $data['status']) {
            $list->where('t.status', $status);
        }
        if ($time = $data['time']) {
            $start = date('Y-m-d H:i:s', strtotime("-{$time} month"));
            $end = date('Y-m-d H:i:s');
            $list->whereBetween('t.created_at', [$start, $end]);
        }
        if ($type_id = $data['type_id']) {
            $list->where('t.type_id', $type_id);
        }
        $list = $list->leftjoin('task_type as tt', 'tt.id', '=', 't.type_id')
            ->leftjoin('district as dp', 'dp.id', '=', 't.province')
            ->leftjoin('district as dc', 'dc.id', '=', 't.city')
            ->leftjoin('users as u', 'u.id', '=', 't.uid')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 't.uid')
            ->leftjoin('cate as c', 'c.id', '=', 't.cate_id')
            ->leftjoin('task_price_ranges as tpr', 'tpr.id', '=', 't.action_id')
            ->orderBy('t.created_at', 'desc')
            ->paginate($perPage);
        return $list;
    }

    /**
     * 任务筛选
     * @param $data
     * @param $paginate
     * @return mixed
     * author: muker（qq:372980503）
     */
    public static function findBy($data, $paginate = 10)
    {
        $query = self::select('task.*', 'b.name as type_name', 'us.name as user_name', 'ud.nickname as nickname', 'tpr.min_price', 'tpr.max_price')
            ->where('task.verified_status', 3)
            ->whereBetween('task.status', [3, 8])
            ->where(function ($query){
                $query->where(function ($type){
                    $type->where('type_id', 2);
                })->orWhere(function ($type){
                    $type->where('type_id', '!=', 2)
                        ->where('bounty_status', 2);
                });
            })
            ->orderBy('task.server_status', 'desc')
            ->orderBy('task.top_status', 'desc')
            ->orderBy('task.urgent_status', 'desc');
            // ->orderBy('task.bounty_status', 'desc');
        //关键词筛选
        if (isset($data['keywords'])) {
            $query->where('task.title', 'like', '%' . e($data['keywords']) . '%');
        }
        //类别筛选
        if (isset($data['category']) && $data['category'] != 0) {
            //查询所有的底层id
            $category_ids = TaskCateModel::findCateIds($data['category']);
            $query->whereIn('cate_id', $category_ids);
        }
        //地区筛选
        if (isset($data['province'])) {
            $query->where('task.province', intval($data['province']));
        }
        if (isset($data['city'])) {
            $query->where('task.city', intval($data['city']));
        }
        if (isset($data['area'])) {
            $query->where('task.area', intval($data['area']));
        }
        //任务状态
        if (isset($data['status'])) {
            $query->where('task.status', $data['status']);
        }
        //交易模式
        if (isset($data['type_id'])) {
            $query->where('task.type_id', $data['type_id']);
        }
        //排序
        if (isset($data['desc']) && $data['desc'] != 'created_at') {
            $query->orderBy($data['desc'], 'desc');
        } elseif (isset($data['desc']) && $data['desc'] == 'created_at') {
            $query->orderBy('created_at');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $data = $query->join('task_type as b', 'task.type_id', '=', 'b.id')
            ->leftjoin('users as us', 'us.id', '=', 'task.uid')
            ->leftjoin('user_detail as ud', 'task.uid', '=', 'ud.uid')
            ->leftjoin('task_price_ranges as tpr', 'task.action_id', '=', 'tpr.id')
            ->paginate($paginate);
        return $data;
    }

    /**
     * 任务筛选
     * @param $data
     * @return mixed
     * author: muker（qq:372980503）
     */
    static function findByCity($data, $city)
    {
        $query = self::select('task.*', 'b.name as type_name', 'us.name as user_name')->where('task.status', '>', 2)
            ->where('task.bounty_status', 1)->where('task.status', '<=', 9)->where('begin_at', "<=", date('Y-m-d H:i:s', time()))
            ->where('task.region_limit', 1)
            ->orderBy('top_status', 'desc');
        //关键词筛选
        if (isset($data['keywords'])) {
            $query = $query->where('task.title', 'like', '%' . e($data['keywords']) . '%');
        }
        //类别筛选
        if (isset($data['category']) && $data['category'] != 0) {
            //查询所有的底层id
            $category_ids = TaskCateModel::findCateIds($data['category']);
            $query->whereIn('cate_id', $category_ids);
        }
        //地区筛选
        if (isset($city)) {
            $query->where(function ($query) use ($city) {
                $query->where('province', $city)->orwhere('city', $city);
            });
        }

        if (isset($data['area'])) {
            $query->where(function ($query) use ($data) {
                $query->where('city', $data['area'])->orwhere('area', $data['area']);
            });
        }
        //任务状态
        if (isset($data['status'])) {
            switch ($data['status']) {
                case 1:
                    $status = [4];
                    break;
                case 2:
                    $status = [5];
                    break;
                case 3:
                    $status = [6, 7];
                    break;
                case 4:
                    $status = [8, 9];
                    break;
            }
            $query->whereIn('task.status', $status);
        }
        //排序
        if (isset($data['desc']) && $data['desc'] != 'created_at') {
            $query->orderBy($data['desc'], 'desc');
        } elseif (isset($data['desc']) && $data['desc'] == 'created_at') {
            $query->orderBy('created_at');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $data = $query->join('task_type as b', 'task.type_id', '=', 'b.id')
            ->leftjoin('users as us', 'us.id', '=', 'task.uid')
            ->paginate(10);

        return $data;
    }

    /**
     * 创建一个任务
     * @param $data
     * @return mixed
     */
    static public function createTask($data)
    {
        $status = DB::transaction(function () use ($data) {
            $result = self::create($data);
            if (!empty($data['file_id'])) {
                //查询用户的附件记录，排除掉用户删除的附件记录
                $file_able_ids = AttachmentModel::fileAble($data['file_id']);
                $file_able_ids = array_flatten($file_able_ids);

                foreach ($file_able_ids as $v) {
                    $attachment_data = [
                        'task_id' => $result['id'],
                        'attachment_id' => $v,
                        'created_at' => date('Y-m-d H:i:s', time()),
                    ];
                    TaskAttachmentModel::create($attachment_data);
                }
                //修改附件的发布状态
                $attachmentModel = new AttachmentModel();
                $attachmentModel->statusChange($file_able_ids);
            }

            if (!empty($data['product'])) {
                foreach ($data['product'] as $k => $v) {
                    $server = ServiceModel::where('id', $v)->first();
                    if ($server['identify'] == 'ZHIDING') {
                        self::where('id', $result['id'])->update(['top_status' => 1]);
                    }
                    if ($server['identify'] == 'SOUSUOYINGQINGPINGBI') {
                        self::where('id', $result['id'])->update(['engine_status' => 1]);
                    }
                    if ($server['identify'] == 'GAOJIANPINGBI') {
                        self::where('id', $result['id'])->update(['work_status' => 1]);
                    }
                    $service_data = [
                        'task_id' => $result['id'],
                        'service_id' => $v,
                        'created_at' => date('Y-m-d H:i:s', time()),
                    ];
                    TaskServiceModel::create($service_data);
                }
            }
            return $result;
        });
        return $status;
    }


    /**
     * 根据id查询任务
     * @param $id
     */
    static function findById($id)
    {
        $data = self::select('task.*', 'b.name as cate_name', 'c.name as type_name')
            ->where('task.id', '=', $id)
            ->leftjoin('cate as b', 'task.cate_id', '=', 'b.id')
            ->leftjoin('task_type as c', 'task.type_id', '=', 'c.id')
            ->first();

        return $data;
    }

    /**
     * 计算用户的任务金额
     */
    public function taskMoney($id)
    {
        $bounty = self::select('task.bounty')->where('id', '=', $id)->first();
        $bounty = $bounty['bounty'];
        $service = TaskServiceModel::select('task_service.service_id')
            ->where('task_id', '=', $id)->get()->toArray();
        $service = array_flatten($service);
        $serviceModel = new ServiceModel();
        $service_money = $serviceModel->serviceMoney($service);
        $money = $bounty + $service_money;

        return $money;
    }

    static function employbounty($money, $task_id, $uid, $code, $type = 2)
    {
        $status = DB::transaction(function () use ($money, $task_id, $uid, $code, $type) {
            //扣除用户的余额
            $query = DB::table('user_detail')->where('uid', '=', $uid);
            $query->where(function ($query) {
                $query->where('balance_status', '!=', 1);
            })->decrement('balance', $money);
            //修改任务的赏金托管状态
            $data = self::where('id', $task_id)->update(['bounty_status' => 1]);
            //生成财务记录，action 1表示发布任务
            $financial = [
                'action' => 1,
                'pay_type' => $type,
                'cash' => $money,
                'uid' => $uid,
                'created_at' => date('Y-m-d H:i:s', time())
            ];
            FinancialModel::create($financial);
            //修改订单状态
            OrderModel::where('code', $code)->update(['status' => 1]);

            //修改用户的托管状态
            self::where('id', '=', $task_id)->update(['status' => 0]);

            //增加用户的发布任务数量
            UserDetailModel::where('uid', $uid)->increment('publish_task_num', 1);
        });

        return is_null($status) ? true : false;
    }

    /**
     * 赏金托管数据操作
     * @param $money
     * @param $uid
     * @param $task_id
     */
    static function bounty($money, $task_id, $uid, $code, $type = 1)
    {
        $status = DB::transaction(function () use ($money, $task_id, $uid, $code, $type) {
            //扣除用户的余额
            $query = DB::table('user_detail')->where('uid', '=', $uid);
            $query->where(function ($query) {
                $query->where('balance_status', '!=', 1);
            })->decrement('balance', $money);
            //修改任务的赏金托管状态
            $data = self::where('id', $task_id)->update(['bounty_status' => 1]);
            //生成财务记录，action 1表示发布任务
            $financial = [
                'action' => 1,
                'pay_type' => $type,
                'cash' => $money,
                'uid' => $uid,
                'created_at' => date('Y-m-d H:i:s', time())
            ];
            FinancialModel::create($financial);
            //修改订单状态
            OrderModel::where('code', $code)->update(['status' => 1]);

            //修改用户的托管状态
            //判断用户的赏金是否大于系统的任务审核金额
            $bounty_limit = \CommonClass::getConfig('task_bounty_limit');
            if ($bounty_limit < $money) {
                self::where('id', '=', $task_id)->update(['status' => 3]);
            } else {
                self::where('id', '=', $task_id)->update(['status' => 2]);
            }
            //增加用户的发布任务数量
            UserDetailModel::where('uid', $uid)->increment('publish_task_num', 1);
        });
        //如果托管成功就发送一条系统消息
        if (is_null($status)) {
            //判断当前的任务发布成功之后是否需要发送系统消息
            $task_publish_success = MessageTemplateModel::where('code_name', 'task_publish_success')->where('is_open', 1)->where('is_on_site', 1)->first();
            if ($task_publish_success) {
                $task = self::where('id', $task_id)->first()->toArray();
                $task_status = [
                    'status' => [
                        0 => '暂不发布',
                        1 => '已经发布',
                        2 => '赏金托管',
                        3 => '审核通过',
                        4 => '威客交稿',
                        5 => '雇主选稿',
                        6 => '任务公示',
                        7 => '交付验收',
                        8 => '双方互评'
                    ]
                ];
                $task = \CommonClass::intToString([$task], $task_status);
                $task = $task[0];
                $user = UserModel::where('id', $uid)->first();//必要条件
                $site_name = \CommonClass::getConfig('site_name');//必要条件
                $domain = \CommonClass::getDomain();
                //组织好系统消息的信息
                //发送系统消息
                $messageVariableArr = [
                    'username' => $user['name'],
                    'task_number' => $task['id'],
                    'task_title' => $task['title'],
                    'task_status' => $task['status_text'],
                    'website' => $site_name,
                    'href' => $domain . '/task/' . $task['id'],
                    'task_link' => $task['title'],
                    'start_time' => $task['begin_at'],
                    'manuscript_end_time' => $task['delivery_deadline'],
                ];
                $message = MessageTemplateModel::sendMessage('task_publish_success', $messageVariableArr);
                $data = [
                    'message_title' => $task_publish_success['name'],
                    'code' => 'task_publish_success',
                    'message_content' => $message,
                    'js_id' => $user['id'],
                    'message_type' => 2,
                    'receive_time' => date('Y-m-d H:i:s', time()),
                    'status' => 0,
                ];
                MessageReceiveModel::create($data);
            }
        }
        return is_null($status) ? true : false;
    }

    /**
     * 查询任务详情
     * @param $id
     */
    public static function detail($id)
    {
        $data = self::select('task.*', 'a.name as user_name', 'b.name as type_name', 'c.name as cate_name', 'ud.nickname as nickname', 'tpr.min_price', 'tpr.max_price')
            ->where('task.id', '=', $id)
            ->orWhere('task.status', 10)
            ->whereBetween('task.status', [3, 8])
            ->where('task.verified_status', 3)
            ->where(function ($query){
                $query->where(function ($type){
                    $type->where('type_id', 2);
                })->orWhere(function ($type){
                    $type->where('type_id', '<>', 2)
                        ->where('bounty_status', 2);
                });
            })
            ->leftjoin('users as a', 'a.id', '=', 'task.uid')
            ->leftjoin('task_type as b', 'b.id', '=', 'task.type_id')
            ->leftjoin('cate as c', 'c.id', '=', 'task.cate_id')
            ->leftjoin('user_detail as ud', 'task.uid', '=', 'ud.uid')
            ->leftjoin('task_price_ranges as tpr', 'task.action_id', '=', 'tpr.id')
            ->first();
        return $data;
    }


    /**
     * 查找相似的任务
     * @param $cate_id
     */
    static function findByCate($cate_id, $id)
    {
        $query = self::where('cate_id', '=', $cate_id);
        $query = $query->where(function ($query) use ($id) {
            $query->where('id', '!=', $id);
        });
        //赏金已经托管的任务
        $query = $query->where(function ($query) {
            $query->where('status', '>', 2);
        });
        //没有到截稿时间
        $query = $query->where(function ($query) {
            $query->where('delivery_deadline', '>', date('Y-m-d H:i:s', time()));
        });
        $data = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        return $data;
    }

    /**
     * 判断是不是雇主
     */
    public static function isEmployer($task_id, $uid)
    {
        $data = self::find($task_id);
        if ($data->uid == $uid) {
            return true;
        } else {
            return false;
        }
    }

    // 赏金分配
    public static function distributeBounty($id, $uid)
    {
        $status = DB::transaction(function () use ($id, $uid) {
            $info = self::where('id', $id)->first();
            $bounty = ($info->bounty / $info->worker_num) * (1 - sprintf("%.2f", $info->task_success_draw_ratio / 100));
            UserDetailModel::where('uid', $uid)->increment('balance', $bounty);
            $finance_data = [
                'action' => 2,
                'pay_type' => 1,
                'cash' => $bounty,
                'uid' => $uid,
                'title' => "完成任务_$info->title",
                'create_at' => date('Y-m-d H:i:s')
            ];
            FinancialModel::createOne($finance_data);
        });
        return is_null($status) ? true : false;
    }


    /**
     * 任务验收通过和任务验收失败
     * @param $task 相关任务数据
     * @param $type 操作类型1表示验收通过2表示验收失败
     */
    static function employAccept($task, $type)
    {
        $status = DB::transeaction(function () use ($task, $type) {
            //验收通过
            if ($type == 1) {
                //将任务状态修改成3验收通过
                TaskModel::where('id', $task['id'])->update(['status' => 3]);
                //将任务的稿件修改成验收通过
                $employee_user = EmployUserModel::where('task_id', $task['id'])->first();
                //将任务的托管金打给威客，并生成记录
                self::distributeBounty($task['id'], $employee_user['uid']);
                $bounty = self::where('id', $task['id'])->first();
                $bounty = ($bounty['bounty'] / $bounty['worker_num']) * (1 - $bounty['task_success_draw_ratio']);
                //增加用户余额
                UserDetailModel::where('uid', $employee_user['uid'])->increment('balance', $bounty);
                //产生一笔财务流水 表示接受任务产生的钱
                $finance_data = [
                    'action' => 2,
                    'pay_type' => 1,
                    'cash' => $bounty,
                    'uid' => $employee_user['uid'],
                    'create_at' => date('Y-m-d H:i:s', time())
                ];
                FinancialModel::create($finance_data);

            } else if ($type == 2) {

            }
        });
    }

    public function test($data)
    {
        $this->where('status','>',2);
    }

    /**
     * 创建一个任务（11dom）
     */
    public static function createOne($data = [])
    {
        try {
            $data['show_cash'] = $data['bounty'];
            if ($data['status'] == 2) {
                $data['verified_status'] = 2;
            }
            $id = DB::transaction(function () use ($data) {
                $time = date('Y-m-d H:i:s');
                $file_id = $data['file_id'];
                $product = $data['product'];
                unset($data['file_id'], $data['product']);
                $result = self::create($data);
                if ($file_id) {
                    $ids = AttachmentModel::whereIn('id', $file_id)
                        ->where('user_id', $data['uid'])
                        ->where('status', 0)
                        ->lists('id')
                        ->toArray();
                    if (count($ids)) {
                        $insert = [];
                        foreach ($ids as $v) {
                            $insert[] = [
                                'task_id' => $result->id,
                                'attachment_id' => $v,
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                        TaskAttachmentModel::insert($insert);
                        AttachmentModel::whereIn('id', $file_id)
                            ->where('user_id', $data['uid'])
                            ->where('status', 0)
                            ->update(['status' => 1]);
                    }
                }
                $update = [];
                $update['server_status'] = 0;
                if ($product) {
                    $server = ServiceModel::whereIn('id', $product)->get();
                    if (count($server)) {
                        $insert = [];
                        foreach ($server as $v) {
                            switch ($v->identify) {
                                case 'ZHIDING':
                                    $update['top_status'] = 1;
                                    break;
                                case 'SOUSUOYINGQINGPINGBI':
                                    $update['engine_status'] = 1;
                                    break;
                                case 'GAOJIANPINGBI':
                                    $update['work_status'] = 1;
                                    break;
                                case 'JIAJI':
                                    $update['urgent_status'] = 1;
                                    break;
                            }
                            $insert[] = [
                                'task_id' => $result->id,
                                'service_id' => $v->id,
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                        $update['server_status'] = 1;
                        TaskServiceModel::insert($insert);
                    }
                }
                self::where('id', $result->id)->update($update);
                return $result->id;
            });
            return $id;
        } catch (\Exception  $e) {
            return null;
        }
    }

    /**
     * 更新一个任务（11dom）
     */
    public static function updateOne($data = [], $uid = 0)
    {
        try {
            $data['show_cash'] = $data['bounty'];
            if ($data['status'] == 2) {
                $data['verified_status'] = 2;
            } else {
                $data['verified_status'] = 1;
            }
            DB::transaction(function () use ($data, $uid) {
                $time = date('Y-m-d H:i:s');
                $id = $data['id'];
                $file_id = $data['file_id'];
                $product = $data['product'];
                unset($data['file_id'], $data['product'], $data['id']);
                TaskModel::where('id', $id)->where('uid', $uid)->update($data);
                if ($file_id) {
                    $ids = AttachmentModel::whereIn('id', $file_id)
                        ->where('user_id', $uid)
                        ->where('status', 0)
                        ->lists('id')
                        ->toArray();
                    if (count($ids)) {
                        $insert = [];
                        foreach ($ids as $v) {
                            $insert[] = [
                                'task_id' => $id,
                                'attachment_id' => $v,
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                        TaskAttachmentModel::insert($insert);
                        AttachmentModel::whereIn('id', $file_id)
                            ->where('user_id', $uid)
                            ->where('status', 0)
                            ->update(['status' => 1]);
                    }
                }
                TaskServiceModel::where('task_id', $id)->delete();
                TaskModel::where('id', $id)
                    ->where('uid', $uid)
                    ->update([
                        'top_status' => 0,
                        'engine_status' => 0,
                        'work_status' => 0
                    ]);
                $update = [];
                $update['server_status'] = 0;
                if ($product) {
                    $server = ServiceModel::whereIn('id', $product)->get();
                    if (count($server)) {
                        $insert = [];
                        foreach ($server as $v) {
                            switch ($v->identify) {
                                case 'ZHIDING':
                                    $update['top_status'] = 1;
                                    break;
                                case 'SOUSUOYINGQINGPINGBI':
                                    $update['engine_status'] = 1;
                                    break;
                                case 'GAOJIANPINGBI':
                                    $update['work_status'] = 1;
                                    break;
                            }
                            $insert[] = [
                                'task_id' => $id,
                                'service_id' => $v->id,
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                        $update['server_status'] = 1;
                        TaskServiceModel::insert($insert);
                    }
                }
                TaskModel::where('id', $id)->where('uid', $uid)->update($update);
            });
            return $data['id'];
        } catch (\Exception  $e) {
            return null;
        }
    }

    /**
     * 检查各给类型的校验结果
     */
    public static function checkType($all = [], $data = [])
    {
        $err = [];
        $max_bounty = HelpsController::priceFormat(HelpsController::getConfigRule('task_bounty_max_limit'));
        $min_bounty = HelpsController::priceFormat(HelpsController::getConfigRule('task_bounty_min_limit'));
        if ($data['region_limit'] == 1) {
            unset($data['province'], $data['city'], $data['area']);
        } else {
            if (! intval($data['province'])) {
                $err['province'] = '请选择省份';
            }
            if (! intval($data['city'])) {
                $err['city'] = '请选择城市';
            }
            if (! intval($data['area'])) {
                $err['area'] = '请选择地区';
            }
        }
        switch ($data['type_id']) {
            case '1':// 多人悬赏
                if (floatval($all['multiple_bounty'])) {
                    $data['bounty'] = $all['multiple_bounty'];
                } else {
                    $err['bounty'] = '请填写赏金';
                }
                $data['worker_num'] = $all['multiple_worker_num'];
                if (! intval($data['worker_num'])) {
                    $err['worker_num_e'] = '请填写中标人数';
                }
                if (intval($data['worker_num']) < 2) {
                    $err['worker_num'] = '中标人数至少为2个';
                }
                break;
            case '2':// 招标模式
                if ($all['bounty_select']) {
                    if (intval($all['tender_bounty_id'])) {
                        $data['bounty'] = 0;
                        $data['action_id'] = $all['tender_bounty_id'];
                    } else {
                        $err['action_id'] = '请选择赏金区间';
                    }
                } else {
                    if (floatval($all['tender_bounty'])) {
                        $data['bounty'] = $all['tender_bounty'];
                        $data['action_id'] = 0;
                    } else {
                        $err['bounty'] = '请填写赏金';
                    }
                }
                $data['worker_num'] = 1;
                break;
            case '3':// 单人悬赏
                if (floatval($all['single_bounty'])) {
                    $data['bounty'] = $all['single_bounty'];
                } else {
                    $err['bounty'] = '请填写赏金';
                }
                $data['worker_num'] = 1;
                break;
            case '4':// 计件悬赏
                if (floatval($all['job_bounty'])) {
                    $data['bounty'] = $all['job_bounty'];
                } else {
                    $err['bounty'] = '请填写赏金';
                }
                $data['worker_num'] = $all['job_worker_num'];
                if (! intval($data['worker_num'])) {
                    $err['worker_num_e'] = '请填写所需件数';
                }
                if (intval($data['worker_num']) < 2) {
                    $err['worker_num'] = '所需件数至少为2个';
                }
                break;
        }
        if ($data['begin_at'] > $data['delivery_deadline']) {
            $err['time'] = '竞标开始时间不能大于结束时间';
        }
        if (! $all['bounty_select'] && isset($data['bounty'])) {
            if (($data['bounty'] > $max_bounty && $max_bounty != 0)
                || $data['bounty'] < $min_bounty ) {
                $err['bounty_limit'] = '赏金应该大于' . $min_bounty . '小于' . $max_bounty;;
            }
        }
        return ['err' => $err, 'data' => $data];
    }

    /**
     * 与之关联的类型
     */
    public function type()
    {
        return $this->belongsTo('App\Modules\Task\Model\TaskTypeModel', 'type_id');
    }

    // 根据标志位获取相应的任务类型
    public static function getTaskType($model = null, $type_id = 0)
    {
        $allow = [1, 2, 3];
        if (! in_array($type_id, $allow)) {
            abort(404);
        }
        switch ($type_id) {
            case '1':// 托管赏金+增值服务
                $prefix = '托管赏金+增值服务_';
                $ids = TaskServiceModel::where('task_id', $model->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $service = price_format($service);
                $total = $model->bounty + $service;
                break;
            case '2':// 托管赏金
                $prefix = '托管赏金_';
                $ids = [];
                $service = price_format(0);
                $total = $model->bounty;
                break;
            case '3':// 增值服务
                $prefix = '增值服务_';
                $ids = TaskServiceModel::where('task_id', $model->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $total = price_format($service);
                break;
            default:
                $prefix = '';
                $ids = [];
                $service = 0.00;
                $total = 0.00;
                break;
        }
        $data = (object)[];
        $data->ids = $ids;
        $data->price = $total;
        $data->service = $service;
        $data->title = $prefix . cut_str($model->title, 20);
        return $data;
    }
}
