<?php
namespace App\Modules\Manage\Http\Controllers;

use App\Http\Controllers\ManageController;
use App\Modules\Agent\Http\Controllers\HelpsController;
use App\Modules\Finance\Model\FinancialModel;
use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\Task\Model\TaskAttachmentModel;
use App\Modules\Task\Model\TaskExtraModel;
use App\Modules\Task\Model\TaskExtraSeoModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskTypeModel;
use App\Modules\Task\Model\WorkCommentModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Theme;

class TaskController extends ManageController
{
    public function __construct()
    {
        parent::__construct();

        $this->initTheme('manage');
        $this->theme->setTitle('任务列表');
        $this->theme->set('manageType', 'task');
    }

    /**
     * 任务列表
     */
    public function taskList(Request $request)
    {
        $search = $request->all();
        $by = $request->get('by', 'id');
        $order = $request->get('order', 'desc');
        $paginate = $request->get('paginate', 10);
        $taskList = TaskModel::from('task as a')
            ->select('a.*', 'b.name as type_name', 'us.name', 'ud.nickname');
        if ($title = $request->get('task_title')) {
            $taskList->where('a.title', 'like', "%{$title}%");
        }
        if ($username = $request->get('username')) {
            $taskList->where('us.name', 'like', "%{$username}%")
                ->orWhere('ud.nickname', 'like', "%{$username}%");
        }
        if ($status = $request->get('status')) {
            $taskList->where('a.status', $status);
        }
        if ($type_id = $request->get('type_id')) {
            $taskList->where('a.type_id', $type_id);
        }
        if ($verified_status = $request->get('verified_status')) {
            $taskList->where('a.verified_status', $verified_status);
        }
        if ($time_type = $request->get('time_type')) {
            $start = $request->get('start', null);
            $end = $request->get('end', null);
            if ($start && $end) {
                $taskList->whereBetween($time_type, [$start, $end]);
            }
        }
        $taskList = $taskList->orderBy($by, $order)
            ->leftJoin('task_type as b', 'b.id', '=', 'a.type_id')
            ->leftJoin('users as us', 'us.id', '=', 'a.uid')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 'a.uid')
            ->paginate($paginate);
        $type = TaskTypeModel::getList();
        $status = [
            0 => [
                'name' => '全部状态',
                'label' => 'default'
            ],
            1 => [
                'name' => '暂不发布',
                'label' => 'warning'
            ],
            2 => [
                'name' => '已发布',
                'label' => 'info'
            ],
            3 => [
                'name' => '竞标中',
                'label' => 'primary'
            ],
            4 => [
                'name' => '选标中',
                'label' => 'info'
            ],
            5 => [
                'name' => '工作中',
                'label' => 'primary'
            ],
            6 => [
                'name' => '交付中',
                'label' => 'info'
            ],
            7 => [
                'name' => '互评中',
                'label' => 'primary'
            ],
            8 => [
                'name' => '圆满完成',
                'label' => 'success'
            ],
            9 => [
                'name' => '任务失败',
                'label' => 'danger'
            ],
            10 => [
                'name' => '维权中',
                'label' => 'warning'
            ],
        ];
        $verified = [
            1 => [
                'name' => 'N/A',
                'label' => 'default'
            ],
            2 => [
                'name' => '审核中',
                'label' => 'warning'
            ],
            3 => [
                'name' => '审核通过',
                'label' => 'success'
            ],
            4 => [
                'name' => '审核失败',
                'label' => 'danger'
            ]
        ];
        $bounty = [
            1 => '未托管',
            2 => '已托管'
        ];
        $view = [
            'task' => $taskList,
            'merge' => $search,
            'status' => $status,
            'type' => $type,
            'verified' => $verified,
            'bounty' => $bounty
        ];
        return $this->theme->scope('manage.tasklist', $view)->render();
    }

    /**
     * 任务处理
     */
    public function taskHandle($id, $action)
    {
        $id = intval($id);
        $arr = ['pass', 'deny', 'del'];
        if (! $id || ! in_array($action, $arr)) {
            return back()->with(['error' => '参数错误！']);
        }

        $task = TaskModel::where('id', $id)->first();
        if (! $task) {
            return back()->with(['error' => '参数错误！']);
        }
        $user = UserModel::find($task->uid);
        $userDetail = UserDetailModel::where('uid', $task->uid)->first();
        $site_name = \CommonClass::getConfig('site_name');
        $domain = \CommonClass::getDomain();
        $time = date('Y-m-d H:i:s');
        switch ($action) {
            case 'pass':
                $code_name = 'audit_success';
                $update = [
                    'verified_status' => 3,
                    'verified_at' => $time
                ];
                if ($task->type_id == 2) {
                    $update['status'] = 3;
                    $update['bid_at'] = $time;
                }
                $messageVariableArr = [
                    'username' => $userDetail->nickname ? $userDetail->nickname : $user->name,
                    'website' => $site_name,
                    'task_number' => $task->id,
                    'task_link' => "{$domain}/task/{$task->id}"
                ];
                $result = TaskModel::where('id', $id)
                    ->whereIn('status', [1, 2])
                    ->update($update);
                if (! $result) {
                    return redirect()->back()->with(['error' => '操作失败！']);
                }
                $task_audit_failure = MessageTemplateModel::where('code_name', $code_name)
                    ->where('is_open', 1)
                    ->where('is_on_site', 1)
                    ->first();
                if ($task_audit_failure) {
                    $message = MessageTemplateModel::sendMessage($code_name, $messageVariableArr);
                    $data = [
                        'message_title' => $task_audit_failure['name'],
                        'code_name' => $code_name,
                        'message_content' => $message,
                        'js_id' => $user->id,
                        'message_type' => 2,
                        'receive_time' => $time,
                        'status' => 0,
                    ];
                    MessageReceiveModel::create($data);
                }
                break;
            case 'deny':
                $code_name = 'task_audit_failure';
                $update = [
                    'verified_status' => 4,
                    'verified_at' => null
                ];
                $messageVariableArr = [
                    'username' => $userDetail->nickname ? $userDetail->nickname : $user->name,
                    'href' => "{$domain}/task/{$task->id}",
                    'task_title' => $task->title,
                    'website' => $site_name
                ];
                $result = TaskModel::where('id', $id)
                    ->whereIn('status', [1, 2])
                    ->update($update);
                if (! $result) {
                    return redirect()->back()->with(['error' => '操作失败！']);
                }
                $task_audit_failure = MessageTemplateModel::where('code_name', $code_name)
                    ->where('is_open', 1)
                    ->where('is_on_site', 1)
                    ->first();
                if ($task_audit_failure) {
                    $message = MessageTemplateModel::sendMessage($code_name, $messageVariableArr);
                    $data = [
                        'message_title' => $task_audit_failure['name'],
                        'code_name' => $code_name,
                        'message_content' => $message,
                        'js_id' => $user->id,
                        'message_type' => 2,
                        'receive_time' => $time,
                        'status' => 0,
                    ];
                    MessageReceiveModel::create($data);
                }
                break;
            case 'del':
                $result = TaskModel::destroy($id);
                if (! $result) {
                    return redirect()->back()->with(['error' => '操作失败！']);
                }
                break;
            default:
                $code_name = '';
                $update = [];
                $messageVariableArr = [];
                break;
        }
        return redirect()->back()->with(['message' => '操作成功！']);
    }


    /**
     * 任务批量处理
     */
    public function taskMultiHandle(Request $request)
    {
        $data = $request->only(['ids', 'action']);
        $allow = ['pass', 'deny', 'del'];
        if (! $data['ids'] || ! in_array($data['action'], $allow)) {
            return response()->json(['code' => 1001, 'msg' => '非法操作']);
        }
        $tasks = TaskModel::select('id', 'uid', 'title', 'type_id')
            ->whereIn('id', explode(',', $data['ids']))
            ->get();
        if (! $tasks) {
            return response()->json(['code' => 1008, 'msg' => '参数错误']);
        }
        $code_name = '';
        $update = $insert = [];
        $users = UserModel::whereIn('id', array_pluck($tasks, 'uid'))
            ->lists('name', 'id')
            ->toArray();
        $userDetail = UserDetailModel::whereIn('uid', array_pluck($tasks, 'uid'))
            ->lists('nickname', 'uid')
            ->toArray();
        $site_name = \CommonClass::getConfig('site_name');
        $domain = \CommonClass::getDomain();
        $time = date('Y-m-d H:i:s');
        $send = true;
        switch ($data['action']) {
            case 'pass':
                $code_name = 'audit_success';
                foreach ($tasks as $v) {
                    $tmp = [
                        'id' => $v->id,
                        'verified_status' => 3,
                        'verified_at' => $time
                    ];
                    if ($v->type_id == 2) {
                        $tmp['status'] = 3;
                        $tmp['bid_at'] = $time;
                    }
                    $update[] = $tmp;
                    $insert[] = [
                        'username' => $userDetail[$v->uid] ? $userDetail[$v->uid] : $users[$v->uid],
                        'website' => $site_name,
                        'task_number' => $v->id,
                        'task_link' => "{$domain}/task/{$v->id}",
                        'uid' => $v->uid
                    ];
                }
                break;
            case 'deny':
                $code_name = 'task_audit_failure';
                foreach ($tasks as $v) {
                    $tmp = [
                        'id' => $v->id,
                        'verified_status' => 4,
                        'verified_at' => null
                    ];
                    $update[] = $tmp;
                    $insert[] = [
                        'username' => $userDetail[$v->uid] ? $userDetail[$v->uid] : $users[$v->uid],
                        'href' => "{$domain}/task/{$v->id}",
                        'task_title' => $v->title,
                        'website' => $site_name,
                        'uid' => $v->uid
                    ];
                }
                break;
            case 'del':
                $send = false;
                $result = TaskModel::destroy(explode(',', $data['ids']));
                break;
        }
        if ($send) {
            $ret = HelpsController::updateBatch($update, 'task');
            $result = DB::update($ret['sql'], $ret['bindings']);
            if (! $result) {
                return response()->json(['code' => 1004, 'msg' => '操作失败']);
            }
            $task_audit_failure = MessageTemplateModel::where('code_name', $code_name)
                ->where('is_open', 1)
                ->where('is_on_site', 1)
                ->first();
            if ($task_audit_failure && $send) {
                $data = [];
                foreach ($insert as $k => $v) {
                    $uid = array_pull($v, 'uid');
                    $message = MessageTemplateModel::sendMessage($code_name, $v);
                    $data[] = [
                        'message_title' => $task_audit_failure['name'],
                        'code_name' => $code_name,
                        'message_content' => $message,
                        'js_id' => $uid,
                        'message_type' => 2,
                        'receive_time' => $time,
                        'status' => 0,
                    ];
                }
                MessageReceiveModel::insert($data);
            }
        } else {
            if (! $result) {
                return response()->json(['code' => 1004, 'msg' => '操作失败']);
            }
        }
        return response()->json(['code' => 1000, 'msg' => '操作成功']);
    }

    /**
     * 任务详情
     * @param $id
     */
    public function taskDetail($id)
    {
        $task = TaskModel::where('id', $id)->first();
        if (!$task) {
            return redirect()->back()->with(['error' => '当前任务不存在，无法查看稿件！']);
        }
        $query = TaskModel::select('task.*', 'us.name as nickname', 'ud.avatar', 'ud.qq')->where('task.id', $id);
        $taskDetail = $query->join('user_detail as ud', 'ud.uid', '=', 'task.uid')
            ->leftjoin('users as us', 'us.id', '=', 'task.uid')
            ->first()->toArray();
        if (!$taskDetail) {
            return redirect()->back()->with(['error' => '当前任务已经被删除！']);
        }
        $status = [
            0 => '暂不发布',
            1 => '已经发布',
            2 => '赏金托管',
            3 => '审核通过',
            4 => '威客交稿',
            5 => '雇主选稿',
            6 => '任务公示',
            7 => '交付验收',
            8 => '双方互评',
            9 => '任务完成',
            10 => '失败',
            11 => '维权'
        ];
        $taskDetail['status_text'] = $status[$taskDetail['status']];

        //任务类型
        $taskType = TaskTypeModel::all();
        //任务中标人数
        $taskDelivery = WorkModel::where('task_id', $id)->where('status', 3)->count();
        //任务附件
        $task_attachment = TaskAttachmentModel::select('task_attachment.*', 'at.url')->where('task_id', $id)
            ->leftjoin('attachment as at', 'at.id', '=', 'task_attachment.attachment_id')->get()->toArray();
        //查询seo数据
        $task_seo = TaskExtraSeoModel::where('task_id', $id)->first();
        //任务稿件
        $works = WorkModel::select('work.*', 'us.name as nickname', 'ud.avatar')
            ->where('work.status', '<=', 1)
            ->where('work.task_id', $id)
            ->with('childrenAttachment')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 'work.uid')
            ->leftjoin('users as us', 'us.id', '=', 'work.uid')
            ->get()->toArray();

        //任务留言
        $task_massages = WorkCommentModel::select('work_comments.*', 'us.name as nickname', 'ud.avatar')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 'work_comments.uid')
            ->leftjoin('users as us', 'us.id', '=', 'work_comments.uid')
            ->where('work_comments.task_id', $id)->paginate();
        //任务交付
        $work_delivery = WorkModel::select('work.*', 'us.name as nickname', 'ud.mobile', 'ud.qq', 'ud.avatar')
            ->whereIn('work.status', [2, 3])
            ->where('work.task_id', $id)
            ->with('childrenAttachment')
            ->leftjoin('user_detail as ud', 'ud.uid', '=', 'work.uid')
            ->leftjoin('users as us', 'us.id', '=', 'work.uid')
            ->get()->toArray();

        $domain = \CommonClass::getDomain();

        $data = [
            'task' => $taskDetail,
            'domain' => $domain,
            'taskType' => $taskType,
            'taskDelivery' => $taskDelivery,
            'taskAttachment' => $task_attachment,
            'task_seo' => $task_seo,
            'works' => $works,
            'task_massages' => $task_massages,
            'work_delivery' => $work_delivery
        ];
        return $this->theme->scope('manage.taskdetail', $data)->render();
    }

    /**
     * 任务详情提交
     * @param Request $request
     */
    public function taskDetailUpdate(Request $request)
    {
        $data = $request->except('_token');
        $task_extra = [
            'task_id' => intval($data['task_id']),
            'seo_title' => $data['seo_title'],
            'seo_keyword' => $data['seo_keyword'],
            'seo_content' => $data['seo_content'],
        ];
        $result = TaskExtraSeoModel::firstOrCreate(['task_id' => $data['task_id']])
            ->where('task_id', $data['task_id'])
            ->update($task_extra);
        //修改任务数据
        $task = [
            'title' => $data['title'],
            'desc' => $data['desc'],
            'phone' => $data['phone']
        ];
        //修改任务数据
        $task_result = TaskModel::where('id', $data['task_id'])->update($task);

        if (!$result || !$task_result) {
            return redirect()->back()->with(['error' => '更新失败！']);
        }

        return redirect()->back()->with(['massage' => '更新成功！']);
    }

    /**
     * 删除任务留言
     */
    public function taskMassageDelete($id)
    {
        $result = WorkCommentModel::destroy($id);

        if (!$result) {
            return redirect()->to('/manage/taskList')->with(['error' => '留言删除失败！']);
        }
        return redirect()->to('/manage/taskList')->with(['massage' => '留言删除成功！']);
    }

    /**下载附件
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($id)
    {
        $pathToFile = AttachmentModel::where('id', $id)->first();
        $pathToFile = $pathToFile['url'];
        return response()->download($pathToFile);
    }
}
