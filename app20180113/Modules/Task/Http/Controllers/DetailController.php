<?php

namespace App\Modules\Task\Http\Controllers;

use App\Http\Controllers\IndexController;
use App\Http\Requests;
use App\Modules\Manage\Model\AgreementModel;
use App\Modules\Manage\Model\MessageTemplateModel;
use App\Modules\Task\Http\Requests\CommentRequest;
use App\Modules\Task\Http\Requests\WorkRequest;
use App\Modules\Task\Model\TaskAttachmentModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskReportModel;
use App\Modules\Task\Model\TaskRightsModel;
use App\Modules\Task\Model\WorkCommentModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\CommentModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\MessageReceiveModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Model\UserDetailModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use App\Modules\Advertisement\Model\AdTargetModel;
use App\Modules\Advertisement\Model\AdModel;
use App\Modules\Advertisement\Model\RePositionModel;
use App\Modules\Advertisement\Model\RecommendModel;
use App\Modules\Manage\Model\ConfigModel;
use Teepluss\Theme\Theme;


class DetailController extends IndexController
{
    public function __construct()
    {
        parent::__construct();
        $this->user = Auth::user();
        $this->initTheme('main');
    }

    /**
     * 任务详情
     */
    public function index(Request $request, $id)
    {
        $data = $request->all();
        $detail = TaskModel::detail($id);
        if (!$detail) {
            return redirect()->to('task')->with(['error' => '参数错误']);
        }
        if ($detail->engine_status) {
            $this->theme->set('engine_status', 1);
        }
        $user_type = 3;
        $is_win_bid = 0;
        $is_delivery = 0;
        $is_rights = 0;
        $delivery_count = 0;
        $works_rights_count = 0;
        if ($detail->status > 2 && Auth::check()) {
            if ($work = WorkModel::isWorker($this->user->id, $detail->id)) {
                $user_type = 2;
                $is_win_bid = WorkModel::isWinBid($id, $this->user->id);
                $is_delivery = WorkModel::where('task_id', $id)->where('status', '>', 1)->where('uid', $this->user->id)->first();
                $is_rights = WorkModel::where('task_id', $id)->where('status', 4)->where('uid', $this->user->id)->first();
            }
            if ($detail->uid == $this->user->id) {
                $user_type = 1;
            }
        }
        $works = WorkModel::findAll($id, $data);
        $works_count = WorkModel::where('task_id', $id)->where('status', '<=', 1)->where('forbidden', 0)->count();
        $works_winbid_count = $works_bid_count = WorkModel::where('task_id', $id)->where('status', 1)->where('forbidden', 0)->count();
        $delivery = [];
        if (Auth::check()) {
            if ($user_type == 2) {
                $delivery = WorkModel::select('work.*', 'us.name as username', 'a.avatar', 'a.nickname')
                    ->where('work.uid', $this->user->id)
                    ->where('work.task_id', $id)
                    ->where('work.status', '>=', 2)
                    ->with('childrenAttachment')
                    ->join('user_detail as a', 'a.uid', '=', 'work.uid')
                    ->leftjoin('users as us', 'us.id', '=', 'work.uid')
                    ->paginate(5)
                    ->setPageName('delivery_page')
                    ->toArray();
                $delivery_count = 1;
            } elseif ($user_type == 1) {
                $delivery = WorkModel::findDelivery($id, $data);
                $delivery_count = WorkModel::where('task_id', $id)
                    ->where('status', '>=', 2)
                    ->count();
            }
        }
        $comment = CommentModel::taskComment($id, $data);
        $comment_count = CommentModel::where('task_id', $id)->count();
        $good_comment = CommentModel::where('task_id', $id)->where('type', 1)->count();
        $middle_comment = CommentModel::where('task_id', $id)->where('type', 2)->count();
        $bad_comment = CommentModel::where('task_id', $id)->where('type', 3)->count();
        $attatchment_ids = TaskAttachmentModel::where('task_id', $id)->lists('attachment_id')->toArray();
        $attatchment = AttachmentModel::whereIn('id', $attatchment_ids)->get();
        $alike_task = TaskModel::findByCate($detail['cate_id'], $id);
        $works_rights = [];
        if (Auth::check()) {
            if ($user_type == 2) {
                $works_rights = WorkModel::select('work.*', 'us.name as username', 'ud.avatar', 'ud.nickname')
                    ->where('work.uid', $this->user->id)
                    ->where('task_id', $id)
                    ->where('work.status', 4)
                    ->with('childrenAttachment')
                    ->join('user_detail as ud', 'ud.uid', '=', 'work.uid')
                    ->leftjoin('users as us', 'us.id', '=', 'work.uid')
                    ->paginate(5)
                    ->setPageName('delivery_page')
                    ->toArray();
                $works_rights_count = 1;
            } elseif ($user_type == 1) {
                $works_rights = WorkModel::findRights($id);
                $works_rights_count = WorkModel::where('task_id', $id)
                    ->where('status', 4)
                    ->count();
            }
        }
        $domain = \CommonClass::getDomain();
        $ad = AdTargetModel::getAdInfo('TASKINFO_RIGHT');
        $agree = AgreementModel::where('code_name', 'task_delivery')->first();
        $view = [
            'detail' => $detail,
            'attatchment' => $attatchment,
            'alike_task' => $alike_task,
            'user_type' => $user_type,
            'works' => $works,
            'file_type' => 'jpg',
            'is_win_bid' => $is_win_bid,
            'is_delivery' => $is_delivery,
            'merge' => $data,
            'delivery' => $delivery,
            'domain' => $domain,
            'comment' => $comment,
            'good_comment' => $good_comment,
            'middle_comment' => $middle_comment,
            'bad_comment' => $bad_comment,
            'works_count' => $works_count,
            'delivery_count' => $delivery_count,
            'comment_count' => $comment_count,
            'works_bid_count' => $works_bid_count,
            'works_rights' => $works_rights,
            'works_rights_count' => $works_rights_count,
            'ad' => $ad,
            'is_rights' => $is_rights,
            'works_winbid_count' => $works_winbid_count,
            'agree' => $agree,
        ];
        if ($detail['region_limit'] == 2
            && $detail['province']
            && $detail['city']
            && $detail['area']) {
            $province = DistrictModel::whereIn('id', [$detail->province, $detail->city, $detail->area])->get()->toArray();
            $province = \CommonClass::keyBy($province, 'id');
            $view['area'] = $province;
        }
        TaskModel::where('id', $id)->increment('view_count', 1);
        $this->theme->setTitle('任务详情');
        return $this->theme->scope('task.detail', $view)->render();
    }

