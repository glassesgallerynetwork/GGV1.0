<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace app\home\controller; 
use app\common\logic\FreightLogic;
use app\common\logic\SearchWordLogic;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Cookie;
class Custom extends Base {

	//渐进镜片
    public function progressive(){
    	return $this->fetch();
    }

    //高品质镜头
    public function highqudlity(){
    	return $this->fetch();
    }

    //买一送一
    public function  onebuyone(){
    	return $this->fetch();
    }

    //所有优惠
    public function  deals(){
    	return $this->fetch();
    }

    // 自由过渡镜头
    public function transition(){
    	return $this->fetch();
    }

    //关于我们
    public function about(){
    	return $this->fetch();
    }

    // 为什么在网上买眼镜
    public function whybuyglassesonline(){
    	return $this->fetch();
    }

    //联盟计划
    public function affiliate(){
    	return $this->fetch();
    }

    // 常见问题
   	public function faq(){
    	return $this->fetch();
    }
    // FDA和CE Mark
   	public function mark(){
    	return $this->fetch();
    }
}

