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
use app\common\logic\GoodsPromFactory;
use app\common\logic\SearchWordLogic;
use app\common\logic\GoodsLogic;
use app\common\model\SpecGoodsPrice;
use app\common\logic\Color;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Cookie;
class Goods extends Base {
    public function index(){      
        return $this->fetch();
    }


   /**
    * 商品详情页
    */ 
    public function goodsInfo(){

        //C('TOKEN_ON',true);        
        $goodsLogic = new GoodsLogic();
        $widget = new \app\home\widget\Cates();
        $widget->header();
        $widget->footer();
        
        $goods_id = I("get.id/d");       
        //镜片属性
        $cat=Db::name('goods')->field('cat_id')->where('goods_id',$goods_id)->select();
        $cat_id=$cat[0]['cat_id'];
        $res=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
        $result=$res[0]['name'];
        $new_array = Array();
        switch($result){
            case '眼镜':
            $name='Eyeglasses';
            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
            foreach($results as $key=>$val){
                $new_array[]=$val['lens_type'];
            }
            $arr=Array();
            foreach($new_array as $v){
                switch ($v) {
                    case 'single_vision':
                        $arr[]=array('name'=>'单一视觉','centent'=>'对于非处方','price'=>'');
                        break;

                    case 'bifocal':
                        $arr[]=array('name'=>'双光','centent'=>'独特个体的独特视角','price'=>'¥520.00');
                        break;

                    case 'progressive':
                        $arr[]=array('name'=>'进步','centent'=>'40岁以后解锁自然视力的钥匙。','price'=>'¥640.00');
                        break;
                }
            }
            break;

            case '墨镜':
            $name='Sunglasses';
            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
            foreach($results as $key=>$val){
                $new_array[]=$val['lens_type'];
            }
            $arr=Array();
            foreach($new_array as $v){
                switch($v){
                    case 'single_vision':
                        $arr[]=array('name'=>'单一视觉','centent'=>'所有年龄段的前所未有的清晰度和安全性','price'=>'');
                        break;
                    case 'progressive':
                        $arr[]=array('name'=>'进步','centent'=>'40年后你解锁自然视力的关键。','price'=>'¥870.00');
                        break;
                }
            }
            break;

            case '运动':
            $name='Sports';
            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
            foreach($results as $key=>$val){
                $new_array[]=$val['lens_type'];
            }
            $arr=Array();
            foreach($new_array as $v){
                switch ($v) {
                    case 'single_vision':
                        $arr[]=array('name'=>'单一视觉','centent'=>'所有年龄段的前所未有的清晰度和安全性','price'=>'');
                        break;
                }
            }
            break;
        }
                    //分配到前台
        $this->assign('results',$arr);
        $Goods = new \app\common\model\Goods();
        $goods = $Goods::get($goods_id);
        if(empty($goods) || ($goods['is_on_sale'] == 0) || ($goods['is_virtual']==1 && $goods['virtual_indate'] <= time())){
        	$this->error('该商品已经下架',U('Index/index'));
        }
        if (cookie('user_id')) {
            $goodsLogic->add_visit_log(cookie('user_id'), $goods);
        }
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->select(); // 商品 图册
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表
	    $filter_spec = $goodsLogic->get_spec($goods_id);
        $freight_free = tpCache('shopping.freight_free'); // 全场满多少免运费
        $spec_goods_price  = M('spec_goods_price')->where("goods_id", $goods_id)->getField("key,item_id,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id", $goods_id)->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $point_rate = tpCache('shopping.point_rate');
        $this->assign('freight_free', $freight_free);// 全场满多少免运费
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('goods_attribute',$goods_attribute);//属性值     
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        $this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看      
        $this->assign('goods',$goods);
        
        //拿到图片需要的信息转为小写  2018-6-29
        $brand_id=$goods['brand_id'];
        $result=Db::name('brand')->field('name')->where(id,$brand_id)->find();
        $brand=strtolower($result['name']);//转换为小写

        $sku=strtolower($goods['sku']);
        $images=Array('brand'=>str_replace(" ","-","$brand"),'sku'=>$sku);
        $this->assign('images',$images);

        //构建手机端URL
        $ShareLink = urlencode("http://{$_SERVER['HTTP_HOST']}/index.php?m=Mobile&c=Goods&a=goodsInfo&id={$goods['goods_id']}");
        $this->assign('ShareLink',$ShareLink);
        $this->assign('point_rate',$point_rate);

        //猜你喜欢
        //根据品牌-眼镜类型-性别来，实现
        // -- $like=Db::query("SELECT * FROM tp_goods as g, tp_brand as b where g.cat_id=$cat_id and g.brand_id=b.id LIMIT 4");
        $counts=Db::name('goods g')->join('tp_brand b','b.id=g.brand_id')->where('g.cat_id',$cat_id)->count();
        $page = new Page($counts,4);
        if($counts > 0)
        {
            $like=Db::name('goods g')->join('tp_brand b','b.id=g.brand_id')->where('g.cat_id',$cat_id)->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('page',$page);//赋值分页输出
        $this->assign('like',$like);//猜你喜欢
        return $this->fetch('goods/info');
    }

    public function activity(){
        $goods_id = input('goods_id/d');//商品id
        $item_id = input('item_id/d');//规格id
        $goods_num = input('goods_num/d');//欲购买的商品数量
        $Goods = new \app\common\model\Goods();
        $goods = $Goods::get($goods_id);
        $goodsPromFactory = new GoodsPromFactory();
        if ($goodsPromFactory->checkPromType($goods['prom_type'])) {
            //这里会自动更新商品活动状态，所以商品需要重新查询
            if($item_id){
                $specGoodsPrice = SpecGoodsPrice::get($item_id);
                $goodsPromLogic = $goodsPromFactory->makeModule($goods,$specGoodsPrice);
            }else{
                $goodsPromLogic = $goodsPromFactory->makeModule($goods,null);
            }
            if($goodsPromLogic->checkActivityIsAble()){
                $goods = $goodsPromLogic->getActivityGoodsInfo();
                $goods['activity_is_on'] = 1;
                $this->ajaxReturn(['status'=>1,'msg'=>'该商品参与活动','result'=>['goods'=>$goods]]);
            }else{
                if(!empty($goods['price_ladder'])){
                    $goodsLogic = new GoodsLogic();
                    $goods->shop_price = $goodsLogic->getGoodsPriceByLadder($goods_num, $goods['shop_price'], $goods['price_ladder']);
                }
                $goods['activity_is_on'] = 0;
                $this->ajaxReturn(['status'=>1,'msg'=>'该商品没有参与活动','result'=>['goods'=>$goods]]);
            }
        }
        if(!empty($goods['price_ladder'])){
            $goodsLogic = new GoodsLogic();
            $goods->shop_price = $goodsLogic->getGoodsPriceByLadder($goods_num, $goods['shop_price'], $goods['price_ladder']);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'该商品没有参与活动','result'=>['goods'=>$goods]]);
    }