    /**
     * 竞标投稿页
     */
    public function work($id = 0)
    {
        /*if ($this->user->user_type < 1) {
            return redirect()->back()->with(['error' => '您必须成为服务商才可以投稿']);
        }*/
        $agree = AgreementModel::where('code_name', 'task_draft')->first();
        $task_data = TaskModel::from('task as t')
            ->select('t.*', 'tpr.min_price', 'tpr.max_price')
            ->where('t.id', $id)
            ->leftJoin('task_price_ranges as tpr', 'tpr.id', '=', 't.action_id')
            ->first();
        $view = [
            'task' => $task_data,
            'agree' => $agree
        ];
        if ($task_data->type_id != 2) {
            $list = ModelsFolderModel::select('id', 'name', 'cover_img')
                ->where('uid', $this->user->id)
                ->orderBy('create_time', 'desc')
                ->get();
            $view['list'] = $list;
        }
        $this->initTheme('task');
        $this->theme->setTitle('竞标投稿');
        return $this->theme->scope('task.work', $view)->render();
    }

    /**
     * 竞标投稿
     */
    public function workCreate(Request $request)
    {
        $type_id = $request->input('type_id', 0);
        if ($type_id == 2) {
            $this->validate($request, [
                'bidding_price' => [
                    'required',
                    'regex:/^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$|^0$/'
                ],
                'work_time' => 'required|integer|min:1',
                'agree' => 'accepted'
            ], [
                'bidding_price.required' => '请给出您的价格',
                'bidding_price.regex' => '价格格式不正确',
                'work_time.required' => "请填写开发周期",
                'work_time.integer' => "开发周期只能为整数",
                'work_time.min' => "开发周期至少为1天",
                'agree.accepted' => '您必须同意《文件交稿协议协议》'
            ]);
        } else {
            $this->validate($request, [
                'action_id' => 'required|integer|min:1',
                'agree' => 'accepted'
            ], [
                'action_id.required' => '您还未选择要提交的作品',
                'action_id.integer' => '您还未选择要提交的作品',
                'action_id.min' => '您还未选择要提交的作品',
                'agree.accepted' => '您必须同意《文件交稿协议协议》'
            ]);
        }
        $domain = url();
        $time = date('Y-m-d H:i:s');
        $data['bidding_price'] = $request->input('bidding_price', 0.00);
        $data['work_time'] = $request->input('work_time', null);
        $data['desc'] = remove_xss($request->input('desc', null));
        $data['file_id'] = $request->input('file_id', []);
        $data['task_id'] = $request->input('task_id', 0);
        $data['action_id'] = $request->input('action_id', 0);
        $data['uid'] = $this->user->id;
        $data['created_at'] = $time;
        $is_work_able = $this->isWorkAble($data['task_id']);
        /*if ($this->user->user_type < 1) {
            return redirect()->back()->withErrors(['deny' => '您必须成为服务商才可以投稿'])->withInput();
        }*/
        if ($type_id != 2) {
            $action_id = ModelsContentModel::where('id', $data['action_id'])
                ->where('uid', $this->user->id)
                ->value('id');
            if (!$action_id) {
                return redirect()->back()->withErrors(['deny' => '这不是您的作品，请勿恶意提交'])->withInput();
            }
        }
        $action_id = WorkModel::where('action_id', $data['action_id'])
            ->where('status', 2)
            ->first();
        if ($action_id) {
            return redirect()->back()->withErrors(['deny' => '该作品处于交付状态'])->withInput();
        }
        if (!$is_work_able['able']) {
            return redirect()->back()->withErrors(['deny' => $is_work_able['errMsg']])->withInput();
        }
        if (!WorkModel::workCreate($data)) {
            return redirect()->back()->withErrors(['deny' => '投稿失败'])->withInput();
        }
        $task_delivery = MessageTemplateModel::where('code_name', 'task_delivery')
            ->where('is_open', 1)
            ->where('is_on_site', 1)
            ->first();
        if ($task_delivery) {
            $task = TaskModel::find($data['task_id']);
            $user = UserModel::find($task->uid);
            $site_name = \CommonClass::getConfig('site_name');
            $username = Auth::user()->name;
            $nickname = Auth::user()->userDetail->nickname;
            $messageVariableArr = [
                'username' => $user->userDetail->nickname ? $user->userDetail->nickname : $user->name,
                'name' => $nickname ? $nickname : $username,
                'href' => "$domain/task/$task->id",
                'task_title' => $task->title,
                'website' => $site_name,
            ];
            $message = MessageTemplateModel::sendMessage('task_delivery', $messageVariableArr);
            $messages = [
                'message_title' => $task_delivery['name'],
                'code_name' => 'task_delivery',
                'message_content' => $message,
                'js_id' => $user->id,
                'message_type' => 2,
                'receive_time' => $time,
                'status' => 0,
            ];
            MessageReceiveModel::create($messages);
        }
        return redirect()->to("task/$task->id");
    }

