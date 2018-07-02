<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: IT宇宙人     
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\admin\logic\SearchWordLogic;
use think\AjaxPage;
use think\Loader;
use think\Page;
use think\Db;
use think\Request;
use think\Model;

class Goods extends Base {
    
    /**
     *  商品分类列表
     */
    public function categoryList(){                
        $GoodsLogic = new GoodsLogic();               
        $cat_list = $GoodsLogic->goods_cat_list();
        $this->assign('cat_list',$cat_list);        
        return $this->fetch();
    }
    
    /**
     * 添加修改商品分类
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'), 
     * select * from tp_goods_category where id = 393
        select * from tp_goods_category where parent_id = 393
        update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
        insert into `tp_goods_category` (`parent_id`,`name`) values 
        ('393','时尚饰品'),
     */
    public function addEditCategory(){
            
            $GoodsLogic = new GoodsLogic();     
            if(IS_GET)
            {
                $goods_category_info = D('GoodsCategory')->where('id='.I('GET.id',0))->find();
                $this->assign('goods_category_info',$goods_category_info);
                
                $all_type = M('goods_category')->where("level<3")->getField('id,name,parent_id');//上级分类数据集，限制3级分类，那么只拿前两级作为上级选择
                if(!empty($all_type)){
                    $parent_id = empty($goods_category_info) ? I('parent_id',0) : $goods_category_info['parent_id'];
                    $all_type = $GoodsLogic->getCatTree($all_type);
                    $cat_select = $GoodsLogic->exportTree($all_type,0,$parent_id);
                    $this->assign('cat_select',$cat_select);
                }
                
                //$cat_list = M('goods_category')->where("parent_id = 0")->select(); 
                //$this->assign('cat_list',$cat_list);         
                return $this->fetch('_category');
                exit;
            }

            $GoodsCategory = D('GoodsCategory'); //

            $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新                   
            //ajax提交验证
            if(I('is_ajax') == 1)
            {
                // 数据验证            
                $validate = \think\Loader::validate('GoodsCategory');
                if(!$validate->batch()->check(input('post.')))
                {                          
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn($return_arr);
                } else {
                    $GoodsCategory->data(input('post.'),true); // 收集数据
                    $GoodsCategory->parent_id = I('parent_id');
                    
                    //查找同级分类是否有重复分类
                    $par_id = ($GoodsCategory->parent_id > 0) ? $GoodsCategory->parent_id : 0;
                    $sameCateWhere = ['parent_id'=>$par_id , 'name'=>$GoodsCategory['name']];
                    $GoodsCategory->id && $sameCateWhere['id'] = array('<>' , $GoodsCategory->id);
                    $same_cate = M('GoodsCategory')->where($sameCateWhere)->find();
                    if($same_cate){
                        $return_arr = array('status' => 0,'msg' => '同级已有相同分类存在','data' => '');
                        $this->ajaxReturn($return_arr);
                    }
                    
                    if ($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id) {
                        //  编辑
                        $return_arr = array('status' => 0,'msg' => '上级分类不能为自己','data' => '',);
                        $this->ajaxReturn($return_arr);
                    }
                    if($GoodsCategory->commission_rate > 100)
                    {
                        //  编辑
                        $return_arr = array('status' => -1,'msg'   => '分佣比例不得超过100%','data'  => '');
                        $this->ajaxReturn($return_arr);                        
                    }   
                   
                    if ($type == 2)
                    {
                        $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库
                        $GoodsLogic->refresh_cat(I('id'));
                    }
                    else
                    {
                        $GoodsCategory->save(); // 写入数据到数据库
                        $insert_id = $GoodsCategory->getLastInsID();
                        $GoodsLogic->refresh_cat($insert_id);
                    }
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',
                        'data'  => array('url'=>U('Admin/Goods/categoryList')),
                    );
                    $this->ajaxReturn($return_arr);

                }  
            }
    }
    
    /**
     * 获取商品分类 的帅选规格 复选框
     */
    public function ajaxGetSpecList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_spec = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_spec');        
        $filter_spec_arr = explode(',',$filter_spec);        
        $str = $GoodsLogic->GetSpecCheckboxList($_REQUEST['type_id'],$filter_spec_arr);  
        $str = $str ? $str : '没有可帅选的商品规格';
        exit($str);        
    }
 
    /**
     * 获取商品分类 的帅选属性 复选框
     */
    public function ajaxGetAttrList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_attr');        
        $filter_attr_arr = explode(',',$filter_attr);        
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'],$filter_attr_arr);          
        $str = $str ? $str : '没有可帅选的商品属性';
        exit($str);        
    }    
    
    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);
        // 判断子分类
        $count = Db::name("goods_category")->where("parent_id = {$ids}")->count("id");
        $count > 0 && $this->ajaxReturn(['status' => -1,'msg' =>'该分类下还有分类不得删除!']);
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$ids}")->count('1');
        $goods_count > 0 && $this->ajaxReturn(['status' => -1,'msg' =>'该分类下有商品不得删除!']);
        // 删除分类
        DB::name('goods_category')->where('id',$ids)->delete();
        $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','url'=>U('Admin/Goods/categoryList')]);
    }
    
    
    /**
     *  商品列表
     */
    public function goodsList(){      
        $GoodsLogic = new GoodsLogic();        
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }
    
    /**
     *  商品列表
     */
    public function ajaxGoodsList(){            
        
        $where = ' 1 = 1 '; // 搜索条件                
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;        
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;                
        $cat_id = I('cat_id');
        // 关键词搜索               
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }
        
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id); 
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        
        $count = M('Goods')->where($where)->count();
        $Page  = new AjaxPage($count,20);
        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        */
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = M('Goods')->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    
    
    public function stock_list(){
        $model = M('stock_log');
        $map = array();
        $mtype = I('mtype');
        if($mtype == 1){
            $map['stock'] = array('gt',0);
        }
        if($mtype == -1){
            $map['stock'] = array('lt',0);
        }
        $goods_name = I('goods_name');
        if($goods_name){
            $map['goods_name'] = array('like',"%$goods_name%");
        }
        $ctime = urldecode(I('ctime'));
        if($ctime){
            $gap = explode(' - ', $ctime);
            $this->assign('start_time',$gap[0]);
            $this->assign('end_time',$gap[1]);
            $this->assign('ctime',$gap[0].' - '.$gap[1]);
            $map['ctime'] = array(array('gt',strtotime($gap[0])),array('lt',strtotime($gap[1])));
        }
        $count = $model->where($map)->count();
        $Page  = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        $stock_list = $model->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('stock_list',$stock_list);
        return $this->fetch();
    }

    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {
        $GoodsLogic = new GoodsLogic();
        $Goods = new \app\admin\model\Goods();
        $goods_id = I('goods_id');
        $type = $goods_id > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {
            // 数据验证
            $is_distribut = input('is_distribut');
            $virtual_indate = input('post.virtual_indate');//虚拟商品有效期
            $return_url = $is_distribut > 0 ? U('admin/Distribut/goods_list') : U('admin/Goods/goodsList');
            $data = input('post.');
            $validate = \think\Loader::validate('Goods');
            if (!$validate->batch()->check($data)) {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            }
            $data['virtual_indate'] = !empty($virtual_indate) ? strtotime($virtual_indate) : 0;
            $data['exchange_integral'] = ($data['is_virtual'] == 1) ? 0 : $data['exchange_integral'];
            $Goods->data($data, true); // 收集数据
            $Goods->on_time = time(); // 上架时间
            I('cat_id_2') && ($Goods->cat_id = I('cat_id_2'));
            I('cat_id_3') && ($Goods->cat_id = I('cat_id_3'));

            I('extend_cat_id_2') && ($Goods->extend_cat_id = I('extend_cat_id_2'));
            I('extend_cat_id_3') && ($Goods->extend_cat_id = I('extend_cat_id_3'));
            $Goods->spec_type = $Goods->goods_type;
            $price_ladder = array();
            if ($Goods->ladder_amount[0] > 0) {
                foreach ($Goods->ladder_amount as $key => $value) {
                    $price_ladder[$key]['amount'] = intval($Goods->ladder_amount[$key]);
                    $price_ladder[$key]['price'] = floatval($Goods->ladder_price[$key]);
                }
                $price_ladder = array_values(array_sort($price_ladder, 'amount', 'asc'));
                $price_ladder_max = count($price_ladder);
                if ($price_ladder[$price_ladder_max - 1]['price'] >= $Goods->shop_price) {
                    $return_arr = array(
                        'msg' => '价格阶梯最大金额不能大于商品原价！',
                        'status' => 0,
                        'data' => array('url' => $return_url)
                    );
                    $this->ajaxReturn($return_arr);
                }
                if ($price_ladder[0]['amount'] <= 0 || $price_ladder[0]['price'] <= 0) {
                    $return_arr = array(
                        'msg' => '您没有输入有效的价格阶梯！',
                        'status' => 0,
                        'data' => array('url' => $return_url)
                    );
                    $this->ajaxReturn($return_arr);
                }
                $Goods->price_ladder = json_encode($price_ladder);
            } else {
                $Goods->price_ladder = '';
            }
            
            $spec_item = I('item/a');
            if ($type == 2) {
                $goods_stock = M('goods')->where(array('goods_id'=>$goods_id))->getField('store_count');
                if(empty($spec_item) && $goods_stock != I('store_count')){
                    update_stock_log(session('admin_id'),I('store_count')-$goods_stock,array('goods_id'=>$goods_id,'goods_name'=>I('goods_name')));//库存日志
                }
                $Goods->isUpdate(true)->save(); // 写入数据到数据库
                // 修改商品后购物车的商品价格也修改一下
                M('cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                    'market_price' => I('market_price'), //市场价
                    'goods_price' => I('shop_price'), // 本店价
                    'member_goods_price' => I('shop_price'), // 会员折扣价
                ));
            } else {
                $Goods->save(); // 写入数据到数据库
                $goods_id = $insert_id = $Goods->getLastInsID();
                if(empty($spec_item)){
                    update_stock_log(session('admin_id'),I('store_count'),array('goods_id'=>$goods_id,'goods_name'=>I('goods_name')));//库存日志
                }
            }
            $Goods->afterSave($goods_id);
            $GoodsLogic->saveGoodsAttr($goods_id, I('goods_type')); // 处理商品 属性
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => $return_url),
            );
            $this->ajaxReturn($return_arr);
        }

        $goodsInfo = Db::name('Goods')->where('goods_id=' . I('GET.id', 0))->find();
       
        if ($goodsInfo['price_ladder']) {
            $goodsInfo['price_ladder'] = json_decode($goodsInfo['price_ladder'], true);
        }
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat($goodsInfo['extend_cat_id']); // 获取分类默认选中的下拉框
        $cat_list = Db::name('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $brandList = $GoodsLogic->getSortBrands($goodsInfo['cat_id']);   //获取三级分类下的全部品牌
        $goodsType = Db::name("GoodsType")->select();
        $suppliersList = Db::name("suppliers")->select();
        $freight_template = Db::name('freight_template')->where('')->select();
        $this->assign('freight_template',$freight_template);
        $this->assign('suppliersList', $suppliersList);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('cat_list', $cat_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        return $this->fetch('_goods');
    }

    public  function getCategoryBindList(){
        $cart_id = I('cart_id/d',0);
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands($cart_id);
        $this->ajaxReturn(['status'=>1,'result'=>$brandList]);
    }
    /**
     * 商品类型  用于设置商品的属性
     */
    public function goodsTypeList(){
        $model = M("GoodsType");                
        $count = $model->count();        
        $Page = $pager = new Page($count,14);
        $show  = $Page->show();
        $goodsTypeList = $model->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch('goodsTypeList');
    }

    /**
     * 添加修改编辑  商品属性类型
     */
    public function addEditGoodsType()
    {
        $id = $this->request->param('id', 0);
        $model = M("GoodsType");
        if (IS_POST) {
            $data = $this->request->post();
            if ($id)
                DB::name('GoodsType')->update($data);
            else
                DB::name('GoodsType')->insert($data);

            $this->success("操作成功!!!", U('Admin/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($id);
        $this->assign('goodsType', $goodsType);
        return $this->fetch('_goodsType');
    }
    
    /**
     * 商品属性列表
     */
    public function goodsAttributeList(){       
        $goodsTypeList = M("GoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }   
    
    /**
     *  商品属性列表
     */
    public function ajaxGoodsAttributeList(){            
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;                
        // 关键词搜索               
        $model = M('GoodsAttribute');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $goodsTypeList = M("GoodsType")->getField('id,name');
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeList',$goodsTypeList);        
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }   
    
    /**
     * 添加修改编辑  商品属性
     */
    public  function addEditGoodsAttribute(){
                        
            $model = D("GoodsAttribute");                      
            $type = I('attr_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新         
            $attr_values = str_replace('_', '', I('attr_values')); // 替换特殊字符
            $attr_values = str_replace('@', '', $attr_values); // 替换特殊字符            
            $attr_values = trim($attr_values);
            
            $post_data = input('post.');
            $post_data['attr_values'] = $attr_values;
            
            if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
            {                                
                    // 数据验证            
                    $validate = \think\Loader::validate('GoodsAttribute');
                    if(!$validate->batch()->check($post_data))
                    {                          
                        $error = $validate->getError();
                        $error_msg = array_values($error);
                        $return_arr = array(
                            'status' => -1,
                            'msg' => $error_msg[0],
                            'data' => $error,
                        );
                        $this->ajaxReturn($return_arr);
                    } else {     
                             $model->data($post_data,true); // 收集数据
                            
                             if ($type == 2)
                             {                                 
                                 $model->isUpdate(true)->save(); // 写入数据到数据库                         
                             }
                             else
                             {
                                 $model->save(); // 写入数据到数据库
                                 $insert_id = $model->getLastInsID();                        
                             }
                             $return_arr = array(
                                 'status' => 1,
                                 'msg'   => '操作成功',                        
                                 'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
                             );
                             $this->ajaxReturn($return_arr);
                }  
            }                
           // 点击过来编辑时                 
           $attr_id = I('attr_id/d',0);  
           $goodsTypeList = M("GoodsType")->select();           
           $goodsAttribute = $model->find($attr_id);           
           $this->assign('goodsTypeList',$goodsTypeList);                   
           $this->assign('goodsAttribute',$goodsAttribute);
           return $this->fetch('_goodsAttribute');
    }  
    
    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
                'goods' => 'goods_id',
                'goods_category' => 'id',
                'brand' => 'id',            
                'goods_attribute' => 'attr_id',
                'ad' =>'ad_id',            
        );        
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];        
        $model->save();   
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',                        
            'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }
        
    /**
     * 删除商品
     */
    public function delGoods()
    {
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);
        $goods_ids = rtrim($ids,",");
        // 判断此商品是否有订单
        $ordergoods_count = Db::name('OrderGoods')->whereIn('goods_id',$goods_ids)->group('goods_id')->getField('goods_id',true);
        if($ordergoods_count)
        {
            $goods_count_ids = implode(',',$ordergoods_count);
            $this->ajaxReturn(['status' => -1,'msg' =>"ID为【{$goods_count_ids}】的商品有订单,不得删除!",'data'  =>'']);
        }
         // 商品团购
        $groupBuy_goods = M('group_buy')->whereIn('goods_id',$goods_ids)->group('goods_id')->getField('goods_id',true);
        if($groupBuy_goods)
        {
            $groupBuy_goods_ids = implode(',',$groupBuy_goods);
            $this->ajaxReturn(['status' => -1,'msg' =>"ID为【{$groupBuy_goods_ids}】的商品有团购,不得删除!",'data'  =>'']);
        }
        
        //删除用户收藏商品记录
        M('GoodsCollect')->whereIn('goods_id',$goods_ids)->delete();
        
        // 删除此商品        
        M("Goods")->whereIn('goods_id',$goods_ids)->delete();  //商品表
        M("cart")->whereIn('goods_id',$goods_ids)->delete();  // 购物车
        M("comment")->whereIn('goods_id',$goods_ids)->delete();  //商品评论
        M("goods_consult")->whereIn('goods_id',$goods_ids)->delete();  //商品咨询
        M("goods_images")->whereIn('goods_id',$goods_ids)->delete();  //商品相册
        M("spec_goods_price")->whereIn('goods_id',$goods_ids)->delete();  //商品规格
        M("spec_image")->whereIn('goods_id',$goods_ids)->delete();  //商品规格图片
        M("goods_attr")->whereIn('goods_id',$goods_ids)->delete();  //商品属性
        M("goods_collect")->whereIn('goods_id',$goods_ids)->delete();  //商品收藏

        $this->ajaxReturn(['status' => 1,'msg' => '操作成功','url'=>U("Admin/goods/goodsList")]);
    }
    
    /**
     * 删除商品类型 
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $id = $this->request->param('id');
        $count = M("Spec")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',U('Admin/Goods/goodsTypeList'));
        // 判断 商品属性        
        $count = M("GoodsAttribute")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品属性不得删除!',U('Admin/Goods/goodsTypeList'));        
        // 删除分类
        M('GoodsType')->where("id = {$id}")->delete();
        $this->success("操作成功!!!",U('Admin/Goods/goodsTypeList'));
    }    

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！"]);
        $attrBute_ids = rtrim($ids,",");
        // 判断 有无商品使用该属性
        $count_ids = Db::name("GoodsAttr")->whereIn('attr_id',$attrBute_ids)->group('attr_id')->getField('attr_id',true);
        if($count_ids){
            $count_ids = implode(',',$count_ids);
            $this->ajaxReturn(['status' => -1,'msg' => "ID为【{$count_ids}】的属性有商品正在使用,不得删除!"]);
        }
        // 删除 属性
        M('GoodsAttribute')->whereIn('attr_id',$attrBute_ids)->delete();
        $this->ajaxReturn(['status' => 1,'msg' => "操作成功!",'url'=>U('Admin/Goods/goodsAttributeList')]);
    }            
    
    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！"]);
        $aspec_ids = rtrim($ids,",");
        // 判断 商品规格项
        $count_ids = M("SpecItem")->whereIn('spec_id',$aspec_ids)->group('spec_id')->getField('spec_id',true);
        if($count_ids){
            $count_ids = implode(',',$count_ids);
            $this->ajaxReturn(['status' => -1,'msg' => "ID为【{$count_ids}】规格，清空规格项后才可以删除!"]);
        }
        // 删除分类
        M('Spec')->whereIn('id',$aspec_ids)->delete();
        $this->ajaxReturn(['status' => 1,'msg' => "操作成功!!!",'url'=>U('Admin/Goods/specList')]);
    } 
    
    /**
     * 品牌列表
     */
    public function brandList(){
        $where = "";
        $keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
        $count = Db::name("Brand")->where($where)->count();
        $Page = $pager = new Page($count,10);        
        $brandList = Db::name("Brand")->where($where)->order("sort desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $show  = $Page->show(); 
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);       
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('brandList',$brandList);
        return $this->fetch('brandList');
    }
    
    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand(){
            $id = I('id');            
            if(IS_POST)
            {
                $data = I('post.');
                $brandVilidate = Loader::validate('Brand');
                if(!$brandVilidate->batch()->check($data)){
                    $return = ['status'=>0,'msg'=>'操作失败','result'=>$brandVilidate->getError()];
                    $this->ajaxReturn($return);
                }
                if($id){
                    Db::name("Brand")->update($data);
                }else{
                    Db::name("Brand")->insert($data);
                }
                $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','result'=>'']);
            }           
           $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
           $this->assign('cat_list',$cat_list);           
           $brand = M("Brand")->find($id);             
           $this->assign('brand',$brand);
           return $this->fetch('_brand');
    }    
    
    /**
     * 删除品牌
     */
    public function delBrand()
    {
        $ids = I('post.ids','');
        empty($ids) && $this->ajaxReturn(['status' => -1,'msg' => '非法操作！']);
        $brind_ids = rtrim($ids,",");
        // 判断此品牌是否有商品在使用
        $goods_count = Db::name('Goods')->whereIn("brand_id",$brind_ids)->group('brand_id')->getField('brand_id',true);
        $use_brind_ids = implode(',',$goods_count);
        if($goods_count)
        {
            $this->ajaxReturn(['status' => -1,'msg' => 'ID为【'.$use_brind_ids.'】的品牌有商品在用不得删除!','data'  =>'']);
        }
        $res=Db::name('Brand')->whereIn('id',$brind_ids)->delete();
        if($res){
            $this->ajaxReturn(['status' => 1,'msg' => '操作成功','url'=>U("Admin/goods/brandList")]);
        }
        $this->ajaxReturn(['status' => -1,'msg' => '操作失败','data'  =>'']);
    }      
    
    /**
     * 商品规格列表    
     */
    public function specList(){       
        $goodsTypeList = M("GoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }
    
    
    /**
     *  商品规格列表
     */
    public function ajaxSpecList(){ 
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;        
        // 关键词搜索               
        $model = D('spec');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $specList = $model->where($where)->order('`type_id` desc')->limit($Page->firstRow.','.$Page->listRows)->select();        
        $GoodsLogic = new GoodsLogic();        
        foreach($specList as $k => $v)
        {       // 获取规格项     
                $arr = $GoodsLogic->getSpecItem($v['id']);
                $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        
        $this->assign('specList',$specList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = M("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);        
        return $this->fetch();
    }

    /**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec(){

            $model = D("spec");
            $id = I('id/d',0);
            if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
            {                
                // 数据验证
                $validate = \think\Loader::validate('Spec');
                $post_data = I('post.');
                $scene = $id>0 ? 'edit' :'add';
                if (!$validate->scene($scene)->batch()->check($post_data)) {  //验证数据
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $this->ajaxReturn(['status' => -1,'msg' => $error_msg[0],'data' => $error]);
                }
                $model->data($post_data, true); // 收集数据
                if ($scene == 'edit') {
                    $model->isUpdate(true)->save(); // 写入数据到数据库
                    $model->afterSave(I('id'));
                } else {
                    $model->save(); // 写入数据到数据库
                    $insert_id = $model->getLastInsID();
                    $model->afterSave($insert_id);
                }
                $this->ajaxReturn(['status' => 1,'msg' => '操作成功','url' => U('Admin/Goods/specList')]);
            }                
           // 点击过来编辑时
           $spec = DB::name("spec")->find($id);
           $GoodsLogic = new GoodsLogic();  
           $items = $GoodsLogic->getSpecItem($id);
           $spec[items] = implode(PHP_EOL, $items); 
           $this->assign('spec',$spec);
           
           $goodsTypeList = M("GoodsType")->select();           
           $this->assign('goodsTypeList',$goodsTypeList);           
           return $this->fetch('_spec');
    }  
    
    
    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;        
        $GoodsLogic = new GoodsLogic();
        //$_GET['spec_type'] =  13;
        $specList = M('Spec')->where("type_id = ".I('get.spec_type/d'))->order('`order` desc')->select();
        foreach($specList as $k => $v)        
            $specList[$k]['spec_item'] = M('SpecItem')->where("spec_id = ".$v['id'])->order('id')->getField('id,item'); // 获取规格项                
        
        $items_id = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);       
        
        // 获取商品规格图片                
        if($goods_id)
        {
           $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');                 
        }        
        $this->assign('specImageList',$specImageList);
        
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');        
    }    
    
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */    
    public function ajaxGetSpecInput(){     
         $GoodsLogic = new GoodsLogic();
         $goods_id = I('goods_id/d') ? I('goods_id/d') : 0;
         $str = $GoodsLogic->getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
         exit($str);   
    }
    
    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename','');
        M('goods_images')->where("image_url = '$path'")->delete();
    }

    /**
     * 初始化商品关键词搜索
     */
    public function initGoodsSearchWord(){
        $searchWordLogic = new SearchWordLogic();
        $successNum = $searchWordLogic->initGoodsSearchWord();
        $this->success('成功初始化'.$successNum.'个搜索关键词');
    }

    /**
     * 初始化地址json文件
     */
    public function initLocationJsonJs()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $area_list = $goodsLogic->getAreaList();
        $data = "var locationJsonInfoDyr = ".json_encode($region_list, JSON_UNESCAPED_UNICODE).';'."var areaListDyr = ".json_encode($area_list, JSON_UNESCAPED_UNICODE).';';
        file_put_contents(ROOT_PATH."public/js/locationJson.js", $data);
        $this->success('初始化地区json.js成功。文件位置为'.ROOT_PATH."public/js/locationJson.js");
    }

     //分类页面横幅图片内容
     public function bannersList(){
       return $this->fetch();
    }

    public function addEditBanners(){
        $cat_list = M('goods_category')->where("parent_id = 0")->select();
        $this->assign('cat_list',$cat_list); 
        return $this->fetch();
    }

    public function twoLeve(){
        $request=request();
        $id=$request->param('id');
        $cat_lists=Db::name('goods_category')->where('parent_id',$id)->select();
        return json_encode($cat_lists);
    }

    public function addBanners(){
        $request=request();
        $res=$request->only(['name','cat_id','desc','logo','alts']);
        //验证
            $bol = $this->validate($res,'Goods.banner');
            if (true !== $bol) {
                $this->error($bol,'/Goods/bannersList');
            }

        $result=Db::name('banner')->insert($res);
        if($result==1){
            return $this->ajaxReturn(['status' => 1,'msg' => '操作成功','url' => U('Admin/Goods/addEditBanners')]);
        }
    }

    //mf.leung 新增函數20180526
    /*commodity_import*/
    // 導入商品
    public function commodity_import()
    {
        header('Content-Type:text/html;charset=utf-8');
        $role_id = I('session.admin_id');
        $condition = 'user_name';
        $role_sesssion_name = Db::name('admin')->where($condition,"admin")->select();
        // echo "<pre>";
        // print_r($role_sesssion_name);
        $files = Request::instance()->file('files');
        $post = input("post.");
        $getExtension = Array("0" => "xlsx","1"=>"csv","2"=>"xls");
        $date = date('Ymd');
        $pathinfos ='./pulic/uploads/temp/'.$date;

        if($_POST)
        {
            if(empty(input('post.files')))
            {
                return ajaxReturn(['status' => 0,'msg' => '文檔不能為空']);
            }
            if($role_id == 1 && $role_sesssion_name)
            {
                if($files){
                    //search files
                    $infos = $files->move(ROOT_PATH.'public'.DS.'uploads/temp');
                    $this->file_open($infos->getSaveName(),$infos->getExtension());
                }else{
                    $info->getError();
                }

            }
            else{
                $this->ajaxReturn(['status' => 0,'msg' => '你無權限使用，請聯係管理員']);
            }
        }
        return $this->fetch();
    }

    //mf.leung 新增函數20180530
    /*file_open
    param $files 接受文件或文件路徑的參數
    param $Extension 文件的後綴名字
    文件導入
    */
    public function file_open($files,$Extension)
    {
        $url = "public/uploads/temp/".$files; 
        $lines = array_map('str_getcsv', file($url));
        $Goods = new \app\admin\model\Goods();
        $result_datas = Array();
        $result = Array();
        $GoodsLogic = new GoodsLogic();
        $headers;

        switch($Extension)
        {
            case "csv":
                $result = array("status" => "1","msg" => ".CSV資料表成功導入");
            break;
            default:
               $this->ajaxReturn(['status' => 0,'msg' => ".". $Extension . "不是有效文件，請重新輸入"]);
            break;
        }
        if (count($lines) > 0) {
            $headers = $lines[0];
        }
        for($i=1; $i<count($lines); $i++) {
            $obj = $lines[$i];
            $result_datas[] = array_combine($headers, $obj);
        }

        if(!empty(Db::table("tp_goods")->where("")->select()))
        {
            //更新數據
            $mysql_results = Db::table("tp_goods")->column("sku");

            /*options*/
            //定義新欄目
            $query_name = Db::name('goods_category')->column("name");
            $save_array = array();
            $save_array[] = $query_name;

            $parent_id_path = "0_1";                    
            $defaults = ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];                    
            $condition["name"] = $defaults["name"];
            $condition["mobile_name"] = $defaults["mobile_name"];                    
            $search_classes = Db::table('tp_goods_category')->where("parent_id",0)->order("id","asc")->find();                    
            $search_son_id = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->select();                    
            $counts = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->count("id");
            $counts == 1 ? $limits = 1:$limits = ''.($counts -1).',1';                   
            $search = "SELECT * FROM `tp_goods_category` WHERE parent_id = ".$search_classes['id']."  ORDER BY id ASC limit  ".$limits;
            if(Db::query($search)){
                $int = Db::query($search);
                // print_r($int);
                $glasses = "glasses".($int[0]['id'] + 1);
                $glasses2 = ("glasses".($int[0]['id'] + 2));
                $mobiles_name = "glasses".($int[0]['id'] + 1);
                $mobiles_name2 = ("glasses".($int[0]['id'] + 2));
                $parent_id = $int[0]['id'];
                $parent_id2 = ($int[0]['id'] + 1);
                $parent_id_path = '0'.'_'.''.$search_classes['id'].''.'_'.''.($int[0]['id']+1).'';
                $parent_id_path2 = '0'.'_'.''.$parent_id2.''.'_'.''.($int[0]['id']+2).'';
                $datas = [["name"=>$glasses,"mobile_name"=>$mobiles_name,"parent_id"=>$search_classes['id'],"parent_id_path"=>$parent_id_path,"level"=>"2","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"],
                ["name"=>$glasses2,"mobile_name"=>$mobiles_name2,"parent_id"=>$parent_id2,"parent_id_path"=>$parent_id_path2,"level"=>"3","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"]];
                // print_r($datas);
                array_unshift($save_array[0],$datas[1]['name']);
                foreach($result_datas as $return_sku){
                    $where['sku'] = $return_sku["sku"];
                    $sql = M("goods")->where($where)->getField("goods_name");
                    if($sql){

                     }else{
                        foreach($save_array[0] as $value){
                            $where1["name"] = $value;
                            $MYSQL1 = M("goods_category")->where($where1)->getField("name");
                            //print_r($MYSQL1);
                            if(!$MYSQL1){
                               Db::name('goods_category')->insertAll($datas); 
                               /*print_r($value);*/
                            }
                        }
                    }
                }
                  
            }else{

            }
            foreach($result_datas as $brands_name){
                $datas1[] = $brands_name["brand"];
                $mysql_results = Db::table("tp_goods")->column("sku");
                if(!in_array($brands_name['sku'],$mysql_results))
                {
                    $datas3[] = $brands_name['brand'];
                }
                    
            }

            foreach($result_datas as $results)
            {
                $post_field = Db::table("tp_goods")->where("sku",$results['sku'])->field("goods_id,cat_id,extend_cat_id,brand_id")->select();
                $mysql_results = Db::table("tp_goods")->where("sku",$results['sku'])->column("sku");
                $counts = Db::table('tp_goods_category')->where("parent_id",$search_classes["id"])->count("id");
                $limits = ''.($counts-1).',1';
                $search_brands_class = "SELECT * FROM `tp_goods_category` WHERE parent_id = ".$search_classes['id']."  ORDER BY id ASC limit  ".$limits;
                if(!$mysql_results){
                    for($j = 0;$j<count(array_values(array_unique($datas1)));$j++)
                    {
                        $value = array_values(array_unique($datas1));
                        $condition2["name"] = $value[$j];
                        $search_brands = M('brand')->where($condition2)->field("id,name")->select(); 
                        if(!$search_brands)
                        {
                            $names = $value[$j];
                            $parent_cat_id = $search_classes;
                            $cat_ids1 = Db::query($search_brands_class);
                            $datas = ["name" => $names,
                             "parent_cat_id"=>$parent_cat_id['id'],
                             "cat_id"=>$cat_ids1[0]['id'],
                             "sort"=>'1'];
                            $brands = M('brand');//實例化一個對象
                            $brands->data($datas)->add();
                            $add_save[] = $datas['name'];
                            
                        }else
                        {

                        }

                    }

                    $post_parent_id = Db::query($search_brands_class); //獲得欄目父親下子iD
                    $cat_id = $post_parent_id[0]['id'];
                    $parent_cat_id = $search_classes["parent_id"];//欄目父親ID
                    $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
                    if(strlen($results["short_description"]) > 240){
                        $goods_remark = substr($results["short_description"],0,240)."....";
                    }else
                    {
                        $goods_remark = $results["short_description"];
                    }
                    $goods_remark; //商品簡單描述
                    $sku = $results["sku"];//商品SKU碼
                    $goods_sn = $results["sku"];
                    $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                    $Shop_price = "1.00";//本店售價
                    $Market_price = "1.00";//市場價
                    $goods_content = $results['short_description'];
                    $store_count = 1;
                    $condition_brands["parent_cat_id"] = $search_classes['id'];
                    $condition_brands['name'] = $results["brand"];
                    $brands_id = M("brand")->where($condition_brands)->field('id')->select();
                    $datas = [
                        "cat_id"=>$cat_id,
                        "extend_cat_id"=>$parent_cat_id,
                        "goods_sn"=>$goods_sn,
                        "goods_name"=>$goods_name,
                        "brand_id"=>$brands_id[0]["id"],
                        "keywords"=>$goods_keywords,
                        "goods_remark"=>$goods_remark,
                        "goods_content"=>$goods_content,
                        "shop_price"=>$Shop_price,
                        "market_price"=>$Market_price,
                        "is_free_shipping"=>1,
                        "sku"=>$sku];

                    $store_sku[] = $results["sku"];
                    $goods_id = $Goods->getLastInsID();
                    update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                    M("goods")->data($datas)->add();
                    $results = ['status'=>'updates',"msg" => "數據更新成功新增了商品","datas"=>$store_sku];
                    
                }
                else{
                    $id = $post_field[0]['goods_id']; //產品ID
                    $cat_id = $post_field[0]['cat_id']; //獲得欄目父親下子iD
                    $parent_cat_id = $post_field[0]['extend_cat_id']; //欄目父親ID
                    $brands_id = $post_field[0]['brand_id']; //獲得品牌ID
                    $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
                    if(strlen($results["short_description"]) > 240){
                        $goods_remark = substr($results["short_description"],0,240)."....";
                    }else
                    {
                        $goods_remark = $results["short_description"];
                    }
                    
                    $goods_remark; //商品簡單描述
                    $sku = $results["sku"];//商品SKU碼
                    $goods_sn = $results["sku"];
                    $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                    $Shop_price = "1.00";//本店售價
                    $Market_price = "1.00";//市場價
                    $goods_content = $results['short_description'];
                    $store_count = 1;
                    $datas = [
                        "cat_id"=>$cat_id,
                        "extend_cat_id"=>$parent_cat_id,
                        "goods_sn"=>$goods_sn,
                        "goods_name"=>$goods_name,
                        "brand_id"=>$brands_id,
                        "keywords"=>$goods_keywords,
                        "goods_remark"=>$goods_remark,
                        "goods_content"=>$goods_content,
                        "shop_price"=>$Shop_price,
                        "market_price"=>$Market_price,
                        "is_free_shipping"=>1,
                        "sku"=>$sku];
                    $goods_id = $Goods->getLastInsID();
                    update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                    Db::name("goods")->where('goods_id',$id)->update($datas);
                    $results = ['status'=>'updates',"msg" => "數據更新完成","datas"=>""];
                }
            } 
            
        }else
        {

            //default datas 三級欄目創建
            $category = Db::name('goods_category')->column("name");
            $category1 = Db::name('goods_category')->column("mobile_name");
            $parent_id_path = '0_1';
            $defaults = ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];
            //檢查數數據庫沒有這條數據 return array
            if(!in_array($defaults["name"],$category) || !in_array($defaults["mobile_name"],$category1))
            {
                if(empty($category)){
                    $datas = [
                    ["name"=>"glasses","mobile_name"=>"glasses","parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"],
                    ["name" =>"glasses1","mobile_name"=>"glasses2","parent_id" => "1","parent_id_path" => $parent_id_path."_1","level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                    ["name" =>"glasses12","mobile_name" => "glasses22","parent_id" => "2","parent_id_path" => $parent_id_path."_2","level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]];
                    Db::name('goods_category')->insertAll($datas,true);
                    //寫入數據
                }

                
                
            }
            else{
                //echo "匹配到數據";
                $count = Db::query("SELECT count(id) FROM tp_goods_category");
                if($count[0]["count(id)"] == 1)
                {
                    //如果數據篩選出總和返回列表1條數據
                    $count = 1;
                }else{

                    // 如果數據篩選列表超出一條數據
                    // 例如6條數據選擇最後1條
                    // 按照ASC的排序選中
                    $count = ($count[0]['count(id)']-1).',1';
                }
                $mysql1 = Db::query("SELECT * FROM tp_goods_category ORDER BY id ASC limit ".$count);
                $glasses = 'glasses'.($mysql1[0]["id"]+1);
                $mobile_name = 'glasses'.($mysql1[0]["id"]+1);
                $parent_id_path = ('0'.'_'.($mysql1[0]["id"] + 1));
                $datas = ["name"=>$glasses,"mobile_name"=>$mobile_name,"parent_id"=>"0","parent_id_path"=>$parent_id_path,"level"=>"1","sort_order"=>"1","is_show"=>"0","image"=>"","is_hot"=>"0","commission_rate"=>"1"];
                Db::name('goods_category')->insert($datas,true);
                //checkout mysql
                $mysql = "SELECT`name`,`id`,`mobile_name`,`parent_id`,`parent_id_path` FROM tp_goods_category WHERE
                         tp_goods_category.`name` = '".$glasses."' AND tp_goods_category.mobile_name = '".$mobile_name."'";

                $mysql = Db::query($mysql);
               //查詢是否能有二級列表
                if($mysql){
                    $count = Db::query("SELECT count(id) FROM tp_goods_category WHERE parent_id = ".$mysql[0]["parent_id"]." AND id=".$mysql[0]["id"]."");
                    if($count[0]["count(id)"] == 1)
                    {
                        //如果數據篩選出總和返回列表1條數據
                        $count = 1;
                    }else{

                        // 如果數據篩選列表超出一條數據
                        // 例如6條數據選擇最後1條
                        // 按照ASC的排序選中
                        $count = ($count[0]['count(id)']-1).',1';
                    }
                    $mysql1 = "SELECT * FROM tp_goods_category where id = ".$mysql[0]['id']." ORDER BY id ASC limit ".$count;
                    $mysql1 = Db::query($mysql1);

                    //設置二級菜單   
                    $glasses1 = 'glasses'.($mysql1[0]["id"] + 1);
                    $mobile_name1 = 'glasses'.($mysql1[0]["id"] + 1);
                    $parent_id_path1 = ('0'.'_'.''.($mysql1[0]["id"]).''.'_'.''.($mysql1[0]["id"] + 1).'');
                    $parent_id1 = ($mysql1[0]["id"]);
                    //設置三級菜單
                    $glasses2 = 'glasses'.($mysql1[0]["id"] + 2);
                    $mobile_name2 = 'glasses'.($mysql1[0]["id"] + 2);
                    $parent_id_path2 = ('0'.'_'.''.($mysql1[0]["id"] + 1).''.'_'.''.($mysql1[0]["id"] + 2).'');
                    $parent_id2 = ($mysql1[0]["id"] + 1); 

                    $datas = [
                        ["name" => $glasses1,"mobile_name" => $mobile_name1,"parent_id" => $parent_id1,"parent_id_path" => $parent_id_path1,"level" => "2","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"],
                        ["name" => $glasses2,"mobile_name" => $mobile_name2,"parent_id" => $parent_id2,"parent_id_path" => $parent_id_path2,"level" => "3","sort_order" => "1","is_show" => "0","image" => "","is_hot" => "0","commission_rate" => "1"]];
                    Db::name('goods_category')->insertAll($datas,true);
                }
                /*default datas 三級欄目創建END*/

            }
            // 設置或獲得品牌
            //默認取第一個分類
            //Return brands name
            $opt_brands = Db::table('tp_brand')->find();
            $isVailte = true;
            $data_brand = [];
            $brand_update = [];
            $datas1 = [];
            $level1 =  Db::table("tp_goods_category")->where(["name"=>"glasses","mobile_name"=>"glasses"])->find();
            $level2 =  Db::table("tp_goods_category")->where("parent_id",$level1["id"])->limit(1)->find();
            if(!is_null($level1))
            {
                if(empty($opt_brands))
                {
                    foreach($result_datas as $brand_item){   
                        $data_brand[] = $brand_item['brand'];
                    }
                    
                    /*第一次過濾重複的值在用數組儲存這些值
                    由於這些數組的值過濾完之後不一樣在用
                    for在去枚舉一次儲存到數據庫*/
                    $values = array_values(array_unique($data_brand));
                    for($j=0;$j < count($values);$j++){
                        $datas = ["name" => $values[$j],
                                 "parent_cat_id"=>$level1['id'],
                                 "cat_id"=>$level2["id"],
                                 "sort"=>'1'];
                        Db::name("brand")->data($datas)->insert();
                        $datas1[] = $datas['name'];
                    }
                    $results = ['status'=>'add',"msg" => "提交商品品牌成功","datas"=>$datas1];

                }else{
                    // 定義數據
                    $level1 =  Db::table("tp_goods_category")->where(["name"=>"glasses","mobile_name"=>"glasses"])->find();
                    $level2 = Db::table("tp_goods_category")->where("parent_id",$level1['id'])->select();
                    foreach($level2 as $brand_item)
                    {   
                        
                        $brand_name = Db::table("tp_brand")->where(["parent_cat_id"=>$level1['id'],"cat_id"=>$brand_item['id']])->column("name");
                        $count = Db::table("tp_brand")->where(["parent_cat_id"=>$level1['id'],"cat_id"=>$brand_item['id']])->count("id");
                        if($count > 0){
                            foreach($result_datas as $brand_item1){   
                                $data_brand[] = $brand_item1['brand'];
                            }
                            /*更新數據庫要先過濾重複的值*/
                            foreach(array_unique($data_brand) as $values0)
                            {
                                $datas = ["name" => $values0,
                                         "parent_cat_id"=>$level1['id'],
                                         "cat_id"=>$brand_item['id'],
                                         "sort"=>'1'];
                                 $brand_update[] = $datas["name"];
                                if(!in_array($values0,$brand_name)){

                                    //若字段查找不到，那麽就新增新字段到數據庫
                                    $brands = M('brand');//實例化一個對象
                                    $brands->data($datas)->add();
                                     $add_save[] = $values0;
                                     $results = ['status'=>'insert_item',"msg" => "更新商品品牌有","data"=> $add_save];
                                }else{

                                    // 查找數據庫ID
                                    $brands_id = Db::table("tp_brand")->where("name",$datas["name"])->column('id');
                                    $brands = M('brand'); //實例化一個對象
                                    $datas['id'] = $brands_id[0];
                                    $datas['name'] = $values0;
                                    $brands->where("id=".$datas["id"])->data($datas)->save();
                                    $results = ['status'=>'updates',"msg" => "更新商品品牌成功",'data'=>$brand_update];
                                }

                            }

                        }
                    }

                }
            }
            //品牌結束END

            foreach($result_datas as $results)
            {
               $goods_name = $result["brand"]." ".$results["sku"]; //商品名稱
               if(strlen($results["short_description"]) > 240){
                    $goods_remark = substr($results["short_description"],0,240)."....";
               }else
               {
                 $goods_remark = $results["short_description"];
               }
               
               $goods_remark; //商品簡單描述
               $sku = $results["sku"];//商品SKU碼
               $level1 =  Db::table("tp_goods_category")->where(["name" => "glasses","mobile_name"=>"glasses"])->find();
               $level2 = Db::table("tp_goods_category")->where("parent_id",$level1['id'])->select();

               foreach($level2 as $level_id)
               {
                 if(Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->find())
                 {

                    $level3 = Db::table("tp_goods_category")->where("parent_id",$level_id['id'])->select();
                    foreach($level3 as $level3_ids){
                       $level3_params = $level3_ids;

                    }
                    $post_brands = Db::table("tp_brand")->where(["parent_cat_id"=>$level1["id"],"cat_id"=>$level_id['id']])->select();
                    $post_brands2 = Db::table("tp_brand")->where("cat_id",$level_id['id'])->find();
                    $brand_id = Db::table("tp_brand")
                    ->where(["parent_cat_id"=>$level1["id"],"cat_id"=>$level_id['id'],"name"=>$results["brand"]])
                    ->column("id");
                    foreach($post_brands as $request_brands){
                       $post_brands1[] = $request_brands["name"];
                       // $brands_all_datas[] = $request_brands; 
                    }

                 }

                 // 獲得品牌對應的二級分類ID
                 if(!is_null($post_brands))
                 {
                    $cat_id_brand = $post_brands2;
                 }else
                 {
                    print_r($post_brands);
                 }
                    
               }
                
                $extends_id = $level1["parent_id"];//扩展分类id
                $cat_id = $cat_id_brand["cat_id"]; //分类id 
                $brand_id;
                $goods_sn = $results["sku"];
                $goods_keywords = ''.$results["brand"].','.$goods_name.','.$results['supplier'].''."\n";//商品SEO關鍵詞
                $Shop_price = "1.00";//本店售價
                $Market_price = "1.00";//市場價
                $goods_content = $results['short_description'];
                $store_count = 1;
                $datas = [
                    "cat_id"=>$cat_id,
                    "extend_cat_id"=>$extends_id,
                    "goods_sn"=>$goods_sn,
                    "goods_name"=>$goods_name,
                    "brand_id"=>$brand_id[0],
                    "keywords"=>$goods_keywords,
                    "goods_remark"=>$goods_remark,
                    "goods_content"=>$goods_content,
                    "shop_price"=>$Shop_price,
                    "market_price"=>$Market_price,
                    "is_free_shipping"=>1,
                    "sku"=>$sku];
                $goods_id = $Goods->getLastInsID();
                update_stock_log(session('admin_id'),$store_count,array('goods_id'=>$goods_id,'goods_name'=>$goods_name));//库存日志
                Db::name("goods")->data($datas)->insert();
                
            }
            $results = ['status'=>"add","msg" => "導入數據成功現跳轉商品欄目頁",'datas'=> U('Admin/Goods/goodsList')];
        }

        $this->ajaxReturn($results);
    }
}
