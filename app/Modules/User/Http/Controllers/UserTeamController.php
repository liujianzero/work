<?php
/**
 * Created by PhpStorm.
 * User: phpEr校长
 * Date: 2017/8/18
 * Time: 10:08
 */

namespace App\Modules\User\Http\Controllers;


use App\Http\Controllers\UserCenterController;
use App\Modules\User\Http\Requests\PasswordRequest;
use App\Modules\User\Model\ModelsContentModel;
use App\Modules\User\Model\ModelsFolderModel;
use App\Modules\User\Model\TeamPowerModel;
use App\Modules\User\Model\TeamUserModel;
use App\Modules\User\Model\UserModel;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class UserTeamController extends UserCenterController
{
    /**
     * @return mixed
     */
    public function getTeam(){
        $this->initTheme('userteam');
        $this->theme->setTitle('我的战队');
        $teamData = TeamUserModel::where('uid',$this->user['id'])->get();
        if(count($teamData) == 0){
            $data = [
                'status' => 0,
            ];
        }else{
            $data = [
                'status' => 1,
                'data'   => $teamData,
            ];
        }
        return $this->theme->scope('user.team.index',$data)->render();
    }

    /**
     * get设置账号权限
     * @return mixed
     */
    public function getTeamPower(){
        $this->initTheme('userteam');
        $this->theme->setTitle('战队权限管理');

        $teamData = TeamUserModel::where('uid',$this->user['id'])->get();
        $powerData  = TeamPowerModel::getTeamPowerDataForType(1);
        $folderList = ModelsFolderModel::select ( 'id', 'name')->where ( 'uid', '=', $this->user ['id'] )->orderBy ( 'create_time')->get ();
        $funcData   = TeamPowerModel::getTeamPowerDataForType(2);
        $view = [
            'name'    => $teamData,
            'inData'  => $powerData,
            'file'    => $folderList,
            'funData' => $funcData,
        ];
        return $this->theme->scope('user.team.power',$view)->render();
    }

    /**
     * post设置账号权限
     * @return bool
     *
     */
    public function postTeamPower(){
        if(empty($_POST['nameId'])){
            return false;
        }
        $data = TeamPowerModel::get();
        foreach($data as $v){
            if(!empty($v['team_id'])){
                $new = str_replace($_POST['nameId'],'',$v['team_id']);
                $news = implode(',',array_filter(explode(',',$new )));
                TeamPowerModel::where('id',$v['id'])->update(['team_id' => $news]);
            }
        }
        if(!empty($_POST['urlId'])){
            foreach(explode(',',$_POST['urlId'] ) as $v){
                $team = TeamPowerModel::where('id',$v)->value('team_id'); //传过来的权限
                if(!empty($team)){ //存在
                    $str = $team.','.$_POST['nameId'];
                    $strs = implode(',',array_unique(explode(',',$str)));
                    TeamPowerModel::where('id',$v)->update(['team_id' => $strs]);
                }else{
                    TeamPowerModel::where('id',$v)->update(['team_id' => $_POST['nameId']]);
                }
            }
        }
        if(!empty($_POST['funcId'])){
            foreach(explode(',',$_POST['funcId'] ) as $v){
                $team = TeamPowerModel::where('id',$v)->value('team_id'); //传过来的权限
                if(!empty($team)){ //存在
                    $str = $team.','.$_POST['nameId'];
                    $strs = implode(',',array_unique(explode(',',$str)));
                    TeamPowerModel::where('id',$v)->update(['team_id' => $strs]);
                }else{
                    TeamPowerModel::where('id',$v)->update(['team_id' => $_POST['nameId']]);
                }
            }
        }

        $fileData = ModelsFolderModel::where('uid',$this->user['id'])->get();
        foreach($fileData as $v){
            if(!empty($v['team_id'])){
                $new = str_replace($_POST['nameId'],'',$v['team_id']);
                $news = implode(',',array_filter(explode(',',$new )));
                ModelsFolderModel::where(['id' => $v['id'],'uid' => $this->user['id']])->update(['team_id' => $news]);
            }
        }

        if(!empty($_POST['fileId'])){ //124,130
            foreach(explode(',',$_POST['fileId'] ) as $v){
                $team = ModelsFolderModel::where(['id' => $v,'uid' => $this->user['id']])->value('team_id');
                if(!empty($team)){ //存在
                    $str = $team.','.$_POST['nameId'];
                    $strs = implode(',',array_unique(explode(',',$str)));
                    ModelsFolderModel::where(['id' => $v,'uid' => $this->user['id']])->update(['team_id' => $strs]);
                }else{
                    ModelsFolderModel::where(['id' => $v,'uid' => $this->user['id']])->update(['team_id' => $_POST['nameId']]);
                }
            }
        }

    }

    /**
     * AJAX获取子账号权限
     * @return array
     *
     */
    public function ajaxTeamPower(){
        $powerData = TeamPowerModel::getTeamPowerDataForType(1);
        foreach($powerData as $v){
            if( in_array($_POST['id'], explode(',',$v['team_id']) ) ){
                $outData[] = [
                    'id' => $v['id'],
                    'title' => $v['title']
                ];
            }else{
                $inData[] = array(
                    'id' => $v['id'],
                    'title' => $v['title']
                );
            }
        }
        $folderData = ModelsFolderModel::select('id','name','team_id')->where( 'uid', '=', $this->user ['id'] )->orderBy ( 'create_time')->get ();
        foreach($folderData as $v){
            if( in_array($_POST['id'], explode(',',$v['team_id']) ) ){
                $outfile[] = [
                    'id' => $v['id'],
                    'title' => $v['name']
                ];
            }else{
                $infile[] = array(
                    'id' => $v['id'],
                    'title' => $v['name']
                );
            }
        }

        $funData = TeamPowerModel::getTeamPowerDataForType(2);
        foreach($funData as $v){
            if( in_array($_POST['id'], explode(',',$v['team_id']) ) ){
                $outFun[] = [
                    'id' => $v['id'],
                    'title' => $v['title']
                ];
            }else{
                $inFun[] = array(
                    'id' => $v['id'],
                    'title' => $v['title']
                );
            }
        }
        $view = [
            'inData'  => !empty($inData) ? $inData : null,
            'outData' => !empty($outData) ? $outData : null,
            'outfile' => !empty($outfile) ? $outfile : null,
            'infile'  => !empty($infile) ? $infile : null,
            'outFun'  => !empty($outFun)  ? $outFun  : null,
            'inFun'   => !empty($inFun)   ? $inFun   : null,
        ];

        return $view;
    }

    /**
     * 修改密码
     * @get
     * @param $id
     * @return mixed
     */
    public function getModifyPwd($id){
        $this->initTheme('userteam');
        $this->theme->setTitle('修改子账号密码');
        $data = TeamUserModel::find($id);
        $view = [
            'name' => $data['username'],
            'id'   => $id
        ];
        return $this->theme->scope('user.team.modifyPwd',$view)->render();
    }

    /**
     * 修改密码
     * @post
     * @param PasswordRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postModifyPwd(PasswordRequest $request){
        // 验证用户的密码
        $data = $request->except('_token');
        $teamData = TeamUserModel::find( intval($data['id']) );
        // 验证原密码是否正确
        if (!TeamUserModel::checkPassword( intval($data['id']) , $data['oldpassword'] )) {
            return redirect()->back()->with('error', '原始密码错误！');
        }
        $result = TeamUserModel::psChange( $data , $teamData);
        if (!$result) {
            return redirect()->back()->with('error' , '密码修改失败！'); // 回传错误信息
        }
        return redirect('/user/team')->with('error', '密码修改成功！');
    }


    /**
     * @get
     * 开通协同子账号
     * @return mixed
     */
    public function openTeam(){
        $this->initTheme('userteam');
        $this->theme->setTitle('开通团队账号');

        return $this->theme->scope('user.team.openTeam')->render();
    }

    /**
     * @post
     * 开通协同子账号并且创建文件夹
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function userGetTeam(){
        $salt = \CommonClass::random(4);
        $data = [
            'uid'        => $_POST['uid'],
            'status'     => 1,
            'username'   => $_POST['username'],
            'password'   => UserModel::encryptPassword($_POST['password'],$salt),
            'created_at' => date('Y-m-d H:i:s'),
            'salt'       => $salt,
        ];
        $teamData = TeamUserModel::insertGetId($data);
        $fData = array (
            'uid'         => $_POST['uid'],
            'name'        => $_POST['username'],
            'team_id'     => $teamData,
            'create_time' => time ()
        );
        $folderData = ModelsFolderModel::insertGetId($fData);

        if ($teamData > 0 && $folderData > 0) {
            return redirect('/user/team');
        } else {
            return redirect()->back()->withInput()->withErrors('开通失败！');
        }
    }


    /**
     * @post
     * 获取子账号用户名是否已经被占用
     * @return array
     */
    public function userTeam(){
        $username = $_POST['username'];
        $status = TeamUserModel::findUser($username);
        if($status){
            $sta = 'yes';
            $msg = '用户名可以使用';
        } else {
            $sta = 'no';
            $msg = '用户名已被占用';
        }
        $data = array(
            'msg' => $msg,
            'sta' => $sta
        );
        return $data;
    }


    /**
     * @post
     * 子账号禁用、启用
     * @return array
     */
    public function is_disabled(){
        $status = TeamUserModel::TeamUpdate($_POST['id'],$_POST['status']);
        return $status;
    }


    /**
     * @post
     * 删除账号和文件夹（如果文件夹下有作品不删除）
     * @return array
     */
    public function is_del(){
        $username = TeamUserModel::where('id',intval($_POST['id']))->value('username');
        $folderId = ModelsFolderModel::where(['uid' => $this->user['id'] , 'name' => $username ])->value('id');
        if($folderId == null){
            $teamData = TeamUserModel::where('id',intval($_POST['id']))->delete();
            if($teamData !=0){
                $data['sta'] = 'yes';
                $data['msg'] = '账号删除成功';
            }else{
                $data['sta'] = 'no';
                $data['msg'] = '账号删除失败';
            }
            return $data;
        }
        $countFolder = ModelsContentModel::where(['uid' => $this->user['id'] , 'folder_id' => intval($folderId) ])->count();
        if($countFolder > 0){
            $sta = 'no';
            $msg = '该账号下还有作品，不可删除';
        }else{
            $folderData = ModelsFolderModel::where(['uid' => $this->user['id'] , 'name' => $username ])->delete();
            $teamData = TeamUserModel::where('id',intval($_POST['id']))->delete();
            if($folderData != 0 && $teamData !=0){
                $sta = 'yes';
                $msg = '账号删除成功';
            }else{
                $sta = 'no';
                $msg = '账号删除失败';
            }
        }
        $data = array(
            'msg' => $msg,
            'sta' => $sta
        );
        return $data;
    }

}