    /**
     * 中标控制器
     */
    public function winBid($work_id, $task_id)
    {
        $task_user = TaskModel::where('id', $task_id)->value('uid');
        if ($task_user != $this->user->id) {
            return redirect()->back()->with(['error' => '非法操作，你不是任务的发布者不能选择中标人选！']);
        }
        $worker_num = TaskModel::where('id', $task_id)->value('worker_num');
        $win_bid_num = WorkModel::where('task_id', $task_id)->where('status', 1)->count();
        if ($worker_num > $win_bid_num) {
            $data = [
                'task_id' => $task_id,
                'work_id' => $work_id,
                'worker_num' => $worker_num,
                'win_bid_num' => $win_bid_num,
            ];
            if (WorkModel::winBid($data)) {
                return redirect()->back()->with(['massage' => '选稿成功！']);
            } else {
                return redirect()->back()->with(['error' => '操作失败！']);
            }
        } else {
            return redirect()->back()->with(['error' => '当前中标人数已满！']);
        }
    }

    // 交稿页面
    public function delivery($id = 0)
    {
        $uid = $this->user->id;
        $task_data = TaskModel::find($id);
        $work = WorkModel::where('task_id', $id)
            ->where('status', 1)
            ->where('uid', $uid)
            ->first();
        if (!$task_data || !$work) {
            return back()->with(['errors' => '参数错误']);
        }
        $agree = AgreementModel::where('code_name', 'task_delivery')->first();
        $list = ModelsFolderModel::select('id', 'name', 'cover_img')
            ->where('uid', $uid)
            ->orderBy('create_time', 'desc')
            ->get();
        $view = [
            'task' => $task_data,
            'agree' => $agree,
            'list' => $list,
            'work' => $work
        ];
        $this->initTheme('task');
        $this->theme->setTitle('交付稿件');
        return $this->theme->scope('task.delivery', $view)->render();
    }

    // 获取设计师某个文件夹下的作品@ajax
    public function modelsGet(Request $request)
    {
        $uid = $this->user->id;
        $list = ModelsContentModel::select('id', 'title', 'cover_img', 'upload_cover_image')
            ->where('uid', $uid)
            ->where('folder_id', $request->get('id'))
            ->where('enroll_status', 0)
            ->where('is_goods', 0)
            ->orderBy('create_time', 'desc')
            ->paginate(18);
        if ($list->lastPage()) {
            $view = [
                'list' => $list
            ];
            return response()->json([
                'code' => '1000',
                'page' => $list->lastPage(),
                'data' => view('task.modelsGet', $view)->render()
            ]);
        } else {
            return response()->json(['code' => '1005', 'msg' => '本文件夹暂无作品']);
        }
    }

