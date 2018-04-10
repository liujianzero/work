<?php

namespace App\Modules\Agent\Http\Controllers\Business;

use App\Modules\Agent\Http\Controllers\AdminController;
use App\Modules\Agent\Model\EditSetUpModel;
use App\Modules\Task\Model\TaskCateModel;
use App\Modules\User\Model\AttachmentModel;
use App\Modules\User\Model\StoreConfig;
use App\Modules\User\Model\UserDetailModel;
use Illuminate\Http\Request;
use Auth;

class SetupController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->theme->set('menu_active', 'setup');
    }

    /**
     * 设置
     */
    public function index(Request $request)
    {
        $info = EditSetUpModel::where('store_id', $this->store->id)->first();

        $default = StoreConfig::where('store_id', $this->store->id)->first();//获取创建时的店铺信息
        $category = TaskCateModel::where('id', $default['major_business'])->first();//获取创建时的店铺的主营商品
        if (is_null($info)) {
            EditSetUpModel::create([
                'store_id'   => $this->store->id,
                'name'       => $default['store_name'],
//                'category'   => $category['name'],
                'created_at' => $default['created_at'],
            ]);
        }

        //数据赋值
        $view = [
            'info' => $info,
            'type' => $category['name'],
            'store_status'  => $info['store_status'],
            'mobile_status' => $info['mobile_status'],
            'default_name'  => $default['store_name'],
            'default_created_at' => $default['created_at'],
            'logo' => $info['logo'],
        ];
        $this->theme->setTitle('设置');
        return $this->theme->scope($this->prefix . '.setup.index', $view)->render();
    }

    /**
     * 设置-保存设置信息@ajax
     */
    public function edit(Request $request)
    {
        $data = $request->except(['_token']);
        $data['store_id'] = $this->store->id;
        $result = EditSetUpModel::where('store_id', $this->store->id)->first();
        if ($result) {
            EditSetUpModel::where('store_id', $this->store->id)->update($data);
        } else {
            EditSetUpModel::create($data);
            /*$post = EditSetUpModel::create($data);
            $post->store_id = $this->store->id;
            $post->save();*/
        }
        return response()->json(['code' => '1000', 'msg' => '保存成功']);
        /*if ($result) {
            return response()->json(['code' => '1000', 'msg' => '保存成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '保存失败']);
        }*/
    }

    /**
     * 设置-保存店名@ajax
     */
    public function update(Request $request)
    {
        $data = $request->except('_token');
        $data['store_id'] = $this->store->id;
        $name = $data['store_name'];
        $result = EditSetUpModel::where('store_id',$this->store->id)->first();
        if ($result) {
            UserDetailModel::where('uid',$this->store->id)->update(['nickname' => $name]);
            StoreConfig::where('store_id',$this->store->id)->update(['store_name' => $name]);
            EditSetUpModel::where('store_id',$this->store->id)->update(['name' => $name]);
            return response()->json(['code' => '1000', 'msg' => '修改成功']);
        } else {
            return response()->json(['code' => '1004', 'msg' => '修改失败']);
        }
    }

    /**
     * 上传LOGO
     */
    public function webUpload(Request $request)
    {
        $data = $request->file('file');
//        dd($this->store->id);

        //处理上传图片
        $result = \FileClass::uploadFile ($data, $path = 'user');
        $result = json_decode ($result, true);
//        dd(Auth::user ()['id']);exit;
        //判断文件是否上传
        if ($result['code'] != 200) {
            return response ()->json ( [ 'code' => 0, 'message' => $result ['message'] ] );
        }
        $attachment_data = array_add ($result ['data'], 'status', 1);
        $attachment_data ['created_at'] = date ('Y-m-d H:i:s', time());
        $attachment_data ['user_id'] = $this->store->id;
        // 将记录写入到attchement表中
        $result2 = AttachmentModel::create ($attachment_data);
        $result3 = EditSetUpModel::where('store_id',$this->store->id)->update(['logo' => $attachment_data ['url']]);
        if (! $result2)
            return response()->json(['code' => 0, 'message' => $result['message']]);
        if (! $result3)
            return response()->json(['code' => 0, 'message' => $result['message']]);
        // 删除原来的头像
        $avatar = \CommonClass::getAvatar ($this->store->id);
//        $avatar = \CommonClass::getAvatar ($this->user ['id']);
        if (file_exists ($avatar)) {
            $file_delete = unlink ($avatar);
            if ($file_delete) {
                AttachmentModel::where ( 'url', $avatar )->delete ();
            } else {
                AttachmentModel::where ( 'url', $avatar )->update ( [
                    'status' => 0
                ] );
            }
        }
        // 修改用户头像
        $data = [
            'avatar' => $result ['data'] ['url']
        ];
        $result3 = UserDetailModel::updateData ( $data, $this->store->id );
//        $result3 = UserDetailModel::updateData ( $data, $this->user ['id'] );
        if (! $result3) {
            return \CommonClass::formatResponse ( '文件上传失败' );
        }

        return response ()->json ( $result );
    }

}
