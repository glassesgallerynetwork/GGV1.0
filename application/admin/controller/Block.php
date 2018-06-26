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
 * Author: 聂晓克      
 * Date: 2017-12-14
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\common\logic\GoodsActivityLogic;
use app\common\logic\ActivityLogic;

use think\Db;

class Block extends Base{

	public function index(){
        header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");  
	}

	//自定义页面列表页
	public function pageList(){
            header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");    
	}

	public function ajaxGoodsList(){
            header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
    }

    //商品列表板块参数设置
    public function goods_list_block(){
        header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
    }

	/*
	*保存编辑完成后的信息
	*/
	public function add_data(){
            header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
	}

	//设置首页
	public function set_index(){
            header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
	}

	//删除页面
	public function delete(){
		$id=I('post.id');
		if($id){
			$r = D('mobile_template')->where('id', $id)->delete();
    		exit(json_encode(1));
		}
	}

	
	//获取秒杀活动数据
	public function get_flash(){
            header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
	}

}
?>