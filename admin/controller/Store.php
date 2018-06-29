<?php
namespace app\admin\controller;
use think\Verify;
use think\Db;
class Store extends Base
{
	public function index(){
		$all = Db::name('store')->select();
		$this->assign('data',$all);
		return $this->fetch();
	}

	public function add(){
		return $this->fetch();
	}

	public function city(){
		$request=request();
		$id=$request->param('upid');
		$res=Db::name('region2')->where('parent_id',$id)->select();
		return json_encode($res);

	}

	public function doadd(){
		$data['store_name']=I('store_name');
		$data['mail']=I('store_mail');
		$data['store_ip']=I('store_ip');
		$data['province']=I('province');
		$data['address']=I('address');
		$results=Db::name('store')->add($data);
		if($results==1){
			echo '插入成功';
		}
	}

	public function del(){
		$id=I('id');
		$del=Db::name('store')->where('id',$id)->delete();
		if($del==1){
			echo '1';
		}else{
			echo '删除失败';
		}
	}

	public function update(){
		$request=request();
		$id=$request->param('id');
		$res=Db::name('store')->where('id',$id)->find();
		$this->assign('res',$res);
		return $this->fetch();
	}

}

?>