    // 交付稿件提交
    public function deliverCreate(Request $request)
    {
        $this->validate($request, [
            'task_id' => 'required|integer|min:1',
            'work_id' => 'required|integer|min:1',
            'models_id' => 'required|integer|min:1',
            'agree' => 'accepted'
        ], [
            'task_id.required' => '非法修改',
            'task_id.integer' => '非法修改',
            'task_id.min' => '非法修改',
            'work_id.required' => '非法修改',
            'work_id.integer' => '非法修改',
            'work_id.min' => '非法修改',
            'models_id.required' => '您还未选择要提交的作品',
            'models_id.integer' => '您还未选择要提交的作品',
            'models_id.min' => '您还未选择要提交的作品',
            'agree.accepted' => '您必须同意《文件交稿协议协议》'
        ]);
        $uid = $this->user->id;
        $allow = ['task_id', 'work_id', 'models_id'];
        $data = $request->only($allow);
        $info = WorkModel::where('id', $data['work_id'])
            ->where('task_id', $data['task_id'])
            ->where('uid', $uid)
            ->where('status', 1)
            ->first();
        if (!$info) {
            return redirect()->back()->withErrors(['error' => '您的稿件没有中标！']);
        }
        $is_delivery = WorkModel::where('task_id', $info->task_id)
            ->where('uid', $uid)
            ->where('status', '>', 1)
            ->first();
        if ($is_delivery) {
            return redirect()->back()->withErrors(['error' => '您已经交付过了！']);
        }
        $create = [
            'task_id' => $info->task_id,
            'bidding_price' => $info->bidding_price,
            'work_time' => $info->work_time,
            'action_id' => $data['models_id'],
            'desc' => $info->desc,
            'status' => 2,
            'forbidden' => $info->forbidden,
            'uid' => $info->uid,
            'bid_by' => $info->bid_by,
            'bid_at' => $info->bid_at,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $task = TaskModel::find($info->task_id);
        $result = DB::transaction(function () use ($task, $create) {
            WorkModel::create($create);
            if ($task->type_id == 2) {
                $update = [
                    'status' => 6,
                    'checked_at' => date('Y-m-d H:i:s')
                ];
                TaskModel::where('id', $task->id)->update($update);
            }
            $update = [
                'is_private' => 0
            ];
            ModelsContentModel::where('id', $create['action_id'])->update($update);
        });
        if ($result) {
            return redirect()->back()->withErrors(['error' => '交付失败！']);
        }
        $agreement_documents = MessageTemplateModel::where('code_name', 'agreement_documents')
            ->where('is_open', 1)
            ->where('is_on_site', 1)
            ->first();
        if ($agreement_documents) {

            $r_username = UserModel::where('id', $task->uid)->value('name');
            $r_nickname = UserDetailModel::where('uid', $task->uid)->value('nickname');
            $site_name = \CommonClass::getConfig('site_name'); //必要条件
            $username = $this->user->name;
            $nickname = UserDetailModel::where('uid', $uid)->value('nickname');
            $domain = \CommonClass::getDomain();
            $messageVariableArr = [
                'username' => $r_nickname ? $r_nickname : $r_username,
                'initiator' => $nickname ? $nickname : $username,
                'agreement_link' => "$domain/task/{$task->id}",
                'website' => $site_name,
            ];
            $message = MessageTemplateModel::sendMessage('agreement_documents', $messageVariableArr);
            $messages = [
                'message_title' => $agreement_documents['name'],
                'code' => 'agreement_documents',
                'message_content' => $message,
                'js_id' => $task->uid,
                'message_type' => 2,
                'receive_time' => date('Y-m-d H:i:s'),
                'status' => 0
            ];
            MessageReceiveModel::create($messages);
        }
        return redirect()->to("task/{$data['task_id']}");
    }

    // 稿件通过验收
    public function workCheck(Request $request)
    {
        if (!$request->input('agree')) {
            return redirect()->back()->with(['error' => '您必须同意《文件交稿协议协议》']);
        }
        $allow = ['work_id'];
        $data = $request->only($allow);
        $work_data = WorkModel::where('id', $data['work_id'])->first();
        $data['uid'] = $work_data['uid'];
        if (!TaskModel::isEmployer($work_data['task_id'], $this->user->id)) {
            return redirect()->back()->with(['error' => '您不是雇主，您的操作有误！']);
        }
        if ($work_data['status'] != 2) {
            return redirect()->back()->with(['error' => '当前稿件不具备验收资格！']);
        }
        $data['worker_num'] = TaskModel::where('id', $work_data['task_id'])->value('worker_num');
        $data['win_check'] = WorkModel::where('task_id', $work_data['task_id'])->where('status', '>', 2)->count();
        $data['task_id'] = $work_data['task_id'];
        $data['work_status'] = 3;
        $data['store_uid'] = $this->user->id;
        $data['action_id'] = $work_data->action_id;
        if (WorkModel::workCheck($data)) {
            return redirect()->to("task/{$data['task_id']}")->with(['manage' => '验收成功！']);
        } else {
            return redirect()->back()->with(['error' => '验收失败！']);
        }
    }

    // 稿件验收失败
    public function lostCheck(Request $request)
    {
        $data = $request->except('_token');
        $data['work_status'] = 4;
        //验证用户是否是雇主
        if (!TaskModel::isEmployer($data['task_id'], $this->user['id']))
            return response()->json(['errCode' => 0, 'error' => '您不是雇主，您的操作有误！']);
        //验证当前稿件是否符合验收标准
        $work_data = WorkModel::where('id', $data['work_id'])->first();
        if ($work_data['status'] != 2)
            return response()->json(['errCode' => 0, 'error' => '当前稿件不具备验收资格！']);

        $workModel = new WorkModel();
        $result = $workModel->lostCheck($data);
        if (!$result) return response()->back()->with('error', '验收失败！');
        //刷新页面
        return response()->json(['errCode' => 1, 'id' => $data['work_id']]);
    }

    // 判断当前用户是否有投稿的资格,便于扩展
    private function isWorkAble($task_id)
    {
        //判断当前任务是否处于投稿期间
        $info = TaskModel::find($task_id);
        if ($info->status != 3) {
            return ['able' => false, 'errMsg' => '当前任务不处于竞标状态！'];
        }
        //判断当前用户是否登录
        if (!isset($this->user->id)) {
            return ['able' => false, 'errMsg' => '请登录后再操作！'];
        }
        //判断用户是否为当前任务的投稿人，如果已经是的，就不能投稿
        if (WorkModel::isWorker($this->user->id, $task_id)) {
            return ['able' => false, 'errMsg' => '你已经投过稿了'];
        }
        //判断当前用户是否为任务的发布者，如果是用户的发布者，就不能投稿
        if (TaskModel::isEmployer($task_id, $this->user->id)) {
            return ['able' => false, 'errMsg' => '你是任务发布者不能投稿！'];
        }
        return ['able' => true];
    }

    // ajax上传附件
    public function ajaxWorkAttatchment(Request $request)
    {
        $file = $request->file('file');
        //将文件上传的数据存入到attachment表中
        $attachment = \FileClass::uploadFile($file, 'task');
        $attachment = json_decode($attachment, true);
        //判断文件是否上传
        if ($attachment['code'] != 200) {
            return response()->json(['errCode' => 0, 'errMsg' => $attachment['message']]);
        }
        $attachment_data = array_add($attachment['data'], 'status', 1);
        $attachment_data['created_at'] = date('Y-m-d H:i:s', time());
        //将记录写入到attchement表中
        $result = AttachmentModel::create($attachment_data);
        $result = json_decode($result, true);

        if (!$result) {
            return response()->json(['errCode' => 0, 'errMsg' => '文件上传失败！']);
        }
        //回传附件id
        return response()->json(['id' => $result['id']]);
    }

    // 附件删除
    public function delAttatchment(Request $request)
    {
        $id = $request->get('id');
        $result = AttachmentModel::where('user_id', $this->user['id'])->where('id', $id)->delete();
        if (!$result) {
            return response()->json(['errCode' => 0, 'errMsg' => '删除失败！']);
        }
        return response()->json(['errCode' => 1, 'errMsg' => '删除成功！']);
    }

    // 下载附件
    public function download($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['errors' => '非法操作']);
        }
        $info = AttachmentModel::find($id);
        if (!$info) {
            return back()->with(['errors' => '参数错误']);
        }
        return response()->download($info->url, $info->name);
    }

    // ajax获取稿件的评论
    public function getComment($id)
    {
        $workComment = WorkCommentModel::where('work_id', $id)
            ->with('parentComment')
            ->with('user')
            ->with('users')
            ->get()->toArray();
        //给头像加绝对路径
        $domain = \CommonClass::getDomain();
        foreach ($workComment as $k => $v) {
            $workComment[$k]['avatar_md5'] = $domain . '/' . $v['user']['avatar'];
            $workComment[$k]['nickname'] = $v['users']['name'];
            if (is_array($v['parent_comment'])) {
                $workComment[$k]['parent_user'] = $v['parent_comment']['nickname'];
            }
        }
        $data['errCode'] = 1;
        $data['comment'] = $workComment;
        $data['onerror_img'] = \CommonClass::getDomain() . '/' . $this->theme->asset()->url('images/defauthead.png');

        return response()->json($data);
    }

    // 提交回复
    public function ajaxComment(CommentRequest $request)
    {
        $data = $request->except('_token');
        $data['comment'] = htmlspecialchars($data['comment']);
        $data['uid'] = $this->user['id'];
        $user = UserDetailModel::where('uid', $this->user['id'])->first();
        $users = UserModel::where('id', $this->user['id'])->first();
        $data['nickname'] = $users['name'];

        $data['created_at'] = date('Y-m-d H:i:s', time());

        //将数据存入数据库
        $result = WorkCommentModel::create($data);

        if (!$result) return response()->json(['errCode' => 0, 'errMsg' => '提交回复失败！']);
        //查询回复数据
        $comment_data = WorkCommentModel::where('id', $result['id'])->with('parentComment')->with('user')->with('users')->first()->toArray();
        $domain = \CommonClass::getDomain();
        $comment_data['avatar_md5'] = $domain . '/' . $user['avatar'];

        if (is_array($comment_data['parent_comment'])) {
            $comment_data['parent_user'] = $comment_data['parent_comment']['nickname'];
        }
        $comment_data['errCode'] = 1;
        $comment_data['onerror_img'] = \CommonClass::getDomain() . '/' . $this->theme->asset()->url('images/defauthead.png');

        return response()->json($comment_data);
    }

    // 评价页面
    public function evaluate(Request $request)
    {
        $data = $request->all();
        $uid = $this->user->id;
        $is_checked = WorkModel::where('task_id', $data['id'])
            ->where('uid', $uid)
            ->where('status', 3)
            ->first();
        $task = TaskModel::find($data['id']);
        if (!$is_checked && $task->uid != $uid) {
            return redirect()->back()->with(['error' => '你不具备评价资格！']);
        }
        $evaluate_people = $comment_people = null;
        $evaluate_from = -1;
        if ($is_checked) {
            $evaluate_people = UserDetailModel::select('user_detail.*', 'us.name as username')
                ->where('uid', $task->uid)
                ->join('users as us', 'user_detail.uid', '=', 'us.id')
                ->first();
            $work = WorkModel::where('id', $data['work_id'])->first();
            $comment_people = UserDetailModel::where('uid', $work['uid'])->first();
            $evaluate_from = 0;
        } elseif ($task->uid == $uid) {
            $work = WorkModel::where('id', $data['work_id'])->first();
            $evaluate_people = UserDetailModel::select('user_detail.*', 'us.name as username')
                ->where('uid', $work['uid'])
                ->join('users as us', 'user_detail.uid', '=', 'us.id')
                ->first();
            $comment_people = UserDetailModel::where('uid', $task->uid)->first();
            $evaluate_from = 1;
        }
        $domain = \CommonClass::getDomain();
        $view = [
            'evaluate_people' => $evaluate_people,
            'task_id' => $data['id'],
            'work_id' => $data['work_id'],
            'domain' => $domain,
            'comment_people' => $comment_people,
            'evaluate_from' => $evaluate_from
        ];
        $this->initTheme('task');
        $this->theme->setTitle('任务互评');
        return $this->theme->scope('task.evaluate', $view)->render();
    }

    // 交易评论
    public function evaluateCreate(Request $request)
    {
        $data = $request->except('token');
        $uid = $this->user->id;
        $is_checked = WorkModel::where('task_id', $data['task_id'])
            ->where('uid', $uid)
            ->where('status', 3)
            ->first();
        $task = TaskModel::find($data['task_id']);
        if (!$is_checked && $task->uid != $uid) {
            return redirect()->back()->withErrors(['error' => '你不具备评价资格！']);
        }
        $data['from_uid'] = $uid;
        $data['comment'] = e($data['comment']);
        $data['created_at'] = date('Y-m-d H:i:s');
        if ($is_checked) {
            $data['to_uid'] = $task->uid;
            $data['comment_by'] = 0;
        } elseif ($task->uid == $uid) {
            $work = WorkModel::find($data['work_id']);
            $data['to_uid'] = $work->uid;
            $data['comment_by'] = 1;
        }
        $is_evaluate = CommentModel::where('from_uid', $uid)
            ->where('task_id', $data['task_id'])
            ->where('to_uid', $data['to_uid'])
            ->first();
        if ($is_evaluate) {
            return redirect()->back()->withErrors(['error' => '你已经评论过了！']);
        }
        if (CommentModel::commentCreate($data)) {
            return redirect()->to("task/{$data['task_id']}")->with(['massage' => '评论成功！']);
        } else {
            return redirect()->back()->withErrors(['error' => '评论失败！']);
        }
    }

    // 交易维权提交
    public function ajaxRights(Request $request)
    {
        $allow = ['task_id', 'work_id', 'type', 'desc'];
        $data = $request->only($allow);
        $data['desc'] = e($data['desc']);
        $data['status'] = 0;
        $data['created_at'] = date("Y-m-d H:i:s");
        $work = WorkModel::where('id', $data['work_id'])->first();
        if ($work->status == 4) {
            return redirect()->back()->with(['error' => '当前稿件正在维权']);
        }
        $is_checked = WorkModel::where('id', $data['work_id'])
            ->where('status', 2)
            ->where('task_id', $data['task_id'])
            ->where('uid', $this->user->id)
            ->first();
        $task = TaskModel::where('id', $data['task_id'])->first();
        if (!$is_checked && $task->uid != $this->user->id) {
            return redirect()->back()->with(['error' => '你不具备维权资格！']);
        }
        if ($is_checked) {
            $data['role'] = 0;
            $data['from_uid'] = $this->user->id;
            $data['to_uid'] = $task->uid;
        } elseif ($task->uid == $this->user->id) {
            $data['role'] = 1;
            $data['from_uid'] = $this->user->id;
            $data['to_uid'] = $work->uid;
        }
        $result = TaskRightsModel::rightCreate($data);
        if (!$result) {
            return redirect()->back()->with(['error' => '维权失败！']);
        }
        $trading_rights = MessageTemplateModel::where('code_name', 'trading_rights')
            ->where('is_open', 1)
            ->where('is_on_site', 1)
            ->first();
        if ($trading_rights) {
            $task = TaskModel::find($data['task_id']);
            $from_user = UserModel::where('id', $this->user->id)->first();
            $site_name = \CommonClass::getConfig('site_name');
            $fromMessageVariableArr = [
                'username' => $from_user['name'],
                'tasktitle' => $task->title,
                'website' => $site_name,
            ];
            $fromMessage = MessageTemplateModel::sendMessage('trading_rights', $fromMessageVariableArr);
            $messages = [
                'message_title' => $trading_rights['name'],
                'code_name' => 'trading_rights',
                'message_content' => $fromMessage,
                'js_id' => $from_user['id'],
                'message_type' => 2,
                'receive_time' => date('Y-m-d H:i:s'),
                'status' => 0,
            ];
            MessageReceiveModel::create($messages);
        }
        return redirect()->to("task/{$data['task_id']}")->with(['error' => '维权成功！']);
    }

    // 举报
    public function report(Request $request)
    {
        $domain = \CommonClass::getDomain();
        $uid = $this->user->id;
        $allow = ['task_id', 'work_id', 'type', 'desc'];
        $data = $request->only($allow);
        $data['desc'] = e($data['desc']);
        $is_report = TaskReportModel::where('from_uid', $uid)
            ->where('task_id', $data['task_id'])
            ->where('work_id', $data['work_id'])
            ->first();
        if ($is_report) {
            return response()->json(['errCode' => 0, 'errMsg' => '您已经成功举报过，请等候平台处理']);
        }

        $work_data = WorkModel::find($data['work_id']);
        //保存举报信息
        $data['status'] = 0;
        $data['from_uid'] = $uid;
        $data['to_uid'] = $work_data->uid;
        $data['created_at'] = date('Y-m-d H:s:i');
        $result2 = TaskReportModel::create($data);
        if (!$result2) {
            return response()->json(['errCode' => 0, 'errMsg' => '举报失败！']);
        }
        $task_publish_success = MessageTemplateModel::where('code_name', 'report')
            ->where('is_open', 1)
            ->where('is_on_site', 1)
            ->first();
        if ($task_publish_success) {
            $task = TaskModel::find($data['task_id']);
            $site_name = \CommonClass::getConfig('site_name');//必要条件
            //组织好系统消息的信息
            $messageVariableArr = [
                'username' => $this->user->name,
                'href' => "$domain/task/{$data['task_id']}",
                'task_title' => $task->title,
                'website' => $site_name,
            ];
            $message = MessageTemplateModel::sendMessage('report ', $messageVariableArr);
            $message = [
                'message_title' => $task_publish_success['name'],
                'code' => 'report',
                'message_content' => $message,
                'js_id' => $uid,
                'message_type' => 2,
                'receive_time' => date('Y-m-d H:i:s'),
                'status' => 0,
            ];
            MessageReceiveModel::create($message);
        }
        return response()->json(['errCode' => 1, 'errMsg' => '举报成功！']);
    }

    // ajax分页投稿内容
    public function ajaxPageWorks(Request $request, $id)
    {
        $data = $request->all();
        $detail = TaskModel::detail($id);
        $user_type = 3;
        $is_win_bid = 0;
        if ($detail->status > 2 && Auth::check()) {
            if ($detail->uid == $this->user->id) {
                $user_type = 1;
            } elseif (WorkModel::isWorker($this->user->id, $detail->id)) {
                $user_type = 2;
                $is_win_bid = WorkModel::isWinBid($id, $this->user->id);
            }
        }
        $domain = \CommonClass::getDomain();
        $works_data = WorkModel::findAll($id, $data);
        $works_count = WorkModel::where('task_id', $id)->where('status', '<=', 1)->where('forbidden', 0)->count();
        $works_bid_count = WorkModel::where('task_id', $id)->where('status', 1)->where('forbidden', 0)->count();
        $view = [
            'detail' => $detail,
            'works' => $works_data,
            'merge' => $data,
            'works_count' => $works_count,
            'works_bid_count' => $works_bid_count,
            'user_type' => $user_type,
            'is_win_bid' => $is_win_bid,
            'domain' => $domain,
        ];
        $this->initTheme('ajaxpage');
        return $this->theme->scope('task.pagework', $view)->render();
    }

    //ajax分页交付内容
    public function ajaxPageDelivery(Request $request, $id)
    {
        $data = $request->all();
        $detail = TaskModel::detail($id);
        $user_type = 3;
        $is_win_bid = 0;
        $is_delivery = 0;
        if ($detail->status > 2 && Auth::check()) {
            if ($detail->uid == $this->user->id) {
                $user_type = 1;
            } elseif (WorkModel::isWorker($this->user->id, $detail->id)) {
                $user_type = 2;
                $is_win_bid = WorkModel::isWinBid($id, $this->user->id);
                $is_delivery = WorkModel::where('task_id', $id)
                    ->where('status', '>', 1)
                    ->where('uid', $this->user->id)
                    ->first();
            }
        }
        $delivery = [];
        $delivery_count = 0;
        if (Auth::check()) {
            if ($user_type == 2) {
                $delivery = WorkModel::select('work.*', 'us.name as username', 'a.avatar', 'a.nickname')
                    ->where('work.uid', $this->user->id)
                    ->where('work.task_id', $id)
                    ->where('work.status', '>=', 2)
                    ->with('childrenAttachment')
                    ->join('user_detail as a', 'a.uid', '=', 'work.uid')
                    ->leftjoin('users as us', 'us.id', '=', 'work.uid')
                    ->paginate(5)
                    ->setPageName('delivery_page')
                    ->toArray();
                $delivery_count = 1;
            } elseif ($user_type == 1) {
                $delivery = WorkModel::findDelivery($id, $data);
                $delivery_count = WorkModel::where('task_id', $id)
                    ->where('status', '>=', 2)
                    ->count();
            }
        }
        $works_data = WorkModel::findAll($id, $data);
        $domain = \CommonClass::getDomain();
        $agree = AgreementModel::where('code_name', 'task_delivery')->first();
        $view = [
            'detail' => $detail,
            'delivery' => $delivery,
            'delivery_count' => $delivery_count,
            'is_delivery' => $is_delivery,
            'merge' => $data,
            'user_type' => $user_type,
            'is_win_bid' => $is_win_bid,
            'domain' => $domain,
            'works' => $works_data,
            'agree' => $agree
        ];
        $this->initTheme('ajaxpage');
        return $this->theme->scope('task.pagedelivery', $view)->render();
    }

    // ajax分页评价
    public function ajaxPageComment(Request $request, $id)
    {
        $data = $request->all();
        $detail = TaskModel::detail($id);
        $data['task_user_id'] = $detail['uid'];
        $comment = CommentModel::taskComment($id, $data);
        $comment_count = CommentModel::where('task_id', $id)->count();
        $good_comment = CommentModel::where('task_id', $id)->where('type', 1)->count();
        $middle_comment = CommentModel::where('task_id', $id)->where('type', 2)->count();
        $bad_comment = CommentModel::where('task_id', $id)->where('type', 3)->count();
        $domain = \CommonClass::getDomain();
        $view = [
            'detail' => $detail,
            'merge' => $data,
            'comment' => $comment,
            'comment_count' => $comment_count,
            'good_comment' => $good_comment,
            'middle_comment' => $middle_comment,
            'bad_comment' => $bad_comment,
            'domain' => $domain,
        ];
        $this->initTheme('ajaxpage');
        return $this->theme->scope('task.pageComment', $view)->render();
    }

    // 记住当前的位置
    public function rememberTable(Request $request)
    {
        if ($index = $request->get('index')) {
            setcookie('table_index', $index, time() + 3600);
        } else {
            setcookie('table_index', 1, time() + 3600);
        }
    }
}
