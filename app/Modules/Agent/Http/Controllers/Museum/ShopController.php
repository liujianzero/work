<?php

namespace App\Modules\Agent\Http\Controllers\Museum;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Http\Controllers\HelpsController;
use App\Modules\Agent\Model\Letter;
use App\Modules\Agent\Model\StorePage;
use App\Modules\Agent\Model\StorePageDetail;
use App\Modules\Agent\Model\StoreSubject;
use App\Modules\Agent\Model\StoreSubjectAnswer;
use App\Modules\Agent\Model\StoreTheme;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\StoreConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'shop');
    }

    // 店铺
    public function index()
    {
        $data = $this->store;
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $data->id)
            ->first();
        $info->module = 'agent';
        $page_detail = StorePageDetail::where('store_id', $data->id)->first();
        $view = [
            'id' => $data->id,
            'info' => $info,
            'page_detail' => $page_detail,
        ];
        $this->theme->setTitle('店铺');
        return $this->theme->scope("{$info->module}.{$info->flag}.shop.index", $view)->render();
    }

    // 店铺-选择模板
    public function selectTemplate()
    {
        $data = $this->store;
        $info = StoreConfig::from('store_configs as sc')
            ->select([
                'sc.*',
                'st.flag',
            ])
            ->leftJoin('store_types as st', 'st.id', '=', 'sc.store_type_id')
            ->where('sc.store_id', $data->id)
            ->first();
        $info->module = 'agent';
        $template = StoreTheme::from('store_themes as st')
            ->select('st.*')
            ->where('st.store_type_id', $info['store_type_id'])
            ->get();
        $view = [
            'info' => $info,
            'template' => $template,
        ];
        $this->theme->setTitle('选择模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.selectTemplate', $view)->render();
    }

    // 店铺-购买模板

    public function buyTemplate()
    {
        $view = [];
        $this->theme->setTitle('购买模板-店铺');
        return $this->theme->scope($this->prefix . '.shop.buyTemplate', $view)->render();
    }

    // 店铺自定义模板
    public function customTemplate()
    {
        $view = [
            'uid' => $this->store->id,
        ];
        $this->theme->setTitle('自定义-店铺');
        return $this->theme->scope($this->prefix . '.shop.customTemplate', $view)->render();
    }

    //
    public function receiveCustomData(Request $request)
    {
        $data = $_POST;
        return \GuzzleHttp\json_encode($data);
    }

    // 店铺-保存发布模板
    public function save(Request $request)
    {
        $data = $request->except('_token');
        $page = $update = $insert = [];
        $widgets = false;
        $time = date('Y-m-d H:i:s');
        foreach ($data['page'] as $v) {
            $tmp = [
                'store_id' => $this->store->id,
                'group' => null,
                'group_name' => null,
                'page' => $v['data_id'],
                'page_name' => trim($v['name']),
                'page_info' => json_encode($v),
                'top' => null,
                'body' => null,
                'bottom' => null,
                'created_at' => $time,
                'updated_at' => $time,
            ];
            if (count($data['widget'])) {
                $widgets = true;
                foreach ($data['widget'] as $widget) {
                    if ($widget['page'] == $v['data_id']) {
                        switch ($widget['key']) {
                            case 'top-nav':
                                $tmp['top'] = $widget;
                                break;
                            case 'bottom-nav':
                                $tmp['bottom'] = $widget;
                                break;
                            default:
                                $tmp['body'][] = $widget;
                                break;
                        }
                    }
                }
            }
            $tmp['top'] = $tmp['top'] ? json_encode($tmp['top']) : null;
            $tmp['bottom'] = $tmp['bottom'] ? json_encode($tmp['bottom']) : null;
            $tmp['body'] = $tmp['body'] ? json_encode($tmp['body']) : null;
            $page[] = $tmp;
        }
        if (!count($page) || !$widgets) {
            return redirect()->back()->with(['err' => '请自定义页面后提交保存']);
        }
        $ids = StorePage::where('store_id', $this->store->id)->lists('page', 'id')->toArray();
        if (count($ids)) {
            foreach ($page as $v) {
                if (in_array($v['page'], $ids)) {
                    $update[] = [
                        'id' => array_search($v['page'], $ids),
                        'group' => $v['group'],
                        'group_name' => $v['group_name'],
                        'page' => $v['page'],
                        'page_name' => $v['page_name'],
                        'page_info' => $v['page_info'],
                        'top' => $v['top'],
                        'body' => $v['body'],
                        'bottom' => $v['bottom'],
                        'updated_at' => $v['updated_at'],
                    ];
                } else {
                    $insert[] = $v;
                }
            }
        } else {
            $insert = $page;
        }
        if (!count($insert) && !count($update)) {
            return redirect()->back()->with(['err' => '请自定义页面后提交保存']);
        }
        try {
            $status = DB::transaction(function () use ($insert, $update) {
                if (count($insert)) {
                    StorePage::insert($insert);
                }
                if (count($update)) {
                    $ret = update_batch($update, 'store_pages');
                    DB::update($ret['sql'], $ret['bindings']);
                }
            });
            if ($status) {
                return redirect()->back()->with(['err' => '保存失败']);
            } else {
                return redirect()->back()->with(['suc' => '保存成功']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with(['err' => '保存失败']);
        }
    }

    // 店铺-获取自定义页面数据
    public function page()
    {
        $tmp = StorePage::where('store_id', $this->store->id)->get();
        if (!count($tmp)) {
            return [
                'code' => '1100',
                'msg' => '没有数据',
            ];
        }
        $data = [];
        foreach ($tmp as $v) {
            if ($v->top && $v->bottom) {
                $top_height = '42px';
                $bottom_height = '58px';
                $body_height = '420px';
                $top = json_decode($v->top);
                $bottom = json_decode($v->bottom);
            } elseif ($v->top) {
                $top_height = '42px';
                $bottom_height = '0';
                $body_height = '478px';
                $top = json_decode($v->top);
                $bottom = null;
            } elseif ($v->bottom) {
                $top_height = '0';
                $bottom_height = '58px';
                $body_height = '462px';
                $top = null;
                $bottom = json_decode($v->bottom);
            } else {
                $top_height = '0';
                $bottom_height = '0';
                $body_height = '520px';
                $top = null;
                $bottom = null;
            }
            $body = $v->body ? json_decode($v->body) : null;
            $view = [
                'top' => $top,
                'body' => $body,
                'bottom' => $bottom,
                'top_height' => $top_height,
                'bottom_height' => $bottom_height,
                'body_height' => $body_height,
            ];
            if ($top || $bottom || $body) {
                $html = view($this->prefix . '.shop.page', $view)->render();
            } else {
                $html = null;
            }
            $data[] = [
                'page' => json_decode($v->page_info),
                'html' => $html,
            ];
        }

        return [
            'code' => '1000',
            'data' => $data,
        ];
    }

    // 店铺-根据关键字获取作品
    public function getModel(Request $request)
    {
        $title = trim($request->input('title'));
        if (!$title) {
            return ['code' => '1001', 'msg' => '请输入关键字'];
        }
        $list = ModelsContentModel::where('uid', $this->store->id)
            ->where('title', 'like', "%{$title}%")
            ->latest('id')
            ->take(12)
            ->get();
        if (!count($list)) {
            return ['code' => '1100', 'msg' => '没有匹配的结果'];
        }
        return [
            'code' => '1000',
            'data' => view($this->prefix . '.shop.model', compact('list'))->render()
        ];
    }

    // 店铺-上传图片
    public function uploadImage(Request $request)
    {
        $path = ucfirst($this->module) . '/' . $this->flag . '/shop/image/';
        $image = $request->file('image');
        $ret = upload_file($image, $path);
        if ($ret['code']) {
            return ['code' => '1000', 'url' => "/{$ret['filePath']}"];
        } else {
            return ['code' => '1001', 'msg' => $ret['msg']];
        }
    }

    // 店铺-删除图片
    public function delImage(Request $request)
    {
        $image = $request->input('image');
        if ($image) {
            $reg = '/^\/Uploads\/.*/i';
            if (preg_match($reg, $image)) {
                $image = ltrim($image, '/');
                if (file_exists($image)) {
                    @unlink($image);
                }
            }
        }
    }

    // 店铺-获取店铺题目列表
    public function getSubject(Request $request)
    {
        $list = StoreSubject::where('store_id', $this->store->id)->latest()->paginate();
        return [
            'code' => 1000,
            'page' => $list->lastPage(),
            'data' => view($this->prefix . '.shop.subject', compact('list'))->render()
        ];
    }

    // 店铺-获取可以选择的题目列表
    public function subjectSelect(Request $request)
    {
        $index = $request->input('id');
        $guid = $request->input('guid');
        $list = StoreSubject::where('store_id', $this->store->id)->latest()->paginate();
        return [
            'code' => 1000,
            'page' => $list->lastPage(),
            'data' => view($this->prefix . '.shop.subjectSelect', compact('list', 'index', 'guid'))->render()
        ];
    }

    // 店铺-获取题目详情
    public function subjectShow(Request $request)
    {
        $id = $request->input('id', 0);
        $info = StoreSubject::where('store_id', $this->store->id)
            ->with('storeSubjectAnswers')
            ->where('id', $id)
            ->first();
        if (!$info) {
            return ['code' => 1100, 'msg' => '未查找到该数据'];
        }

        return [
            'code' => 1000,
            'data' => $info,
        ];
    }

    // 店铺-新增/编辑题目页面
    public function handleSubject(Request $request)
    {
        $action = $request->input('action');
        $id = $request->input('id');
        if ($action == 'edit') {
            $info = StoreSubject::where('store_id', $this->store->id)
                ->with('storeSubjectAnswers')
                ->where('id', $id)
                ->first();
            if (!$info) {
                return ['code' => 1100, 'msg' => '未查找到该数据'];
            }
        } else {
            $info = null;
        }
        $letter = Letter::getCache();

        return [
            'code' => 1000,
            'data' => view($this->prefix . '.shop.subjectAction', compact('info', 'letter', 'action'))->render()
        ];
    }

    // 店铺-新增题目
    public function subjectStore(Request $request)
    {
        $allow = [
            'title',
            'score',
            'type',
            'subject',
        ];
        $data = $request->only($allow);
        $chk = array_pull($data['subject'], 'checked');
        if (!$chk) {
            return [
                'code' => 1100,
                'msg' => '至少有一个正确答案',
            ];
        }
        $subject = [
            'store_id' => $this->store->id,
            'title' => $data['title'],
            'score' => $data['score'],
            'type' => $data['type'],
        ];
        $answer = [];
        $time = date('Y-m-d H:i:s');
        foreach ($data['subject'] as $k => $v) {
            $answer[] = [
                'store_subject_id' => 0,
                'option' => $k,
                'is_right' => in_array($k, $chk) ? 'Y' : 'N',
                'title' => $v['answer'],
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        $need = 2;
        if (count($answer) < $need) {
            return [
                'code' => 1100,
                'msg' => '至少需要 ' . $need . ' 个答案',
            ];
        }
        $status = DB::transaction(function () use ($subject, $answer) {
            $obj = StoreSubject::create($subject);
            foreach ($answer as &$v) {
                $v['store_subject_id'] = $obj->id;
            }
            StoreSubjectAnswer::insert($answer);
        });
        if ($status) {
            return [
                'code' => 1100,
                'msg' => '新增失败',
            ];
        } else {
            return [
                'code' => 1000,
                'msg' => '新增成功',
            ];
        }
    }

    // 店铺-编辑题目
    public function subjectUpdate(Request $request)
    {
        $allow = [
            'id',
            'title',
            'score',
            'type',
            'subject',
        ];
        $data = $request->only($allow);
        $has = StoreSubject::where('id', $data['id'])
            ->where('store_id', $this->store->id)
            ->first();
        if (!$has) {
            return [
                'code' => 1001,
                'msg' => '非法操作',
            ];
        }
        $chk = array_pull($data['subject'], 'checked');
        if (!$chk) {
            return [
                'code' => 1100,
                'msg' => '至少有 1 个正确答案',
            ];
        }
        $subject = [
            'title' => $data['title'],
            'score' => $data['score'],
            'type' => $data['type'],
        ];
        $answer = [];
        $time = date('Y-m-d H:i:s');
        foreach ($data['subject'] as $k => $v) {
            $answer[] = [
                'store_subject_id' => $data['id'],
                'option' => $k,
                'is_right' => in_array($k, $chk) ? 'Y' : 'N',
                'title' => $v['answer'],
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        $need = 2;
        if (count($answer) < $need) {
            return [
                'code' => 1100,
                'msg' => '至少需要 ' . $need . ' 个答案',
            ];
        }
        $id = $data['id'];
        $status = DB::transaction(function () use ($id, $subject, $answer) {
            StoreSubject::where('id', $id)->update($subject);
            StoreSubjectAnswer::where('store_subject_id', $id)->delete();
            StoreSubjectAnswer::insert($answer);
        });
        if ($status) {
            return [
                'code' => 1100,
                'msg' => '更新失败',
            ];
        } else {
            return [
                'code' => 1000,
                'msg' => '更新成功',
            ];
        }
    }

    // 店铺-删除题目
    public function subjectDestroy(Request $request)
    {
        $id = $request->input('id');
        $status = DB::transaction(function () use ($id) {
            StoreSubject::where('id', $id)->where('store_id', $this->store->id)->delete();
            StoreSubjectAnswer::where('store_subject_id', $id)->delete();
        });
        if ($status) {
            return [
                'code' => 1100,
                'msg' => '移除失败',
            ];
        } else {
            return [
                'code' => 1000,
                'msg' => '移除成功',
            ];
        }
    }

    /**
     * 页面设置-附件图片上传@ajax
     */
    public function storeUpload(Request $request)
    {
        $store_id = $this->store->id;
        $file = $request->file('file');
        $path = 'Store/file/';
        $allowed_extensions = [
            'png', 'jpg', 'jpeg', 'gif', 'bmp',
        ];
        $result = HelpsController::uploadFile($file, $path, $size = 2048, $allowed_extensions);
        if ($result['code']) {
            $create = [
                'name' => $result['filename'],
                'type' => $result['extension'],
                'size' => $result['fileSize'] / 1024,
                'url' => $result['filePath'],
                'status' => 0,
                'user_id' => $store_id,
                'disk' => 'upload',
                'created_at' => date('Y-m-d H:i:s')
            ];
            if ($result = AttachmentModel::create($create)) {
                return response()->json([
                    'code' => '1000',
                    'id' => $result->id,
                    'url' => $result->url,
                ]);
            } else {
                if (!empty($result['filePath']) && file_exists($result['filePath'])) {
                    @unlink($result['filePath']);
                }
                return response()->json(['code' => '1004', 'msg' => '文件上传失败']);
            }
        } else {
            return response()->json(['code' => '1009', 'msg' => $result['msg']]);
        }
    }

    /**
     * 页面设置-附件图片删除文件@ajax
     */
    public function storeDelFile($id = 0)
    {
        $data = $this->store;
        if ($id <= 0) {
            return response()->json(['code' => '1001', 'msg' => '非法操作']);
        }
        $info = AttachmentModel::where('id', $id)
            ->where('user_id', $data->id)
            ->first();
        if (!$info) {
            return response()->json(['code' => '1009', 'msg' => '该附件并没有成功上传']);
        }
        $status = DB::transaction(function () use ($id) {
            AttachmentModel::destroy($id);
        });
        if (!$status) {
            if (!empty($info->url) && file_exists($info->url)) {
                @unlink($info->url);
            }
            return response()->json(['code' => '1000']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '附件删除失败']);
        }
    }

    /**
     * 页面设置-表单验证
     */
    public function validateFrom(Request $request)
    {
        $this->validate($request, [
            'summary_desc' => 'required',
            'address_details' => 'required',
            'travel_tips' => 'required',
        ], [
            'summary_desc.required' => '请填写店铺概况',
            'address_details.required' => '请填写地址信息',
            'travel_tips.required' => '请填写内容',
        ]);
        $uid = $this->store->id;
        $theme = StoreConfig::where('store_id', $uid)->value('theme');
        $info = StorePageDetail::where('store_id', $uid)->first();
        $summary_img = $info['summary_img'];
        $address_img = $info['address_img'];
//        $data = $request->all();
//        dd($data);exit;
        $update['head_nav_one'] = $request->input('head_nav_one', null);
        $update['head_nav_tow'] = $request->input('head_nav_tow', null);
        $update['head_nav_three'] = $request->input('head_nav_three', null);
        $update['head_nav_four'] = $request->input('head_nav_four', null);
        if (($request->input('file_first', null)) == null) {
            $update['summary_img'] = $summary_img;
        } else {
            $update['summary_img'] = $request->input('file_first', $summary_img);
        }
        if (($request->input('file_second', null)) == null) {
            $update['address_img'] = $address_img;
        } else {
            $update['address_img'] = $request->input('file_second', $address_img);
        }
        $update['summary_desc'] = $request->input('summary_desc', null);
        $update['address_details'] = $request->input('address_details', null);
        $update['travel_tips'] = $request->input('travel_tips', null);
        $product = $request->input('product', []);
        if (!empty($product)) {
            foreach ($product as $k => $v) {
                if ($v == 1) {
                    StorePageDetail::where('store_id', $uid)->update(['distributor_status' => 1]);
                } else {
                    StorePageDetail::where('store_id', $uid)->update(['distributor_status' => 0]);
                }
                if ($v == 2) {
                    StorePageDetail::where('store_id', $uid)->update(['collect_status' => 1]);
                } else {
                    StorePageDetail::where('store_id', $uid)->update(['collect_status' => 0]);
                }
                if ($v == 3) {
                    StorePageDetail::where('store_id', $uid)->update(['cart_status' => 1]);
                } else {
                    StorePageDetail::where('store_id', $uid)->update(['cart_status' => 0]);
                }
                if ($v == 4) {
                    StorePageDetail::where('store_id', $uid)->update(['orders_status' => 1]);
                } else {
                    StorePageDetail::where('store_id', $uid)->update(['orders_status' => 0]);
                }
            }
        }
        $data = [
            'store_id' => $uid,
            'head_nav_one' => $update['head_nav_one'],
            'head_nav_tow' => $update['head_nav_tow'],
            'head_nav_three' => $update['head_nav_three'],
            'head_nav_four' => $update['head_nav_four'],
            'summary_img' => $update['summary_img'],
            'summary_desc' => $update['summary_desc'],
            'address_img' => $update['address_img'],
            'address_details' => $update['address_details'],
            'travel_tips' => $update['travel_tips'],
            'theme' => $theme,
            'created_at' => date('Y-m-d H:i:s')
        ];
        if (!$info) {
            $result = StorePageDetail::create($data);
            if ($result) {
                return back()->with(['suc' => '保存成功']);
            } else {
                return back()->with(['err' => '保存失败']);
            }
        } else {
            $result = DB::transaction(function () use ($data, $uid) {
                StorePageDetail::where('store_id', $uid)->update($data);
            });
            $result = is_null($result) ? true : false;
            if ($result) {
                return back()->with(['suc' => '保存成功']);
            } else {
                return back()->with(['err' => '保存失败']);
            }
        }
    }
}

