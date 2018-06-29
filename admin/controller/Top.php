<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 当燃
 * 拼团控制器
 * Date: 2016-06-09
 */

namespace app\admin\controller;


use app\common\model\TeamActivity;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use think\AjaxPage;
use think\Loader;
use think\Db;
use think\Page;

class Top extends Base
{
	//把数据导入到后台显示 
	public function index(){
		$res = Db::name("head_nav")->select();
		return $this->fetch('',['res'=>$res]);
	}

	//跳到添加页面
	public function add(){
		return $this->fetch();
	}

	//添加顶部导航
	public function addnav(){
		$arr = array();
		$arr['name'] =I('title');
		$arr['link'] =I('htm');
		$arr['status'] = '0';
		$model = M('head_nav');
		$res=$model->add($arr);
		if($res){
			$this->success('添加成功',U('Top/index'));exit;
		}else{
			$this->error('数据插入失败,请联系管理员！');
		}
	}	

	//回填数据
	public function update(){
		$request=request();
		$id=$request->param('id');
		$model =M('head_nav');
		$res=$model->where('id',$id)->find();
		if($res){
			//赋值给分页
			return $this->fetch('',['res'=>$res]);
		}else{
			return $this->error('操作失败,请联系管理员!');
		}
	}

	//执行修改
	public function doupdate(){
		$request=request();
		$content['name']=$request->param('name');
		$content['link']=$request->param('link');
		$id=$request->param('id');
		$row =Db::name('head_nav')->where('id',$id)->update($content);
		if(!$row){
                $this->success('没有更新数据',U('Admin/Top/update',array('id'=>$id)));
            }else{
                $this->success('操作成功',U('Admin/Top/index'));
            }
            exit;
	}

	//删除
	public function del(){
		$request=request();
		$id=$request->param('id');
		$res = Db::name('head_nav')->where('id',$id)->delete();
		if($res){
			return $this->fetch('Top/index');
		}
	}

}
