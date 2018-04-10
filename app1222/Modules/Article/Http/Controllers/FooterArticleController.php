<?php
namespace App\Modules\Article\Http\Controllers;

use App\Http\Controllers\IndexController;
use App\Http\Requests;
use App\Modules\Article\Model\ArticleModel;
use App\Modules\Manage\Model\ArticleCategoryModel;
use Illuminate\Http\Request;

class FooterArticleController extends IndexController
{
	public function __construct()
    {
        parent::__construct();

        $this->initTheme('main');
    }

    
    public function aboutUs(Request $request,$catID)
    {
        
        $category = ArticleCategoryModel::where('pid',3)->orderBy('id','ASC')->paginate(8)->toArray();
        $cate = ArticleCategoryModel::where('id',$catID)->first()->toArray();
        $catIDs = array();
        $thirdCatIds = array();
        
        $childrenCate = ArticleCategoryModel::where('pid',$cate['id'])->get()->toArray();
        if(empty($childrenCate)) {
            $childrenCate = array();
        } else {
            if(!empty($childrenCate) && is_array($childrenCate)) {
                foreach($childrenCate as $k => $v){
                    
                    $catIDs[] = $v['id'];
                    $secCate = ArticleCategoryModel::where('pid',$v['id'])->get()->toArray();
                    if(!empty($secCate) && is_array($secCate)) {
                        foreach($secCate as $key => $val){
                            $thirdCatIds[] = $val['id'];
                        }
                    }
                    $childrenCate[$k]['children'] = $secCate;
                }
            }
        }
        
        $firArticle = ArticleModel::where('cat_id',$catID)->first();
        $article = '';
        if(empty($firArticle)){
            
            $secArticle = ArticleModel::whereIn('cat_id',$catIDs)->get()->toArray();
            if(empty($secArticle)) {
                $thirdArticle = ArticleModel::whereIn('cat_id',$thirdCatIds)->get()->toArray();
                if(!empty($thirdArticle) && is_array($thirdArticle)){
                    $article = $thirdArticle[0];
                }
            } else {
                $secArticleArr =  ArticleModel::whereIn('cat_id',$catIDs)->get()->toArray();
                if(!empty($secArticleArr) && is_array($secArticleArr)){
                    $article = $secArticleArr[0];
                }
            }
        } else {
            $article = $firArticle;
        }
        $data = array(
            'catID' => $catID,
            'category' => $category['data'],
            'cate' => $cate,
            'article' => $article,
            'childrenCate'=> $childrenCate
        );
        $this->theme->setTitle($cate['cate_name']);
        if($cate['cate_name'] == '关于我们'){
            $this->theme->set('keywords','关于我们,关于介绍,公司介绍');
            $this->theme->set('description','关于我们，众包威客关于我们介绍。');
        }elseif($cate['cate_name'] == '服务条款'){
            $this->theme->set('keywords','服务条款');
            $this->theme->set('description','服务条款');
        }elseif($cate['cate_name'] == '帮助中心'){
            $this->theme->set('keywords','帮助中心');
            $this->theme->set('description','帮助中心');
        }
        return $this->theme->scope('bre.footerarticle',$data)->render();
    }

    
    public function helpCenter(Request $request,$catID,$upID)
    {
        
        $category = ArticleCategoryModel::where('pid',3)->get()->toArray();
        $upIDs = array();
        if($category && is_array($category)) {
            foreach($category as $a => $b) {
                $upIDs[] = $b['id'];
            }
            if(in_array($upID,$upIDs)) {
                
                $catArr = ArticleCategoryModel::where('pid',$upID)->first();
                $upID = $catArr['id'];
            } else {
                $upID = $upID;
            }
        }
        
        $search = $request->get('search');
        $cate = ArticleCategoryModel::where('id',$catID)->first();
        $upCate = ArticleCategoryModel::where('id',$upID)->first();
        $helpID = $upCate['pid'];
        $catIDs = array();
        $thirdCatIds = array();
        
        $childrenCate = ArticleCategoryModel::where('pid',$helpID)->get()->toArray();
        if(empty($childrenCate)){
            $childrenCate = array();
        } else{
            if(!empty($childrenCate) && is_array($childrenCate)){
                foreach($childrenCate as $k => $v){
                    
                    $catIDs[] = $v['id'];
                    $secCate = ArticleCategoryModel::where('pid',$v['id'])->get()->toArray();
                    if(!empty($secCate) && is_array($secCate)){
                        foreach($secCate as $key => $val) {
                            $thirdCatIds[] = $val['id'];
                        }
                    }
                    $childrenCate[$k]['children'] = $secCate;
                }
            }
        }
        $ids = array_merge($catIDs,$thirdCatIds);
        $searchArticle = array();
        $article = array();
        if($search) {
            
            $res = ArticleCategoryModel::where('cate_name','like',"%".$search."%")->get();
            if(!empty($res)){
                foreach($res as $m => $n){
                    if(in_array($n['id'],$ids)){
                        
                        $searchArticle = ArticleModel::where('cat_id',$n['id'])->first();
                    }
                }
            }else{
                $searchArticle = array();
            }
            $data['searchArticle'] = $searchArticle;
        }else{
            
            $article = ArticleModel::where('cat_id',$catID)->first();
        }
        $data = array(
            'upID' => $upID,
            'catID' => $catID,
            'cate' => $cate,
            'article' => $article,
            'childrenCate'=> $childrenCate,
            'searchArticle' => $searchArticle,
            'search' => $search
        );
        $this->theme->setTitle($cate['cate_name']);
        $this->theme->set('keywords',$cate['cate_name'].'，帮助中心');
        $this->theme->set('description',$cate['cate_name']);
        return $this->theme->scope('bre.helpcenter',$data)->render();
    }
}

















