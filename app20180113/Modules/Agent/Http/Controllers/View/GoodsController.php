<?php

namespace App\Modules\Agent\Http\Controllers\View;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Http\Controllers\HelpsController;
use App\Modules\Agent\Model\GoodsCategory;
use App\Modules\Agent\Model\TaskPriceRange;
use App\Modules\Manage\Model\AgreementModel;
use App\Modules\Task\Model\ServiceModel;
use App\Modules\Task\Model\TaskAttachmentModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\Task\Model\TaskModel;
use App\Modules\Task\Model\TaskServiceModel;
use App\Modules\Task\Model\TaskTypeModel;
use App\Modules\Task\Model\WorkModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\Attribute;
use App\Modules\User\Model\CommentModel;
use App\Modules\User\Model\DistrictModel;
use App\Modules\User\Model\GoodsAttribute;
use App\Modules\User\Model\GoodsCart;
use App\Modules\User\Model\GoodsStock;
use App\Modules\User\Model\GoodsType;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GoodsController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'goods');
    }

    // 商品
    public function index(Request $request)
    {
        $merge = $request->all();
        $list = ModelsContentModel::where('uid', $this->store->id);
        if ($get = $request->get('screen')) {
            switch ($get) {
                case 'Y':
                    $list->where('is_on_sale', 'Y');
                    break;
                case 'N':
                    $list->where('is_on_sale', 'N');
                    break;
            }
        } else {
            $list->where('is_on_sale', 'Y');
        }
        if ($title = $request->get('title')) {
            $list->where('title', 'LIKE', "%{$title}%");
        }
        if ($goods_cat_id = $request->get('goods_cat_id')) {
            $list->where('goods_cat_id', $goods_cat_id);
        }
        $perPage = $request->get('perPage') ? $request->get('perPage') : '10';
        $list  = $list->orderBy('create_time', 'DESC')->paginate($perPage);
        $mysql_prefix = config('database.connections.mysql.prefix');
        $count = ModelsContentModel::from('models_content as mc')
            ->select([
                DB::raw("count(if(`{$mysql_prefix}mc`.`is_on_sale` = 'Y', true, null)) as on_sale"),
                DB::raw("count(if(`{$mysql_prefix}mc`.`is_on_sale` = 'N', true, null)) as off_sale"),
            ])
            ->where('uid', $this->store->id)
            ->first();
        $screen = [
            [
                'txt' => '在售中',
                'name' => 'screen',
                'value' => 'Y',
                'count' => $count->on_sale,
            ],
            [
                'txt' => '仓库中',
                'name' => 'screen',
                'value' => 'N',
                'count' => $count->off_sale,
            ],
        ];

        $perPageList = [
            [
                'name' => '每页显示10条数据',
                'value' => '10'
            ],
            [
                'name' => '每页显示20条数据',
                'value' => '20'
            ],
            [
                'name' => '每页显示30条数据',
                'value' => '30'
            ],
            [
                'name' => '每页显示40条数据',
                'value' => '40'
            ],
            [
                'name' => '每页显示50条数据',
                'value' => '50'
            ]
        ];
        $cat = GoodsCategory::getList($this->store->id);
        $view = [
            'list' => $list,
            'perPage' => $perPage,
            'screen' => $screen,
            'perPageList' => $perPageList,
            'merge' => $merge,
            'cat' => $cat
        ];
        $this->theme->setTitle('商品');
        return $this->theme->scope($this->prefix . '.goods.index', $view)->render();
    }

    // 商品-默认页-批量操作-改分组@ajax
    public function batchCat(Request $request)
    {
        $uid = $this->store->id;
        $ids = $request->get('ids', []);
        $cat_id = $request->get('cat_id', 0);
        if (! $ids && ! $cat_id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $ret = ModelsContentModel::where('uid', $uid)
            ->whereIn('id', $ids)
            ->update(['goods_cat_id' => $cat_id, 'update_time' => time()]);
        if ($ret) {
            return response()->json(['code' => '1000', 'msg' => '批量转移分组成功']);
        } else {
            return response()->json(['code' => '1003', 'msg' => '批量转移分组失败']);
        }
    }

    // 商品-默认页-批量操作-交还设计师@ajax
    public function batchBack(Request $request)
    {
        $uid = $this->store->id;
        $ids = $request->get('ids', []);
        if (! $ids) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $tmp = ModelsContentModel::select('id', 'old_uid')
            ->where('uid', $uid)
            ->whereIn('id', $ids)
            ->get();
        $time = time();
        $update = [];
        foreach ($tmp as $v) {
            $update[] = [
                'id' => $v->id,
                'uid' => $v->old_uid,
                'is_goods' => 0,
                'update_time' => $time,
                'is_private' => 1,
                'goods_cat_id' => 0,
                'goods_number' => 0,
                'price' => 0,
                'is_on_sale' => 'N',
                'goods_type_id' => 0,
                'transaction_mode' => 0
            ];
        }
        $ret = DB::transaction(function () use ($ids, $uid, $update) {
            GoodsAttribute::whereIn('goods_id', $ids)
                ->where('user_id', $uid)
                ->delete();
            GoodsStock::whereIn('goods_id', $ids)->delete();
            GoodsCart::whereIn('goods_id', $ids)->update(['is_effective' => 'N']);
            $ret = update_batch($update, 'models_content');
            DB::update($ret['sql'], $ret['bindings']);
        });
        $ret = is_null($ret) ? true : false;
        if ($ret) {
            return response()->json(['code' => '1000', 'msg' => '批量交还成功']);
        } else {
            return response()->json(['code' => '1003', 'msg' => '批量交还失败']);
        }
    }

    // 商品-默认页-批量操作-下架@ajax
    public function batchOffSale(Request $request)
    {
        $uid = $this->store->id;
        $ids = $request->get('ids', []);
        if (! $ids) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $update = [
            'is_private' => 1,
            'is_on_sale' => 'N',
            'update_time' => time()
        ];
        $ret = ModelsContentModel::where('uid', $uid)
            ->whereIn('id', $ids)
            ->update($update);
        if ($ret) {
            return response()->json(['code' => '1000', 'msg' => '批量下架成功']);
        } else {
            return response()->json(['code' => '1003', 'msg' => '批量下架失败']);
        }
    }

    // 商品-任务列表
    public function taskList(Request $request)
    {
        $merge = $request->all();
        $uid = $this->store->id;
        $list = TaskModel::from('task as a')
            ->select(['a.*', 'b.name as type_name'])
            ->where('uid', $uid);
        if ($status = $request->get('status')) {
            $list->where('a.status', $status);
        }
        if ($title = $request->get('s_title')) {
            $list->where('a.title', 'LIKE', "%{$title}%");
        }
        if ($time = $request->get('time')) {
            $start = date('Y-m-d H:i:s', strtotime("-{$time} month"));
            $end = date('Y-m-d H:i:s');
            $list->whereBetween('a.created_at', [$start, $end]);
        }
        if ($type_id = $request->get('type_id')) {
            $list->where('a.type_id', $type_id);
        }
        $perPage = $request->get('perPage') ? $request->get('perPage') : '10';
        $list->leftjoin('task_type as b', 'a.type_id', '=', 'b.id');
        $list = $list->orderBy('a.created_at', 'desc')->paginate($perPage);
        $perPageList = [
            [
                'name' => '每页显示10条数据',
                'value' => '10'
            ],
            [
                'name' => '每页显示20条数据',
                'value' => '20'
            ],
            [
                'name' => '每页显示30条数据',
                'value' => '30'
            ],
            [
                'name' => '每页显示40条数据',
                'value' => '40'
            ],
            [
                'name' => '每页显示50条数据',
                'value' => '50'
            ]
        ];
        $agree = AgreementModel::getInfoByKey('task_publish');
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
        $time = [
            0 => '不限时段',
            1 => '1个月',
            3 => '3个月',
            6 => '6个月'
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
        $type = TaskTypeModel::getList();
        $siteUrl = HelpsController::getConfigRule('site_url');
        $view = [
            'list' => $list,
            'perPage' => $perPage,
            'perPageList' => $perPageList,
            'merge' => $merge,
            'agree' => $agree,
            'status' => $status,
            'time' => $time,
            'type' => $type,
            'verified' => $verified,
            'bounty' => $bounty,
            'siteUrl' => $siteUrl
        ];
        $this->theme->setTitle('任务列表-商品');
        return $this->theme->scope($this->prefix . '.goods.taskList', $view)->render();
    }

    // 商品-托管赏金@ajax
    public function taskBounty(Request $request, $id = 0)
    {
        $uid = $this->store->id;
        $type = $request->input('type');
        if ($id <= 0 || ! in_array($type, ['merge', 'bounty', 'server'])) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = TaskModel::where('id', $id)
            ->where('uid', $uid)
            ->where('verified_status', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        switch ($type) {
            case 'merge':
                if ($info->bounty_status == 2 || $info->server_status == 2) {
                    return response()->json(['code' => '1100', 'msg' => '您已完成支付']);
                }
                $ids = TaskServiceModel::where('task_id', $info->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $info->service = price_format($service);
                $total = $info->service + $info->bounty;
                break;
            case 'bounty':
                if ($info->bounty_status == 2) {
                    return response()->json(['code' => '1100', 'msg' => '您已完成支付']);
                }
                $total = $info->bounty;
                break;
            case 'server':
                if ($info->server_status == 2) {
                    return response()->json(['code' => '1100', 'msg' => '您已完成支付']);
                }
                $ids = TaskServiceModel::where('task_id', $info->id)
                    ->lists('service_id')
                    ->toArray();
                $service = ServiceModel::whereIn('id', $ids)->sum('price');
                $info->service = price_format($service);
                $total = $info->service;
                break;
            default:
                $total = 0.00;
                break;
        }
        $balance = UserDetailModel::where('uid', $this->store->id)
            ->where('balance_status', 0)
            ->value('balance');
        if ($balance >= $total) {
            $balance_status = true;
        } else {
            $balance_status = false;
        }
        $view = [
            'info' => $info,
            'total' => $total,
            'balance_status' => $balance_status,
            'type' => $type,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.taskBountyPay', $view)->render()
        ]);
    }

    // 商品-任务列表-任务操作@ajax
    public function taskInfo($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = TaskModel::where('id', $id)
            ->where('uid', $uid)
            ->where('status', '>', 2)
            ->where('verified_status', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        $work = WorkModel::getWorkData($id);
        $delivery = WorkModel::getDeliveryData($id);
        $comment = CommentModel::getCommentData($id);
        $domain = \CommonClass::domain();
        $nav = [
            [
                'name' => '投稿记录',
                'class' => 'active',
                'tab' => 'work',
            ],
            [
                'name' => '交付内容',
                'class' => '',
                'tab' => 'delivery',
            ],
            [
                'name' => '任务互评',
                'class' => '',
                'tab' => 'comment',
            ],
            /*[
                'name' => '任务维权',
                'class' => '',
                'tab' => 'rights',
            ],*/
        ];
        $view = [
            'info' => $info,
            'nav' => $nav,
            'work' => $work,
            'delivery' => $delivery,
            'domain' => $domain,
            'uid' => $uid,
            'comment' => $comment,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.taskInfo', $view)->render()
        ]);
    }

    // 商品-任务列表-获取分页数据@ajax
    public function ajaxPage(Request $request)
    {
        $allow = ['id', 'type'];
        $data = $request->only($allow);
        $info = TaskModel::where('id', $data['id'])
            ->where('uid', $this->store->id)
            ->where('status', '>', 2)
            ->where('verified_status', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        switch ($data['type']) {
            case 'work':
                $work = WorkModel::getWorkData($data['id']);
                $domain = \CommonClass::domain();
                $view = [
                    'info' => $info,
                    'work' => $work,
                    'domain' => $domain,
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view($this->prefix . '.goods.workPage', $view)->render()
                ]);
                break;
            case 'delivery':
                $delivery = WorkModel::getDeliveryData($data['id']);
                $domain = \CommonClass::domain();
                $view = [
                    'info' => $info,
                    'delivery' => $delivery,
                    'domain' => $domain,
                    'uid' => $this->store->id,
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view($this->prefix . '.goods.deliveryPage', $view)->render()
                ]);
                break;
            case 'comment':
                $comment = CommentModel::getCommentData($data['id']);
                $view = [
                    'info' => $info,
                    'comment' => $comment,
                ];
                return response()->json([
                    'code' => '1000',
                    'data' => view($this->prefix . '.goods.commentPage', $view)->render()
                ]);
                break;
            /*case 'rights':

                break;*/
            default:
                return response()->json(['code' => '1001', 'msg' => '非法操作']);
                break;
        }
    }

    // 商品-任务列表-任务中标@ajax
    public function winBid(Request $request)
    {
        $allow = ['work_id', 'task_id'];
        $data = $request->only($allow);
        $info = TaskModel::where('id', $data['task_id'])
            ->where('uid', $this->store->id)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1001', 'msg' => '查询任务失败']);
        }
        $win_bid_num = WorkModel::where('task_id', $data['task_id'])->where('status', 1)->count();
        if ($info->worker_num > $win_bid_num) {
            $data = [
                'task_id' => $data['task_id'],
                'work_id' => $data['work_id'],
                'worker_num' => $info->worker_num,
                'win_bid_num' => $win_bid_num,
            ];
            if (WorkModel::winBid($data)) {
                return response()->json(['code' => '1000', 'msg' => '操作成功']);
            } else {
                return response()->json(['code' => '1004', 'msg' => '操作失败']);
            }
        } else {
            return redirect()->back()->with(['error' => '当前中标人数已满']);
        }
    }

    // 商品-任务列表-稿件通过验收@ajax
    public function workCheck(Request $request)
    {
        $allow = ['work_id'];
        $data = $request->only($allow);
        $work_data = WorkModel::find($data['work_id']);
        if (! TaskModel::isEmployer($work_data->task_id, $this->store->id)) {
            return response()->json(['code' => '1100', 'msg' => '您不是雇主，您的操作有误']);
        }
        if ($work_data->status != 2) {
            return response()->json(['code' => '1100', 'msg' => '当前稿件不具备验收资格']);
        }
        $data['uid'] = $work_data->uid;
        $data['worker_num'] = TaskModel::where('id',$work_data['task_id'])->value('worker_num');
        $data['win_check'] = WorkModel::where('task_id', $work_data['task_id'])->where('status', '>', 2)->count();
        $data['task_id'] = $work_data->task_id;
        $data['work_status'] = 3;
        $data['store_uid'] = $this->store->id;
        $data['action_id'] = $work_data->action_id;
        if (WorkModel::workCheck($data)) {
            return response()->json(['code' => '1000', 'msg' => '验收成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '操作失败']);
        }
    }

    // 商品-任务列表-评价页面@ajax
    public function commentPage(Request $request)
    {
        $allow = ['work_id', 'task_id'];
        $data = $request->only($allow);
        $uid = $this->store->id;
        $work = WorkModel::where('id', $data['work_id'])
            ->where('status', 3)
            ->first();
        $task = TaskModel::find($work->task_id);
        if (! $work || $task->uid != $uid) {
            return response()->json(['code' => '1100', 'msg' => '你不具备评价资格']);
        }
        $user = UserDetailModel::from('user_detail as ud')
            ->select([
                'ud.*',
                'u.name as username',
            ])
            ->join('users as u', 'u.id', '=', 'ud.uid')
            ->where('uid', $work->uid)
            ->first();
        $shop = UserDetailModel::where('uid', $uid)->first();
        $shop->username = $this->store->name;
        $view = [
            'work' => $work,
            'user' => $user,
            'shop' => $shop,
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.comment', $view)->render()
        ]);
    }

    // 商品-任务列表-评价@ajax
    public function comment(Request $request)
    {
        $allow = [
            'type',
            'comment',
            'speed_score',
            'quality_score',
            'attitude_score',
            'task_id',
            'work_id'
        ];
        $data = $request->only($allow);
        $uid = $this->store->id;
        $work = WorkModel::where('id', $data['work_id'])
            ->where('status', 3)
            ->first();
        $task = TaskModel::find($work->task_id);
        if (! $work || $task->uid != $uid) {
            return response()->json(['code' => '1100', 'msg' => '你不具备评价资格']);
        }
        $data['from_uid'] = $uid;
        $data['comment'] = e($data['comment']);
        $data['created_at'] = date('Y-m-d H:i:s');
        $work = WorkModel::find($data['work_id']);
        $data['to_uid'] = $work->uid;
        $data['comment_by'] = 1;
        $is_evaluate = CommentModel::where('from_uid', $uid)
            ->where('task_id', $data['task_id'])
            ->where('to_uid', $data['to_uid'])
            ->first();
        if ($is_evaluate){
            return response()->json(['code' => '1100', 'msg' => '你已经评论过了']);
        }
        if (CommentModel::commentCreate($data)){
            return response()->json(['code' => '1000', 'msg' => '评论成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '评论失败']);
        }
    }

    // 商品-任务列表-删除任务@ajax
    public function taskDel($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = TaskModel::where('uid', $uid)
            ->where('id', $id)
            ->where('bounty_status', '<>', 2)
            ->where('verified_status', '<>', 3)
            ->where('status', '<', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        $status = DB::transaction(function () use ($id, $uid) {
            TaskModel::destroy($id);
            $ids = TaskAttachmentModel::where('task_id', $id)
                ->lists('attachment_id')
                ->toArray();
            if (count($ids)) {
                TaskAttachmentModel::where('task_id', $id)->delete();
                AttachmentModel::where('user_id', $uid)->whereIn('id', $ids)->delete();
            }
            TaskServiceModel::where('task_id', $id)->delete();
        });
        $status = is_null($status) ? true : false;
        if ($status) {
            return response()->json(['code' => '1000', 'msg' => '删除成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '删除失败']);
        }
    }

    // 商品-任务列表-编辑任务@ajax
    public function taskEdit($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = TaskModel::where('uid', $uid)
            ->where('id', $id)
            ->where('verified_status', '<', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        $region_limit = [
            [
                'name' => '不限地区',
                'val' => 1
            ],
            [
                'name' => '指定地区',
                'val' => 2
            ]
        ];
        $cate = TaskCateModel::getCategoryList();
        $cate_children = TaskCateModel::getCategoryList($info->cate_pid);
        $service = ServiceModel::getList();
        $type = TaskTypeModel::getList();
        $province = DistrictModel::getRegionList();
        $city = [];
        $area = [];
        if ($info->region_limit == 2) {
            $city = DistrictModel::getRegionList($info->province);
            $area = DistrictModel::getRegionList($info->city);
        }
        $files = TaskAttachmentModel::where('task_id', $info->id)
            ->lists('attachment_id')->toArray();
        $files = AttachmentModel::select('size', 'name', 'id', 'url')
            ->where('user_id', $uid)
            ->where('status', 1)
            ->whereIn('id', $files)
            ->get();
        $task_service = TaskServiceModel::where('task_id', $id)
            ->lists('service_id')
            ->toArray();
        $service_price = ServiceModel::whereIn('id', $task_service)->sum('price');
        $info->bounty = price_format($info->type_id == 2 ? 0.00 : $info->bounty);
        $total_price = price_format($info->bounty + $service_price);
        $price_range = TaskPriceRange::getList();
        $view = [
            'info' => $info,
            'cate' => $cate,
            'cate_children' => $cate_children,
            'region_limit' => $region_limit,
            'province' => $province,
            'city' => $city,
            'area' => $area,
            'service' => $service,
            'task_service' => $task_service,
            'type' => $type,
            'total_price' => $total_price,
            'price_range' => $price_range
        ];
        return response()->json([
            'code' => '1000',
            'init' => ['file' => $files],
            'data' => view($this->prefix . '.goods.taskEdit', $view)->render()
        ]);
    }

    // 商品-任务列表-编辑任务处理@ajax
    public function taskUpdate(Request $request)
    {
        $uid = $this->store->id;
        $this->validate($request, [
            'phone' => [
                'required',
                'regex:/^1[34578]\d{9}$/',
            ],
            'cate_id' => 'required',
            'title' => 'required',
            'desc' => 'required',
            'agree' => 'accepted',
            'delivery_deadline' => [
                'required',
                'date_format:Y-m-d'
            ],
            'type_id' => 'required'
        ], [
            'phone.required' => '请填写联系手机',
            'phone.regex' => '联系手机格式不正确',
            'cate_id.required' => '请选择分类',
            'title.required' => '请填写需求标题',
            'desc.required' => '请填写需求详情',
            'agree.accepted' => '您必须同意《任务发布协议》',
            'delivery_deadline.required' => "请选择截稿结束时间",
            'delivery_deadline.date_format' => "截稿结束时间格式不正确",
            'type_id.required' => "请选择交易模式",
        ]);
        $all = $request->all();
        $data = [
            'cate_pid' => $all['cate_pid'],
            'cate_id' => $all['cate_id'],
            'province' => $all['province'],
            'city' => $all['city'],
            'area' => $all['area'],
            'desc' => $all['desc'],
            'phone' => $all['phone'],
            'region_limit' => $all['region_limit'],
            'title' => $all['title'],
            'type_id' => $all['type_id'],
            'status' => $all['status'],
            'id' => $all['id'],
            'delivery_deadline' => $all['delivery_deadline'],
            'begin_at' => date('Y-m-d'),
            'product' => $request->get('product', []),
            'file_id' => $request->get('file_id', []),
        ];
        $info = TaskModel::where('uid', $uid)
            ->where('id', $data['id'])
            ->where('verified_status', '<', 3)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        $ret = TaskModel::checkType($all, $data);
        if (count($ret['err'])) {
            return response()->json($ret['err'], 422);
        }
        if (TaskModel::updateOne($ret['data'], $uid)) {
            return response()->json(['code' => '1000', 'msg' => '编辑成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '编辑失败']);
        }
    }

    // 商品-任务列表-发布任务@ajax
    public function issueTask()
    {
        $cate = TaskCateModel::getCategoryList();
        $province = DistrictModel::getRegionList();
        $region_limit = [
            [
                'name' => '不限地区',
                'val' => 1
            ],
            [
                'name' => '指定地区',
                'val' => 2
            ]
        ];
        $service = ServiceModel::getList();
        $type = TaskTypeModel::getList();
        $price_range = TaskPriceRange::getList();
        $view = [
            'cate' => $cate,
            'region_limit' => $region_limit,
            'province' => $province,
            'service' => $service,
            'type' => $type,
            'price_range' => $price_range
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.issueTask', $view)->render()
        ]);
    }

    // 商品-任务列表-发布任务处理@ajax
    public function issueTaskCreate(Request $request)
    {
        $uid = $this->store->id;
        $this->validate($request, [
            'phone' => [
                'required',
                'regex:/^1[34578]\d{9}$/',
            ],
            'cate_id' => 'required',
            'title' => 'required',
            'desc' => 'required',
            'agree' => 'accepted',
            'delivery_deadline' => [
                'required',
                'date_format:Y-m-d'
            ],
            'type_id' => 'required'
        ], [
            'phone.required' => '请填写联系手机',
            'phone.regex' => '联系手机格式不正确',
            'cate_id.required' => '请选择分类',
            'title.required' => '请填写需求标题',
            'desc.required' => '请填写需求详情',
            'agree.accepted' => '您必须同意《任务发布协议》',
            'delivery_deadline.required' => "请选择竞标结束时间",
            'delivery_deadline.date_format' => "竞标结束时间格式不正确",
            'type_id.required' => "请选择交易模式",
        ]);
        $all = $request->all();
        $data = [
            'cate_pid' => $all['cate_pid'],
            'cate_id' => $all['cate_id'],
            'province' => $all['province'],
            'city' => $all['city'],
            'area' => $all['area'],
            'desc' => $all['desc'],
            'phone' => $all['phone'],
            'region_limit' => $all['region_limit'],
            'title' => $all['title'],
            'type_id' => $all['type_id'],
            'status' => $all['status'],
            'delivery_deadline' => $all['delivery_deadline'],
            'begin_at' => date('Y-m-d'),
            'product' => $request->get('product', []),
            'file_id' => $request->get('file_id', []),
        ];
        $data['uid'] = $uid;
        $data['task_success_draw_ratio'] = HelpsController::getConfigRule('task_percentage');
        $data['task_fail_draw_ratio'] = HelpsController::getConfigRule('task_fail_percentage');
        $ret = TaskModel::checkType($all, $data);
        if (count($ret['err'])) {
            return response()->json($ret['err'], 422);
        }
        if (TaskModel::createOne($ret['data'])) {
            return response()->json(['code' => '1000', 'msg' => '添加成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '添加失败']);
        }
    }

    // 商品-任务列表-发布任务-获取分类@ajax
    public function taskCategory($id = 0)
    {
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $data = TaskCateModel::getCategoryList($id);
        return response()->json(['code' => '1000', 'data' => $data]);
    }

    // 商品-任务列表-发布任务-上传文件@ajax
    public function taskUpload(Request $request)
    {
        $uid = $this->store->id;
        $file = $request->file('file');
        $path  = ucfirst($this->module) . '/' . $this->flag . '/goods/task/';
        $allowed_extensions = [
            'png', 'jpg', 'jpeg', 'gif', 'bmp',
            'zar', 'doc', 'docx', 'xls', 'xlsx',
            'ppt', 'pptx', 'pdf'
        ];
        $result = upload_file($file, $path, $size = 2048, $allowed_extensions);
        if ($result['code']) {
            $create = [
                'name' => $result['filename'],
                'type' => $result['extension'],
                'size' => $result['fileSize'] / 1024,
                'url' => $result['filePath'],
                'status' => 0,
                'user_id' => $uid,
                'disk' => 'upload',
                'created_at' => date('Y-m-d H:i:s')
            ];
            if ($result = AttachmentModel::create($create)) {
                return response()->json(['code' => '1000', 'id' => $result->id]);
            } else {
                if (! empty($result['filePath']) && file_exists($result['filePath'])) {
                    @unlink($result['filePath']);
                }
                return response()->json(['code' => '1004', 'msg' => '文件上传失败']);
            }
        } else {
            return response()->json(['code' => '1009', 'msg' => $result['msg']]);
        }
    }

    // 商品-任务列表-发布任务-删除文件@ajax
    public function taskDelFile($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = AttachmentModel::where('id', $id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1009', 'msg' => '该附件并没有成功上传']);
        }
        $status = DB::transaction(function () use ($id) {
            AttachmentModel::destroy($id);
            TaskAttachmentModel::where('attachment_id', $id)->delete();
        });
        if (! $status) {
            if (! empty($info->url) && file_exists($info->url)) {
                @unlink($info->url);
            }
            return response()->json(['code' => '1000']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '附件删除失败']);
        }
    }

    // 商品-任务列表-发布任务-赏金验证@ajax
    public function checkBounty(Request $request)
    {
        $data = $request->only(['param', 'begin_at']);
        $begin_at = preg_replace('/([\x80-\xff]*)/i', '', $data['begin_at']);
        $begin_at = $begin_at ? $begin_at : date('Y-m-d');
        $max_limit = price_format(HelpsController::getConfigRule('task_bounty_max_limit'));
        $min_limit = price_format(HelpsController::getConfigRule('task_bounty_min_limit'));
        if (($min_limit > $data['param'])
            || ($max_limit < $data['param'] && $max_limit != 0)) {
            $data['info'] = '赏金应该大于' . $min_limit . '小于' . $max_limit;
            $data['status'] = false;
            return response()->json(['code' => '1000', 'data' => $data]);
        }
        $limit_time = HelpsController::getConfigRule('task_delivery_limit_time');
        $limit_time = json_decode($limit_time, true);
        $keys = array_keys($limit_time);
        $key = \CommonClass::get_rand($keys, $data['param']);
        if (in_array($key, $keys)) {
            $limit_time = $limit_time[$key];
        } else {
            reset($keys);
            $limit_time = $limit_time[end($keys)];
        }
        $data['status'] = true;
        $data['info'] = '您当前的发布的任务金额是：￥' . price_format($data['param']);
        $data['deadline'] = date('Y-m-d',strtotime($begin_at) + $limit_time * 24 * 3600);
        $data['begin_at'] = $begin_at;
        $data['time'] = '当前竞标时间最晚可设置为：' . $data['deadline'];
        return response()->json(['code' => '1000', 'data' => $data]);
    }

    // 商品-添加商品分组@ajax
    public function catStore(Request $request)
    {
        $this->validate($request, [
            'cat_name' => 'required',
            'parent_id' => [
                'regex:/^[\d]+$/'
            ],
            'sort_order' => [
                'regex:/^[\d]+$/'
            ]
        ], [
            'cat_name.required' => '请填写分组名称',
            'parent_id.regex' => '父级分组必须为整数',
            'sort_order.regex' => '推荐排序必须为整数'
        ]);
        $data = $request->all();
        $data['store_id'] = $this->store->id;
        $status = GoodsCategory::create($data);
        if ($status) {
            Cache::forget('store_cat_list@' . $this->store->id);
            if ($request->get('goods')) {
                return response()->json(['code' => '1000', 'data' => GoodsCategory::getList($this->store->id)]);
            } else {
                return response()->json(['code' => '1000', 'msg' => '新建分组成功']);
            }
        } else {
            return response()->json(['code' => '1004', 'msg' => '新建分组失败']);
        }
    }

    // 商品-获取设计师文件夹@ajax
    public function folderGet(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($uid != $this->store->id) {
                $perPage = $request->get('page') == 1 ? 17 : 18;
                $list = ModelsFolderModel::select('id', 'name', 'cover_img')
                    ->where('uid', $uid)
                    ->orderBy('create_time', 'desc')
                    ->paginate($perPage);
                if ($list->lastPage()) {
                    $view = [
                        'list' => $list
                    ];
                    return response()->json([
                        'code' => '1000',
                        'page' => $list->lastPage(),
                        'data' => view($this->prefix . '.goods.folderGet', $view)->render()
                    ]);
                } else {
                    return response()->json(['code' => '1005', 'msg' => '已获取全部文件夹']);
                }
            } else {
                return response()->json(['code' => '1002', 'msg' => '权限错误']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    // 商品-获取设计师某个文件夹下的模型@ajax
    public function modelsGet(Request $request)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($uid != $this->store->id) {
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
                        'data' => view($this->prefix . '.goods.modelsGet', $view)->render()
                    ]);
                } else {
                    return response()->json(['code' => '1005', 'msg' => '本文件夹暂无作品']);
                }
            } else {
                return response()->json(['code' => '1002', 'msg' => '权限错误']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    // 商品-设计师移交模型给店家@ajax
    public function modelsAdd($id = 0)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            if ($uid != $this->store->id) {
                if ($id <= 0) {
                    return response()->json(['code' => '1001', 'msg' => '非法操作']);
                }
                $info = ModelsContentModel::where('id', $id)
                    ->where('uid', $uid)
                    ->where('enroll_status', 0)
                    ->where('is_goods', 0)
                    ->first();
                if (! $info) {
                    return back()->with(['err' => '参数错误']);
                }
                $time = time();
                $update = [
                    'uid' => $this->store->id,
                    'is_goods' => 1,
                    'create_time' => $time,
                    'update_time' => $time,
                    'old_uid' => $uid,
                    'folder_id' => 0,
                    'is_private' => 1,
                    'transaction_mode' => 1
                ];
                $status = ModelsContentModel::where('id', $id)->update($update);
                if ($status) {
                    return response()->json(['code' => '1000', 'msg' => '商品移交成功']);
                } else {
                    return response()->json(['code' => '1004', 'msg' => '商品移交失败']);
                }
            } else {
                return response()->json(['code' => '1002', 'msg' => '权限错误']);
            }
        } else {
            return response()->json(['code' => '1003', 'msg' => '未登录或登录过期']);
        }
    }

    // 商品-编辑商品页面
    public function editGoods($id = 0)
    {
        if ($id <= 0) {
            return back()->with(['err' => '非法操作']);
        }
        $info = ModelsContentModel::where('id', $id)
            ->where('uid', $this->store->id)
            ->first();
        if (! $info) {
            return back()->with(['err' => '参数错误']);
        }
        //参数
        $param = [];
        if (!empty($info['paramaters'])) {
            $tempArray = explode('|', $info['paramaters']);
            foreach ($tempArray as $k => $v) {
                $paraArray = explode('：', $v);
                $param[$k] = $paraArray;
            }
        }
        $info->param = $param;
        // 上架
        $sale = [
            [
                'txt' => '是',
                'name' => 'is_on_sale',
                'value' => 'Y'
            ],
            [
                'txt' => '否',
                'name' => 'is_on_sale',
                'value' => 'N'
            ]
        ];
        // 商品分组
        $cat = GoodsCategory::getList($this->store->id);
        $mysql_prefix = config('database.connections.mysql.prefix');
        //获取类型列表
        $type = GoodsType::getList($this->store->id);
        $action = ModelsContentModel::from('models_content as mc')
            ->select([
                DB::raw("max(if(`{$mysql_prefix}mc`.`id` < {$id}, `{$mysql_prefix}mc`.`id`, null)) as prev"),
                DB::raw("min(if(`{$mysql_prefix}mc`.`id` > {$id}, `{$mysql_prefix}mc`.`id`, null)) as next"),
            ])
            ->where('uid', $this->store->id)
            ->first();
        $prev = $action->prev;
        $next = $action->next;
        $view_mode = [
            'once' => '次付',
            'month' => '月付',
            'permanent' => '永久',
        ];
        // 数据赋值
        $view = [
            'info' => $info,
            'sale' => $sale,
            'cat' => $cat,
            'type' => $type,
            'prev' => $prev,
            'next' => $next,
            'view_mode' => $view_mode,
        ];
        $this->theme->setTitle('编辑商品-商品');
        return $this->theme->scope($this->prefix . '.goods.editGoods', $view)->render();
    }

    // 商品-编辑商品处理
    public function updateGoods(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'goods_cat_id' => 'required',
            'price' => [
                'required',
                'regex:/^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$|^0$/'
            ],
            'goods_number' => [
                'required',
                'regex:/^\d+$/'
            ]
        ], [
            'title.required' => '请填写商品名称',
            'goods_cat_id.required' => '请选择商品分组',
            'price.required' => '请填写商品价格',
            'price.regex' => '商品价格格式不正确',
            'goods_number.required' => '请填写商品库存',
            'goods_number.regex' => '商品库存必须为整数'
        ]);
        $uid = $this->store->id;
        $id = $request->get('id');
        $time = date('Y-m-d H:i:s');
        // 基本信息处理
        $param = $request->get('param');
        $parameter = [];
        foreach ($param['key'] as $k => $v) {
            if (empty(trim($v)) || empty(trim($param['val'][$k]))) {
                continue;
            }
            $parameter[] = trim($v) . '：' . trim($param['val'][$k]);
        }
        if (count($parameter) < 2) {
            return back()->withErrors(['param' => '参数至少要填写两个'])->withInput();
        }
        $allow = [
            'title',
            'goods_cat_id',
            'price',
            'goods_number',
            'is_on_sale',
            'content',
            'goods_type_id',
            'view_mode',
        ];
        $data = $request->only($allow);
        $data['paramaters'] = implode('|', $parameter);
        $data['is_private'] = 0;
        $data['update_time'] = time();
        $data['content'] = remove_xss($data['content']);
        $data['is_goods'] = 1;
        $data['transaction_mode'] = 2;
        // 属性处理
        $update = [];
        $insert = [];
        $goods_attr_id = [];
        $manual = $request->get('manual', []);
        foreach ($manual as $v) {// 属性
            if ($v['goods_attr_id'] > 0 ) {
                if (! empty(trim($v['val']))) {
                    $update[] = [
                        'id' => $v['goods_attr_id'],
                        'attr_price' => null,
                        'attr_value' => trim($v['val']),
                        'updated_at' => $time
                    ];
                    $goods_attr_id[] = $v['goods_attr_id'];
                }
            } else {
                if (! empty(trim($v['val']))) {
                    $insert[] = [
                        'user_id' => $uid,
                        'goods_id' => $id,
                        'attribute_id' => $v['attr_id'],
                        'attr_price' => null,
                        'attr_value' => trim($v['val']),
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }
            }
        }
        $list = $request->get('list', []);
        foreach ($list as $v) {
            if (isset($v['checked']) && isset($v['price'])) {
                if ($v['goods_attr_id'] > 0) {
                    $update[] = [
                        'id' => $v['goods_attr_id'],
                        'attr_price' => price_format($v['price']),
                        'attr_value' => trim($v['val']),
                        'updated_at' => $time
                    ];
                    $goods_attr_id[] = $v['goods_attr_id'];
                } else {
                    $insert[] = [
                        'user_id' => $uid,
                        'goods_id' => $id,
                        'attribute_id' => $v['attr_id'],
                        'attr_price' => price_format($v['price']),
                        'attr_value' => trim($v['val']),
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }
            }
        }
        $attr = [
            'update' => $update,
            'insert' => $insert,
            'delete' => $goods_attr_id,
            'type_id' => $request->get('old_goods_type_id', 0),
            'stock' => $request->get('stock', [])
        ];
        // 数据处理
        $status = DB::transaction(function () use ($id, $uid, $data, $attr) {
            ModelsContentModel::where('id', $id)->where('uid', $uid)->update($data);
            if ($attr['type_id'] != $data['goods_type_id']) {
                GoodsAttribute::where('goods_id', $id)->where('user_id', $uid)->delete();
            }
            if (count($attr['delete'])) {
                GoodsAttribute::whereNotIn('id', $attr['delete'])
                    ->where('user_id', $uid)
                    ->where('goods_id', $id)
                    ->delete();
            }
            if (count($attr['update'])) {
                $ret = update_batch($attr['update'], 'goods_attributes');
                DB::update($ret['sql'], $ret['bindings']);
            }
            if (count($attr['insert'])) {
                GoodsAttribute::insert($attr['insert']);
            }
            if (count($attr['stock'])) {
                $ret = GoodsAttribute::handleAttrTable($attr['stock'], $uid, $id);
                if (count($ret['delete'])) {
                    GoodsStock::whereNotIn('id', $ret['delete'])
                        ->where('goods_id', $id)
                        ->delete();
                }
                if (count($ret['update'])) {
                    $up = update_batch($ret['update'], 'goods_stocks');
                    DB::update($up['sql'], $up['bindings']);
                }
                if (count($ret['insert'])) {
                    GoodsStock::insert($ret['insert']);
                }
            }
        });
        $status = is_null($status) ? ['suc' => '编辑商品成功'] : ['err' => '编辑商品失败'];
        return redirect()->route($this->prefix . '.goods.index')->with($status);
    }

    // 商品-编辑商品处理-上传商品描述的图片@ajax
    public function uploadContentImages(Request $request)
    {
        $image = $request->file('image');
        $path  = ucfirst($this->module) . '/' . $this->flag . '/goods/content/';
        $data  = [];
        $fail   = 0;
        foreach ($image as $v) {
            $result = upload_file($v, $path);
            if ($result['code']) {
                $data[] = '/' . $result['filePath'];
            } else {
                $fail++;
            }
        }
        return response()->json(['code' => '1000', 'data' => $data, 'fail' => $fail]);
    }

    // 商品-编辑商品处理-获取属性类型列表@ajax
    public function getTypeList()
    {
        $list = GoodsType::from('goods_types as gt')
            ->select([
                'gt.*',
                DB::raw('COUNT(' . config('database.connections.mysql.prefix') . 'a.id) as num'),
            ])
            ->leftJoin('attributes as a', 'a.goods_type_id', '=', 'gt.id')
            ->where('gt.user_id', $this->store->id)
            ->groupBy('gt.id')
            ->orderBy('gt.created_at', 'desc')
            ->paginate(18);
        $view = [
            'list' => $list
        ];
        return response()->json([
            'code' => '1000',
            'page' => $list->lastPage(),
            'data' => view($this->prefix . '.goods.type', $view)->render()
        ]);
    }

    // 商品-编辑商品处理-添加属性类型@ajax
    public function addType(Request $request)
    {
        $name = $request->get('name', null);
        if (empty($name)) {
            return response()->json(['code' => '1101', 'msg' => '类型名称不能为空']);
        }
        $data = [
            'name' => $name,
            'user_id' => $this->store->id
        ];
        if ($add = GoodsType::create($data)) {
            Cache::forget('store_type_list@' . $this->store->id);
            return response()->json(['code' => '1000', 'msg' => '新增成功', 'data' => $add]);
        } else {
            return response()->json(['code' => '1004', 'msg' => '新增失败']);
        }
    }

    // 商品-编辑商品处理-编辑属性类型@ajax
    public function editType(Request $request)
    {
        $uid = $this->store->id;
        $id = $request->get('id', 0);
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $name = $request->get('name', null);
        if (empty($name)) {
            return response()->json(['code' => '1101', 'msg' => '类型名称不能为空']);
        }
        $data = [
            'name' => $name
        ];
        $ret = GoodsType::where('id', $id)->where('user_id', $uid)->update($data);
        if ($ret) {
            Cache::forget('store_type_list@' . $this->store->id);
            return response()->json(['code' => '1000', 'msg' => '编辑成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '编辑失败']);
        }
    }

    // 商品-编辑商品处理-删除属性类型@ajax
    public function delType(Request $request)
    {
        $uid = $this->store->id;
        $id = $request->get('id', 0);
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $ret = GoodsType::where('id', $id)->where('user_id', $uid)->delete($id);
        if ($ret) {
            Cache::forget('store_type_list@' . $this->store->id);
            return response()->json(['code' => '1000', 'msg' => '移除成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '移除失败']);
        }
    }

    // 商品-编辑商品处理-获取属性列表@ajax
    public function getAttrList(Request $request)
    {
        $uid = $this->store->id;
        $id = $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $list = Attribute::from('attributes as a')
            ->select([
                'a.*',
                'gt.name as type_name',
                DB::raw('COUNT(' . config('database.connections.mysql.prefix') . 'ga.id) as num'),
            ])
            ->leftJoin('goods_attributes as ga', 'ga.attribute_id', '=', 'a.id')
            ->leftJoin('goods_types as gt', 'gt.id', '=', 'a.goods_type_id')
            ->where('a.user_id', $uid)
            ->where('a.goods_type_id', $id)
            ->groupBy('a.id')
            ->orderBy('a.created_at', 'desc')
            ->paginate(18);
        $view = [
            'list' => $list,
        ];
        return response()->json([
            'code' => '1000',
            'page' => $list->lastPage(),
            'data' => view($this->prefix . '.goods.attr', $view)->render()
        ]);
    }

    // 商品-编辑商品处理-添加属性页面@ajax
    public function addAttr($id = 0)
    {
        $uid = $this->store->id;
        $type = GoodsType::getList($uid);
        $view = [
            'pid' => $id,
            'type' => $type
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.attrAdd', $view)->render()
        ]);
    }

    // 商品-编辑商品处理-添加属性处理@ajax
    public function createAttr(Request $request)
    {
        $uid = $this->store->id;
        $this->validate($request, [
            'name' => 'required',
            'goods_type_id' => 'required',
            'input_type' => 'required'
        ]);
        $allow = [
            'goods_type_id', 'name', 'value', 'input_type'
        ];
        $data = $request->only($allow);
        if ($data['input_type'] == 'list') {
            if (empty($data['value'])) {
                return response()->json(['code' => '1007', 'msg' => '可选值列表不能为空']);
            }
            $data['value'] = trim($data['value']);
        } else {
            $data['value'] = null;
        }
        $data['user_id'] = $uid;
        if (Attribute::create($data)) {
            return response()->json(['code' => '1000', 'msg' => '新增成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '新增失败']);
        }
    }

    // 商品-编辑商品处理-编辑属性页面@ajax
    public function editAttr($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = Attribute::where('id', $id)
            ->where('user_id', $uid)
            ->first();
        if (! $info) {
            return response()->json(['code' => '1008', 'msg' => '参数错误']);
        }
        //获取类型列表
        $type = GoodsType::getList($uid);
        $view = [
            'info' => $info,
            'type' => $type
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.attrEdit', $view)->render()
        ]);
    }

    // 商品-编辑商品处理-编辑属性处理@ajax
    public function updateAttr(Request $request)
    {
        $uid = $this->store->id;
        $this->validate($request, [
            'name' => 'required',
            'goods_type_id' => 'required',
            'input_type' => 'required',
            'id' => 'required'
        ]);
        $allow = [
            'goods_type_id', 'name', 'value', 'input_type'
        ];
        $data = $request->only($allow);
        $id = $request->get('id');
        if ($data['input_type'] == 'list') {
            if (empty($data['value'])) {
                return response()->json(['code' => '1007', 'msg' => '可选值列表不能为空']);
            }
            $data['value'] = trim($data['value']);
        } else {
            $data['value'] = null;
        }
        $ret = Attribute::where('id', $id)->where('user_id', $uid)->update($data);
        if ($ret) {
            return response()->json(['code' => '1000', 'msg' => '编辑成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '编辑失败']);
        }
    }

    // 商品-编辑商品处理-删除属性处理@ajax
    public function delAttr($id = 0)
    {
        $uid = $this->store->id;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $ret = Attribute::where('id', $id)->where('user_id', $uid)->delete($id);
        if ($ret) {
            return response()->json(['code' => '1000', 'msg' => '移除成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '移除失败']);
        }
    }

    // 商品-编辑商品处理-获取商品属性@ajax
    public function listAttr(Request $request)
    {
        $id = $request->get('id');
        $goods_id = $request->get('goods_id');
        if ($id <= 0 || $goods_id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $data = [
            'id' => $id,
            'goods_id' => $goods_id
        ];
        return response()->json(['code' => '1000', 'data' => $this->goodsAttr($data)]);
    }

    // 商品-获取商品属性
    protected function goodsAttr($param = [])
    {
        $uid = $this->store->id;
        $tmp = Attribute::where('goods_type_id', $param['id'])
            ->where('user_id', $uid)
            ->get();
        if (count($tmp)) {
            $data = [
                'list' => [],
                'manual' => [],
                'table' => [],
                'price' => []
            ];
            $stock = [];
            $goods_attr = GoodsAttribute::where('goods_id', $param['goods_id'])
                ->where('user_id', $uid)->get();
            foreach ($tmp as $v) {
                if ($v->input_type == 'list') {// 规格
                    $list = [
                        'id' => $v->id,
                        'name' => $v->name,
                        'value' => explode(',', str_replace("\r\n", ',', $v->value))
                    ];
                    foreach ($list['value'] as $key => &$value) {
                        $value = [
                            'attr_id' => $v->id,
                            'name' => $value,
                            'checked' => false,
                            'goods_attr_id' => 0,
                            'price' => '0.00'
                        ];
                        foreach ($goods_attr as $val) {
                            if ($val->attribute_id == $value['attr_id']
                                && $val->attr_value == $value['name']) {
                                $value['checked'] = true;
                                $value['goods_attr_id'] = $val->id;
                                $value['price'] = $val->attr_price;
                                $data['price'][] = [
                                    'goods_attr_id' => $val->id,
                                    'attr_id' => $value['attr_id'],
                                    'attr_value' => $val->attr_value,
                                    'attr_price' => $val->attr_price,
                                    'index' => $value['attr_id'] . '_' . $key
                                ];
                                $data['table'][$value['attr_id']][] = [
                                    'val' => $val->attr_value,
                                    'attr_id' => $value['attr_id'],
                                    'goods_attr_id' => $val->id
                                ];
                                $stock[$value['attr_id']][] = $val->id;
                            }
                        }
                    }
                    $data['list'][] = $list;
                } else {// 属性
                    $manual = [
                        'attr_id' => $v->id,
                        'name' => $v->name,
                        'value' => null,
                        'goods_attr_id' => 0
                    ];
                    foreach ($goods_attr as $val) {
                        if ($val->attribute_id == $manual['attr_id']) {
                            $manual['value'] = $val->attr_value;
                            $manual['goods_attr_id'] = $val->id;
                        }
                    }
                    $data['manual'][] = $manual;
                }
            }
            // 属性库存表
            if (count($data['table'])) {
                $ret = $this->makeAttrTable($data['table'], $uid, $stock, $param['goods_id']);
                $data['table'] = [
                    'head' => $ret['head'],
                    'body' => $ret['body'],
                    'stock' => $ret['stock'],
                    'list' => $ret['list']
                ];
            }
            $view  = [
                'data' => $data
            ];
            $blade = view($this->prefix . '.goods.attrGoods', $view)->render();
        } else {
            $blade = view($this->prefix . '.goods.attrGoodsEmpty')->render();
        }
        return $blade;
    }

    // 商品-编辑商品处理-获取商品属性库存表@ajax
    public function tableAttr(Request $request)
    {
        $uid = $this->store->id;
        $data = $request->get('data', []);
        $goods_id = $request->get('goods_id', 0);
        $table = $body = $stock = [];
        foreach ($data as $v) {
            $table[$v['attr_id']][] = $v;
            $stock[$v['attr_id']][] = $v['goods_attr_id'];
        }
        $ret = $this->makeAttrTable($table, $uid, $stock, $goods_id);
        $view = [
            'head' => $ret['head'],
            'body' => $ret['body'],
            'stock' => $ret['stock'],
            'list' => $ret['list']
        ];
        return response()->json([
            'code' => '1000',
            'data' => view($this->prefix . '.goods.attrTable', $view)->render()
        ]);
    }

    // 商品-生成表格数据
    protected function makeAttrTable($table, $uid, $stock, $goods_id)
    {
        ksort($table);
        ksort($stock);
        $key = array_keys($table);
        $head = Attribute::select('id', 'name')
            ->whereIn('id', $key)
            ->where('user_id', $uid)
            ->where('input_type', 'list')
            ->get();
        $body = combination(array_values($table));
        $stock = combination(array_values($stock));
        foreach ($stock as &$v) {
            $v = implode(',', $v);
        }
        $list = GoodsStock::where('goods_id', $goods_id)
            ->whereIn('goods_attr_id', $stock)
            ->lists('goods_number', 'goods_attr_id')
            ->toArray();
        return ['head' => $head, 'body' => $body, 'stock' => $stock, 'list' => $list];
    }

    // 商品-分销商城
    public function distributionGoods()
    {
        //数据赋值
        $view = [];
        $this->theme->setTitle('分销商城-商品');
        return $this->theme->scope($this->prefix . '.goods.distribution', $view)->render();
    }
}