    /**
     * 获取可发货地址
     */
    public function getRegion()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $region_list['status'] = 1;
        $this->ajaxReturn($region_list);
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){ 
        
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html = S($key);
        if(!empty($html))
        {
            return $html;
        }
        
        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d',1); // 当前分类id
        print_r($id);
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格 
        $attr = I('get.attr',''); // 属性        
        $sort = I('get.sort','sort'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱        
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
     
        $filter_param['id'] = $id; //加入帅选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price  && ($filter_param['price'] = $price); //加入帅选条件中

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        
        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id", $id)->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆        
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 帅选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
        $goods_where = ['is_on_sale' => 1, 'exchange_integral' => 0,'cat_id'=>['in',$cat_id_arr]];
        $filter_goods_id = Db::name('goods')->where($goods_where)->cache(true)->getField("goods_id",true);
        // 过滤帅选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个帅选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 帅选的价格期间         
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList'); // 获取指定分类下的帅选品牌
        $filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选规格        
        $filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选属性        

        $count = count($filter_goods_id);
        $page = new Page($count,20);
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order([$sort=>$sort_asc])->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        // print_r($filter_menu);         
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = navigate_goods($id); // 面包屑导航


        $this->assign('goods_list',$goods_list);
        $this->assign('navigate_cat',$navigate_cat);

        $this->assign('goods_category',$goods_category);                
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_spec',$filter_spec);  // 帅选规格
        $this->assign('filter_attr',$filter_attr);  // 帅选属性
        $this->assign('filter_brand',$filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出        
        $html = $this->fetch();        
        S($key,$html);
        return $html;
    }    

    /**
     *  查询配送地址，并执行回调函数
     */
    public function region()
    {
        $fid = I('fid/d');
        $callback = I('callback');
        $parent_region = M('region')->field('id,name')->where(array('parent_id'=>$fid))->cache(true)->select();
        echo $callback.'('.json_encode($parent_region).')';
        exit;
    }

    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {
        $goods_id = I('goods_id/d');//143
        $region_id = I('region_id/d');//28242
        $Goods = new \app\common\model\Goods();
        $goods = $Goods->cache(true)->where('goods_id',$goods_id)->find();
        $freightLogic = new FreightLogic();
        $freightLogic->setGoodsModel($goods);
        $freightLogic->setRegionId($region_id);
        $freightLogic->setGoodsNum(1);
        $isShipping = $freightLogic->checkShipping();
        if($isShipping){
            $freightLogic->doCalculation();
            $freight = $freightLogic->getFreight();
            $dispatching_data = ['status'=>1,'msg'=>'可配送','result'=>['freight'=>$freight]];
        }else{
            $dispatching_data = ['status'=>0,'msg'=>'该地区不支持配送','result'=>''];
        }
        $this->ajaxReturn($dispatching_data);
    }
    /**
     * 商品搜索列表页
     */
    public function search()
    {
        //C('URL_MODEL',0);
        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d', 0); // 当前分类id
        $brand_id = I('brand_id', 0);
        $sort = I('sort', 'goods_id'); // 排序
        $sort_asc = I('sort_asc', 'asc'); // 排序
        $price = I('price', ''); // 价钱
        $start_price = trim(I('start_price', '0')); // 输入框价钱
        $end_price = trim(I('end_price', '0')); // 输入框价钱
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱
        $q = urldecode(trim(I('q', ''))); // 关键字搜索
        empty($q) && $this->error('请输入搜索词');
        $id && ($filter_param['id'] = $id); //加入帅选条件中                       
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $price && ($filter_param['price'] = $price); //加入帅选条件中
        $q && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        $SearchWordLogic = new SearchWordLogic();
        $where = $SearchWordLogic->getSearchWordWhere($q);
        $where['is_on_sale'] = 1;
        $where['exchange_integral'] = 0;//不检索积分商品
        Db::name('search_word')->where('keywords', $q)->setInc('search_num');
        $goodsHaveSearchWord = Db::name('goods')->where($where)->count();
        if ($goodsHaveSearchWord) {
            $SearchWordIsHave = Db::name('search_word')->where('keywords',$q)->find();
            if($SearchWordIsHave){
                Db::name('search_word')->where('id',$SearchWordIsHave['id'])->update(['goods_num'=>$goodsHaveSearchWord]);
            }else{
                $SearchWordData = [
                    'keywords' => $q,
                    'pinyin_full' => $SearchWordLogic->getPinyinFull($q),
                    'pinyin_simple' => $SearchWordLogic->getPinyinSimple($q),
                    'search_num' => 1,
                    'goods_num' => $goodsHaveSearchWord
                ];
                Db::name('search_word')->insert($SearchWordData);
            }
        }
        if ($id) {
            $cat_id_arr = getCatGrandson($id);
            $where['cat_id'] = array('in', implode(',', $cat_id_arr));
        }
        $search_goods = M('goods')->where($where)->getField('goods_id,cat_id');
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if ($filter_cat_id) {
            $cateArr = M('goods_category')->where("id", "in", implode(',', $filter_cat_id))->select();
            $tmp = $filter_param;
            foreach ($cateArr as $k => $v) {
                $tmp['id'] = $v['id'];
                $cateArr[$k]['href'] = U("/Home/Goods/search", $tmp);
            }
        }
        // 过滤帅选的结果集里面找商品        
        if ($brand_id || $price) {
            // 品牌或者价格
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'search'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id, $filter_param, 'search'); // 帅选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id, $filter_param, 'search'); // 获取指定分类下的帅选品牌

        $count = count($filter_goods_id);
        $page = new Page($count, 20);
        if ($count > 0) {
            $goods_list = M('goods')->where(['is_on_sale' => 1, 'goods_id' => ['in', implode(',', $filter_goods_id)]])->order([$sort=>$sort_asc])->limit($page->firstRow . ',' . $page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if ($filter_goods_id2)
                $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->select();
        }

        $this->assign('goods_list', $goods_list);
        $this->assign('goods_images', $goods_images);  // 相册图片
        $this->assign('filter_menu', $filter_menu);  // 帅选菜单
        $this->assign('filter_brand', $filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price', $filter_price);// 帅选的价格期间
        $this->assign('cateArr', $cateArr);
        $this->assign('filter_param', $filter_param); // 帅选条件
        $this->assign('cat_id', $id);
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('q', I('q'));
        C('TOKEN_ON', false);
        return $this->fetch();
    }
    
    /**
     * 商品咨询ajax分页
     */
    public function ajax_consult(){
        $goods_id = I("goods_id/d", '0');
        $consult_type = I('consult_type', '0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后
        $where = ['parent_id' => 0, 'goods_id' => $goods_id,'is_show'=>1];
        if ($consult_type > 0) {
            $where['consult_type'] = $consult_type;
        }
        $count = M('GoodsConsult')->where($where)->count();
        $page = new AjaxPage($count, 5);
        $show = $page->show();
        $consultList = M('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow . ',' . $page->listRows)->order('add_time desc')->select();
        foreach($consultList as $key =>$list){
            $consultList[$key]['replyList'] = M('GoodsConsult')->where(['parent_id' => $list['id'],'is_show'=>1])->order('add_time desc')->select();
        }
        $this->assign('consultCount', $count);// 商品咨询数量
        $this->assign('consultList', $consultList );// 商品咨询
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }
    
    /**
     * 商品评论ajax分页
     */
    public function ajaxComment(){        
        $goods_id = I("goods_id/d",'0');        
        $commentType = I('commentType','1'); // 1 全部 2好评 3 中评 4差评
        $where = ['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>0];
        if($commentType==5){
            $where['img'] = ['<>',''];
        }else{
        	$typeArr = array('1'=>'0,1,2,3,4,5','2'=>'4,5','3'=>'3','4'=>'0,1,2');
            $where['ceil((deliver_rank + goods_rank + service_rank) / 3)'] = ['in',$typeArr[$commentType]];
        }
        $count = M('Comment')->where($where)->count();                
        
        $page = new AjaxPage($count,10);
        $show = $page->show();   
       
        $list = M('Comment')->alias('c')->join('__USERS__ u','u.user_id = c.user_id','LEFT')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();
         
//        $replyList = M('Comment')->where(['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>['>',0]])->order("add_time desc")->select();
        
        foreach($list as $k => $v){
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片
            $replyList[$v['comment_id']] = M('Comment')->where(['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>$v['comment_id']])->order("add_time desc")->select();
        }
        $this->assign('commentlist',$list);// 商品评论
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出        
        return $this->fetch();        
    }    
    
    /**
     *  商品咨询
     */
    public function goodsConsult(){
        C('TOKEN_ON', true);
        $goods_id = I("goods_id/d", '0'); // 商品id
        $store_id = I("store_id/d", '0'); // 商品id
        $consult_type = I("consult_type", '1'); // 商品咨询类型
        $username = I("username", 'TPshop用户'); // 网友咨询
        $content = trim(I("content",'')); // 咨询内容
        if(strlen($content) >500)
            $this->error("咨询内容不得超过500字符！！");
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), 'consult')) {
            $this->error("验证码错误");
        }
        $data = array(
            'goods_id' => $goods_id,
            'consult_type' => $consult_type,
            'username' => $username,
            'content' => $content,
            'store_id' => $store_id,
            'is_show' => 1,
            'add_time' => time(),
        );
        Db::name('goodsConsult')->add($data);
        $this->success('咨询已提交!', U('/Home/Goods/goodsInfo', array('id' => $goods_id)));
    }
    
    /**
     * 用户收藏商品
     */
    public function collect_goods(){
        $goods_ids = I('goods_ids/a',[]);
        if(empty($goods_ids)){
            $this->ajaxReturn(['status'=>0,'msg'=>'请至少选择一个商品','result'=>'']);
        }
        $goodsLogic = new GoodsLogic();
        $result = [];
        foreach($goods_ids as $key=>$val){
            $result[] = $goodsLogic->collect_goods(cookie('user_id'), $val);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'已添加至我的收藏','result'=>$result]);
    }
    
    /**
     * 加入购物车弹出
     */
    public function open_add_cart()
    {        
         return $this->fetch();
    }

    /**
     * 积分商城
     */
    public function integralMall()
    {
        $cat_id = I('get.id/d');
        $minValue = I('get.minValue');
        $maxValue = I('get.maxValue');
        $brandType = I('get.brandType');
        $point_rate = tpCache('shopping.point_rate');
        $is_new = I('get.is_new',0);
        $exchange = I('get.exchange',0);
        $goods_where = array(
            'is_on_sale' => 1,  //是否上架
            'is_virtual' =>0,
        );
        //积分兑换筛选
        $exchange_integral_where_array = array(array('gt',0));
        // 分类id
        if (!empty($cat_id)) {
            $goods_where['cat_id'] = array('in', getCatGrandson($cat_id));
        }
        //积分截止范围
        if (!empty($maxValue)) {
            array_push($exchange_integral_where_array, array('elt', $maxValue));
        }
        //积分起始范围
        if (!empty($minValue)) {
            array_push($exchange_integral_where_array, array('egt', $minValue));
        }
        //积分+金额
        if ($brandType == 1) {
            array_push($exchange_integral_where_array, array('exp', ' < shop_price* ' . $point_rate));
        }
        //全部积分
        if ($brandType == 2) {
            array_push($exchange_integral_where_array, array('exp', ' = shop_price* ' . $point_rate));
        }
        //新品
        if($is_new == 1){
            $goods_where['is_new'] = $is_new;
        }
        //我能兑换
        $user_id = cookie('user_id');
        if ($exchange == 1 && !empty($user_id)) {
            $user_pay_points = intval(M('users')->where(array('user_id' => $user_id))->getField('pay_points'));
            if ($user_pay_points !== false) {
                array_push($exchange_integral_where_array, array('lt', $user_pay_points));
            }
        }

        $goods_where['exchange_integral'] =  $exchange_integral_where_array;
        $goods_list_count = M('goods')->where($goods_where)->count();   //总页数
        $page = new Page($goods_list_count, 15);
        $goods_list = M('goods')->where($goods_where)->limit($page->firstRow . ',' . $page->listRows)->select();
        $goods_category = M('goods_category')->where(array('level' => 1))->select();

        $this->assign('goods_list', $goods_list);
        $this->assign('page', $page->show());
        $this->assign('goods_list_count',$goods_list_count);
        $this->assign('goods_category', $goods_category);//商品1级分类
        $this->assign('point_rate', $point_rate);//兑换率
        $this->assign('nowPage',$page->nowPage);// 当前页
        $this->assign('totalPages',$page->totalPages);//总页数
        return $this->fetch();
    }

    /**
     * 全部商品分类
     * @author lxl
     * @time17-4-18
     */
    public function all_category(){
        return $this->fetch();
    }

    /**
     * 全部品牌列表
     * @author lxl
     * @time17-4-18
     */
    public function all_brand(){
        return $this->fetch();
    }

    //首页选择品牌
    public function brand(){
       $name=I("get.name/S");
       if(Db::name('goods')->where('goods_name','like',"%{$name}%")->select()){
            $res=Db::name('goods')->field(['goods_id','goods_name','keywords','sku','market_price','shop_price'])->where('goods_name','like',"%{$name}%")->select();
       }
       if($res){
           foreach($res as $val){
                $brand_names=strtolower($val['goods_name']);
                $sku=strtolower($val['sku']);
                $length=mb_strlen($sku, 'GBK');
                $nam=substr($brand_names,0,(0-$length)-1);
                $name=str_replace(" ","-","$nam");
                $arr[]=Array('brand_names'=>$name,'sku'=>$sku,'market_price'=>$val['market_price'],'price'=>$val['shop_price'],'goods_id'=>$val['goods_id']);
           }
       }
       if(Db::name('brand')->select()){
            $result=Db::name('brand')->where('name',$name)->select();
       }
        $this->assign('result',$result);
        $this->assign('data',$arr);
       
       return $this->fetch();
    }
    
    //点击导航跳转到相应的商品
    public function list(){
        $request=request();
        $id=$request->param('id');
        $count=M('goods')->where('cat_id',$id)->count();
        $page= new AjaxPage($count,18);
        $show=$page->show();
        // $res=Db::name('goods')->field('goods_id,goods_name,shop_price,original_img,market_price')->where('cat_id',$id)->limit($page->firstRow . ',' . $page->listRows)->order('sort')->select();
        // foreach($res as $key=>$val){
        // $ids=$val['goods_id'];
        // M('goods_images')->alias('i')->field('i.image_url')->join('goods g','i.goods_id = g.goods_id','LEFT')->where('i.goods_id',$ids)->select(); 
        // }
        $bann=Db::name('banner')->where('cat_id',$id)->find();
        $res=Db::query("SELECT t.brand_id, t.sku, t.goods_id, t.goods_name,t.shop_price,t.market_price FROM tp_goods as t,tp_goods_category as c where c.id=t.cat_id and c.id={$id}");
        foreach($res as $val){
            $brand_id=$val['brand_id'];
            $result=Db::name('brand')->field('name')->where(id,$brand_id)->find();
            $brand=strtolower($result['name']);//转换为小写
            $sku=strtolower($val['sku']);
            $goods_id=$val['goods_id'];
            $shop_price=$val['shop_price'];
            $market_price=$val['market_price'];
             $data[]=Array('brand'=>$brand,'sku'=>$sku,'goods_id'=>$goods_id,'shop_price'=>$shop_price,'market_price'=>$market_price);
            
        }
        $count = M("goods")->where("cat_id = ".$id."")->count(); //分页
        $navigate_cat = navigate_goods($id); // 面包屑导航
        $Page  = new Page($count,1);// 实例化分页类 传入总记录数和每页显示的记录数
        //$show  = $Page->custom_made_show();// 分页显示输出
        //$this->assign('page',$show);// 赋值分页输出
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('data',$data);
        $this->assign('bann',$bann);
        return $this->fetch('goods/goodslist');


    }
    //商品详情
    // public function goodsInfo(){
    //     $request=request();
    //     $id=$request->param('id');
    //     $res=Db::name('goods')->where('goods_id',$id)->find();
    //     echo "<pre>";
    //     var_dump($res);exit;
    //     return $this->fetch();
    // }




    //用户镜片选择
    public function lenses_back(){
        $color=new Color();
        $arr=Array();
        $request=request();
        if(empty($request->param('store'))){
            if(empty($request->param('func'))){
                $cent=$request->param('centents');
                $id=$request->param('id');
                $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
                $cat_id=$cat[0]['cat_id'];
                $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
                $name=$result[0]['name'];
                switch($name){
                    case '眼镜':
                    $name='Eyeglasses';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'自由');
                                    break;

                                    case 'computer':
                                        $arr[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'自由');
                                    break;

                                    case 'drivewear':
                                        $arr[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960');
                                    break;
                                }
                           }
                          return json_encode($arr);
                        break;

                        case '双光':
                        $cent='bifocal';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                        foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'自由');
                                    break;

                                    case 'computer':
                                        $arr[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'自由');
                                    break;

                                    case 'drivewear':
                                        $arr[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960');
                                    break;
                                }
                           }
                          return json_encode($arr);
                        break;

                        case '进步':
                        $cent='progressive';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                         foreach($res as $val){
                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'自由');
                                    break;

                                    case 'computer':
                                        $arr[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'自由');
                                    break;

                                    case 'drivewear':
                                        $arr[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960');
                                    break;
                                }
                            }            
                           return json_encode($arr);
                        break;
                    }
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'自由');
                                    break;

                                    case 'sun_mirror':
                                        $arr[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥993');
                                    break;
                                }
                           }
                          return json_encode($arr);
                        break;

                        case '进步':
                        $cent='progressive';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                         foreach($res as $val){
                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'自由');
                                    break;

                                    case 'sun_mirror':
                                        $arr[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥960');
                                    break;
                                }
                            }            
                           return json_encode($arr);
                        break;
                    }
                    break;

                    case '运动':
                    $name='Sports';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'自由');
                                    break;

                                    case 'sun_mirror':
                                        $arr[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥993');
                                    break;
                                }
                           }
                          return json_encode($arr);
                        break;
                    }
                    break;
                }
                
                
            }else{
                $func=$request->param('func');
                $id=$request->param('id');
                $cent=$request->param('centents');
                $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
                $cat_id=$cat[0]['cat_id'];
                $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
                $name=$result[0]['name'];

                switch($name)
                {
                    case '眼镜':
                    $name='Eyeglasses';
                    switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    case '双光';
                    $cent='bifocal';
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '专业行驶':
                        $func='drivewear';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                if($val['lens_pkg']=='standard'){
                                    $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'自由');
                                }
                            }                   
                        break;

                        case '转变':
                        $func='transitions';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥314.00');
                                    break;

                                    case 'thinner';
                                        $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                    break;

                                    case 'super_thin';
                                        $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                    break;
                                }
                             }
                        break;

                        case '数字涂层':
                        $func='computer';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thinner';
                                        $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                    break;

                                    case 'super_thin';
                                        $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                    break;
                                }
                             }
                        break;

                        case '清楚的':
                        $func='clear';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                        case 'standard';
                                            $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'自由');
                                        break;

                                        case 'thinner';
                                            $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                        break;

                                        case 'super_thin';
                                            $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                        break;
                                    }
                                 }
                        break;
                    }
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '偏振':
                        $func='nupolar_polarized';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                            }                   
                        break;

                        case '偏光镜':
                        $func='nupolar_polarized_mirror';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;

                                }
                             }
                        break;

                        case '彩色镜片':
                        $func='sun_color';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                   case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                             }
                        break;

                        case '彩色镜子':
                        $func='sun_mirror';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                    }
                                 }
                        break;
                    }
                    break;

                    case '运动':
                    $name='Sports';
                     switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '偏振':
                        $func='nupolar_polarized';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                            }                   
                        break;

                        case '偏光镜':
                        $func='nupolar_polarized_mirror';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;

                                }
                             }
                        break;

                        case '彩色镜片':
                        $func='sun_color';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                   case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                             }
                        break;

                        case '彩色镜子':
                        $func='sun_mirror';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'自由');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                    }
                                 }
                        break;
                    }
                    break;
                }
                
                return json_encode($arr);
            }
       }else{
            $store=$request->param('store');
            $func=$request->param('func');
            $id=$request->param('id');
            $cent=$request->param('centents');
            $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
            $cat_id=$cat[0]['cat_id'];
            $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
            $name=$result[0]['name'];

                switch($name){
                    case '眼镜':
                    $name='Eyeglasses';
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    break;

                    case '运动':
                    $name='Sports';
                    break;
                }
                switch($cent){
                    case '单一视觉':
                    $cent='single_vision';
                    break;
                    case '双光':
                    $cent='bifocal';
                    break;
                    case '进步':
                    $cent='progressive';
                    break;
                }
                
                switch($func){
                    case '专业行驶':
                    $func='drivewear';
                    break;
                    case '转变':
                    $func='transitions';
                    break;
                    case '数字涂层':
                    $func='computer';
                    break;
                    case '清楚的':
                    $func='clear';
                    break;
                    case '偏振':
                    $func='nupolar_polarized';
                    break;
                    case '偏光镜':
                    $func='nupolar_polarized_mirror';
                    break;
                    case '彩色镜片':
                    $func='sun_color';
                    break;
                    case '彩色镜子':
                    $func='sun_mirror';
                    break;
                }
                switch($store)
                {
                    case '标准':
                    $store='standard';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);

                    }
                    break;

                    case '瘦身':
                    $store='thin';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '舒适合身':
                    $store='thinner';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '更薄':
                    $store='thinner';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '超薄':
                    $store='super_thin';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;
                }
                return json_encode($arr);
       }
    }

        //镜头颜色联动
        public function overlay(){
            $request=request();
            $src=$request->param('src');
            $names=$request->param('name');
            $sku=$request->param('sku');
            $length=mb_strlen($sku, 'GBK');
            $nam=substr($names,0,(0-$length)-1);
            $name=str_replace(" ","-","$nam");
            $res=substr($src,49);//截取字符串
            $color=new Color();
            $result=$color->overlay_image($res);
            $data="{$name}-{$sku}{$result}";//拼接路径
            $datas=strtolower($data);//转为小写
            return $datas;
        }


     public function nav(){
        $request=request();
        $name=$request->param('name');
        $res=Db::name('goods_category')->field('id')->where('name',$name)->find();
        $id=$res['id'];
        $result=Db::name('goods_category')->where('parent_id',$id)->select();
        return json_encode($result);
    }


    public function lenses(){
        $color=new Color();
        $arr=Array();
        $request=request();
        if(empty($request->param('store'))){ //鏡頭厚度
            if(empty($request->param('func'))){ //鏡頭類型
                $cent=$request->param('centents'); //内容
                $id=$request->param('id');
                $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
                $cat_id=$cat[0]['cat_id'];
                $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
                $name=$result[0]['name'];
                $result_lense = $result[0]['name'];
                $new_array = Array();
		        switch($result_lense){
		            case '眼镜':
		            $name='Eyeglasses';
		            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
		            foreach($results as $key=>$val){
		                $new_array[]=$val['lens_type'];
		            }
		            $arr=Array();
		            foreach($new_array as $v){
		                switch ($v) {
		                    case 'single_vision':
		                        $arr[]=array('name'=>'单一视觉','centent'=>'对于非处方','price'=>'');
		                        break;

		                    case 'bifocal':
		                        $arr[]=array('name'=>'双光','centent'=>'独特个体的独特视角','price'=>'¥520.00');
		                        break;

		                    case 'progressive':
		                        $arr[]=array('name'=>'进步','centent'=>'40岁以后解锁自然视力的钥匙。','price'=>'¥640.00');
		                        break;
		                }
		            }
		            break;

		            case '墨镜':
		            $name='Sunglasses';
		            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
		            foreach($results as $key=>$val){
		                $new_array[]=$val['lens_type'];
		            }
		            $arr=Array();
		            foreach($new_array as $v){
		                switch($v){
		                    case 'single_vision':
		                        $arr[]=array('name'=>'单一视觉','centent'=>'所有年龄段的前所未有的清晰度和安全性','price'=>'');
		                        break;
		                    case 'progressive':
		                        $arr[]=array('name'=>'进步','centent'=>'40年后你解锁自然视力的关键。','price'=>'¥870.00');
		                        break;
		                }
		            }
		            break;

		            case '运动':
		            $name='Sports';
		            $results=Db::name('lenses')->Distinct(true)->field('lens_type')->where('eyewear_type',$name)->select();
		            foreach($results as $key=>$val){
		                $new_array[]=$val['lens_type'];
		            }
		            $arr=Array();
		            $arr1=Array();
		            foreach($new_array as $v){
		                switch ($v) {
		                    case 'single_vision':
		                        $arr[]=array('name'=>'单一视觉','centent'=>'所有年龄段的前所未有的清晰度和安全性','price'=>'');
		                        break;
		                }
		            }
		            break;
		            $this->assign('results',$arr);
		        }
		        //分配到前台
		        
                $name=$result[0]['name'];
                $cent=$request->param('centents');
                
                switch($name){
                    case '眼镜':
                    $name='Eyeglasses';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr1[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'免費');
                                    break;

                                    case 'computer':
                                        $arr1[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr1[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'免費');
                                    break;

                                    case 'drivewear':
                                        $arr1[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960.00');
                                    break;
                                }
                           }
                          $this->ajaxReturn($arr1);
                        break;

                        case '双光':
                        $cent='bifocal';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                        foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr1[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'免費');
                                    break;

                                    case 'computer':
                                        $arr1[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr1[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'免費');
                                    break;

                                    case 'drivewear':
                                        $arr1[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960.00');
                                    break;
                                }
                           }
                          $this->ajaxReturn($arr1);
                        break;

                        case '进步':
                        $cent='progressive';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                         foreach($res as $val){
                                switch($val['lens_func']){
                                    case 'clear':
                                        $arr1[]=array('name'=>'清楚的','centent'=>'耐冲击，重量轻，非常清晰','price'=>'免費');
                                    break;

                                    case 'computer':
                                        $arr1[]=array('name'=>'数字涂层','centent'=>'保护您的眼睛免受数字设备的排放','price'=>'¥192.00');
                                    break;

                                    case 'transitions':
                                        $arr1[]=array('name'=>'转变','centent'=>'自动调整色彩以适应你周围的光线','price'=>'免費');
                                    break;

                                    case 'drivewear':
                                        $arr1[]=array('name'=>'专业行驶','centent'=>'专门为驾驶而设计 - 唯一可以在挡风玻璃后面过渡的透镜','price'=>'¥960.00');
                                    break;
                                }
                            }            
                           $this->ajaxReturn($arr1);
                        break;
                    }
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr1[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'免費');
                                    break;

                                    case 'sun_mirror':
                                        $arr1[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331.00');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr1[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662.00');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr1[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥993.00');
                                    break;
                                }
                           }
                          $this->ajaxReturn($arr1);
                        break;

                        case '进步':
                        $cent='progressive';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                         foreach($res as $val){
                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr1[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'免費');
                                    break;

                                    case 'sun_mirror':
                                        $arr1[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331.00');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr1[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662.00');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr1[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥960.00');
                                    break;
                                }
                            }            
                           $this->ajaxReturn($arr1);
                        break;
                    }
                    break;

                    case '运动':
                    $name='Sports';
                    switch($cent)
                    {
                        case '仅限框架':
                        
                        break;

                        case '单一视觉':
                        $cent='single_vision';
                        $res=Db::name('lenses')->Distinct(true)->field('lens_func')->where('lens_type',$cent)->where('eyewear_type',$name)->select();
                            foreach($res as $val){

                                switch($val['lens_func']){
                                    case 'sun_color':
                                        $arr1[]=array('name'=>'彩色镜片','centent'=>'从我们选择的颜色中选择适合您个人口味的颜色','price'=>'免費');
                                    break;

                                    case 'sun_mirror':
                                        $arr1[]=array('name'=>'彩色镜子','centent'=>'使用我们的各种彩色镜片，获得好莱坞的魅力','price'=>'¥331.00');
                                    break;

                                    case 'nupolar_polarized':
                                        $arr1[]=array('name'=>'偏振','centent'=>'偏光镜片可阻挡有害的紫外线并增强物体对比度。','price'=>'¥662.00');
                                    break;

                                    case 'nupolar_polarized_mirror':
                                        $arr1[]=array('name'=>'偏光镜','centent'=>'偏光镜片的所有优点加上镜面镜片，带来好莱坞魅力。','price'=>'¥993.00');
                                    break;
                                }
                           }
                          $this->ajaxReturn($arr1);
                        break;
                    }
                    break;
                }
                $this->ajaxReturn('');
                
                
            }else{
                $func=$request->param('func');
                $id=$request->param('id');
                $cent=$request->param('centents');
                $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
                $cat_id=$cat[0]['cat_id'];
                $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
                $name=$result[0]['name'];

                switch($name)
                {
                    case '眼镜':
                    $name='Eyeglasses';
                    switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    case '双光';
                    $cent='bifocal';
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '专业行驶':
                        $func='drivewear';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                if($val['lens_pkg']=='standard'){
                                    $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'免費');
                                }
                            }                   
                        break;

                        case '转变':
                        $func='transitions';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥314.00');
                                    break;

                                    case 'thinner';
                                        $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                    break;

                                    case 'super_thin';
                                        $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                    break;
                                }
                             }
                        break;

                        case '数字涂层':
                        $func='computer';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thinner';
                                        $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                    break;

                                    case 'super_thin';
                                        $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                    break;
                                }
                             }
                        break;

                        case '清楚的':
                        $func='clear';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                        case 'standard';
                                            $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'优秀的光学镜头，最薄的镜头解决方案。','price'=>'免費');
                                        break;

                                        case 'thinner';
                                            $arr[]=array('image'=>'supreme','name'=>'更薄','centent'=>'使用1.67索引镜片增强您的视觉体验，即使在比标准镜片薄30％的较高处方能力下，也能确保最小的失真。','price'=>'¥384.00');
                                        break;

                                        case 'super_thin';
                                            $arr[]=array('image'=>'prestige','name'=>'超薄','centent'=>'使用1.74索引镜片获得最佳的光学体验，这是目前最轻，最薄的精密镜片，比标准镜片薄35％','price'=>'¥640.00');
                                        break;
                                    }
                                 }
                        break;
                    }
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '偏振':
                        $func='nupolar_polarized';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                            }                   
                        break;

                        case '偏光镜':
                        $func='nupolar_polarized_mirror';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;

                                }
                             }
                        break;

                        case '彩色镜片':
                        $func='sun_color';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                   case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                             }
                        break;

                        case '彩色镜子':
                        $func='sun_mirror';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                    }
                                 }
                        break;
                    }
                    break;

                    case '运动':
                    $name='Sports';
                     switch($cent)
                    {
                    case '单一视觉';
                    $cent='single_vision';
                    break;
                    break;
                    case '进步';
                    $cent='progressive';
                    break;
                    }
                    switch($func)
                    {
                        case '偏振':
                        $func='nupolar_polarized';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                            foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                            }                   
                        break;

                        case '偏光镜':
                        $func='nupolar_polarized_mirror';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;

                                }
                             }
                        break;

                        case '彩色镜片':
                        $func='sun_color';
                             $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                             foreach($ress as $val){
                                switch($val['lens_pkg']){
                                   case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                }
                             }
                        break;

                        case '彩色镜子':
                        $func='sun_mirror';
                            $ress=Db::name('lenses')->Distinct(true)->field('lens_pkg')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->select();
                                 foreach($ress as $val){
                                    switch($val['lens_pkg']){
                                    case 'standard';
                                        $arr[]=array('image'=>'standard','name'=>'标准','centent'=>'卓越的光学，最薄的镜头解决方案。','price'=>'免費');
                                    break;

                                    case 'thin';
                                        $arr[]=array('image'=>'supreme','name'=>'瘦身','centent'=>'我们的1.61指数镜头比标准镜头薄25％，不会在功耗和美观之间妥协。','price'=>'¥199.00');
                                    break;


                                    case 'thinner';
                                        $arr[]=array('image'=>'prestige','name'=>'舒适合身','centent'=>'1.67指数镜片可增强您的视觉体验，即使处方能力比标准镜片薄30％，也能确保最小的失真。','price'=>'¥200.00');
                                    break;
                                    }
                                 }
                        break;
                    }
                    break;
                }
                
                return json_encode($arr);
            }
       }else{
            $store=$request->param('store');
            $func=$request->param('func');
            $id=$request->param('id');
            $cent=$request->param('centents');
            $cat=Db::name('goods')->field('cat_id')->where('goods_id',$id)->select();
            $cat_id=$cat[0]['cat_id'];
            $result=Db::name('goods_category')->field('name')->where('id',$cat_id)->select();
            $name=$result[0]['name'];

                switch($name){
                    case '眼镜':
                    $name='Eyeglasses';
                    break;

                    case '墨镜':
                    $name='Sunglasses';
                    break;

                    case '运动':
                    $name='Sports';
                    break;
                }
                switch($cent){
                    case '单一视觉':
                    $cent='single_vision';
                    break;
                    case '双光':
                    $cent='bifocal';
                    break;
                    case '进步':
                    $cent='progressive';
                    break;
                }
                
                switch($func){
                    case '专业行驶':
                    $func='drivewear';
                    break;
                    case '转变':
                    $func='transitions';
                    break;
                    case '数字涂层':
                    $func='computer';
                    break;
                    case '清楚的':
                    $func='clear';
                    break;
                    case '偏振':
                    $func='nupolar_polarized';
                    break;
                    case '偏光镜':
                    $func='nupolar_polarized_mirror';
                    break;
                    case '彩色镜片':
                    $func='sun_color';
                    break;
                    case '彩色镜子':
                    $func='sun_mirror';
                    break;
                }
                switch($store)
                {
                    case '标准':
                    $store='standard';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);

                    }
                    break;

                    case '瘦身':
                    $store='thin';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '舒适合身':
                    $store='thinner';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '更薄':
                    $store='thinner';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;

                    case '超薄':
                    $store='super_thin';
                    $res=Db::name('lenses')->Distinct(true)->field('lens_color')->where('lens_type',$cent)->where('eyewear_type',$name)->where('lens_func',$func)->where('lens_pkg',$store)->select();
                    foreach($res as $val){
                    $res=$val['lens_color'];
                    $arr[]=$color->color($res);
                    }
                    break;
                }
                return json_encode($arr);
       }
    }
  
}
       