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
namespace app\mobile\controller;
use app\common\logic\CartLogic;
use app\common\logic\GoodsActivityLogic;
use app\common\logic\CouponLogic;
use app\common\logic\Integral;
use app\common\logic\OrderLogic;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\model\Goods;
use app\common\model\SpecGoodsPrice;
use app\common\util\TpshopException;
use think\Db;
use think\Url;

class Cart extends MobileBase {

    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new CartLogic();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
            // 给用户计算会员价 登录前后不一样
            if ($user) {
                $user['discount'] = (empty($user['discount'])) ? 1 : $user['discount'];
                if ($user['discount'] != 1) {
                    $c = Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->where('member_goods_price = goods_price')->count();
                    $c && Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->update(['member_goods_price' => ['exp', 'goods_price*' . $user['discount']]]);
                }
            }
        }
    }

    public function index(){
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartList = $cartLogic->getCartList();//用户购物车
        $userCartGoodsTypeNum = $cartLogic->getUserCartGoodsTypeNum();//获取用户购物车商品总数
        $hot_goods = M('Goods')->where('is_hot=1 and is_on_sale=1')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('hot_goods', $hot_goods);
        $this->assign('userCartGoodsTypeNum', $userCartGoodsTypeNum);
        $this->assign('cartList', $cartList);//购物车列表
        return $this->fetch();
    }

    /**
     * 更新购物车，并返回计算结果
     */
    public function AsyncUpdateCart()
    {
        $cart = input('cart/a', []);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->AsyncUpdateCart($cart);
        $this->ajaxReturn($result);
    }

    /**
     *  购物车加减
     */
    public function changeNum(){
        $cart = input('cart/a',[]);
        if (empty($cart)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要更改的商品', 'result' => '']);
        }
        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cart['id'],$cart['goods_num']);
        $this->ajaxReturn($result);
    }

    /**
     * 删除购物车商品
     */
    public function delete(){
        $cart_ids = input('cart_ids/a',[]);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->delete($cart_ids);
        if($result !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功','result'=>$result]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'删除失败','result'=>$result]);
        }
    }

    /**
     * 购物车第二步确定页面
     */
    public function cart2(){
        $goods_id = input("goods_id/d"); // 商品id
        $goods_num = input("goods_num/d");// 商品数量
        $item_id = input("item_id/d"); // 商品规格id
        $action = input("action/s"); // 行为
        if ($this->user_id == 0){
            $this->error('请先登录', U('Mobile/User/login'));
        }
        $address_id = I('address_id/d');
        if($address_id){
            $address = M('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id'=>$this->user_id])->order(['is_default'=>'desc'])->find();
        }
        if(empty($address)){
            $address = M('user_address')->where(['user_id'=>$this->user_id])->find();
        }
        $cartLogic = new CartLogic();
        $couponLogic = new CouponLogic();
        $cartLogic->setUserId($this->user_id);
        //立即购买
        if($action == 'buy_now'){
            $cartLogic->setGoodsModel($goods_id);
            $cartLogic->setSpecGoodsPriceModel($item_id);
            $cartLogic->setGoodsBuyNum($goods_num);
            $buyGoods = [];
            try{
                $buyGoods = $cartLogic->buyNow();
            }catch (TpshopException $t){
                $error = $t->getErrorArr();
                $this->error($error['msg']);
            }
            $cartList['cartList'][0] = $buyGoods;
            $cartGoodsTotalNum = $goods_num;
        }else{
            if ($cartLogic->getUserCartOrderCount() == 0){
                $this->error('你的购物车没有选中商品', 'Cart/index');
            }
            $cartList['cartList'] = $cartLogic->getCartList(1); // 获取用户选中的购物车商品
            $cartGoodsTotalNum = count($cartList['cartList']);
        }
        $cartGoodsList = get_arr_column($cartList['cartList'],'goods');
        $cartGoodsId = get_arr_column($cartGoodsList,'goods_id');
        $cartGoodsCatId = get_arr_column($cartGoodsList,'cat_id');
        $cartPriceInfo = $cartLogic->getCartPriceInfo($cartList['cartList']);  //初始化数据。商品总额/节约金额/商品总共数量
        $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, $cartGoodsId, $cartGoodsCatId);//用户可用的优惠券列表
        $cartList = array_merge($cartList,$cartPriceInfo);
        $userCartCouponList = $cartLogic->getCouponCartList($cartList, $userCouponList);
        $userCouponNum = $cartLogic->getUserCouponNumArr();
        $this->assign('address',$address); //收货地址
        $this->assign('userCartCouponList', $userCartCouponList);  //优惠券，用able判断是否可用
        $this->assign('userCouponNum', $userCouponNum);  //优惠券数量
        $this->assign('cartGoodsTotalNum', $cartGoodsTotalNum);
        $this->assign('cartList', $cartList['cartList']); // 购物车的商品
        $this->assign('cartPriceInfo', $cartPriceInfo);//商品优惠总价
        return $this->fetch();
    }


    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        if($this->user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        $address_id = I("address_id/d"); //  收货地址id
        $invoice_title = I('invoice_title'); // 发票
        $taxpayer = I('taxpayer'); // 纳税人编号
        $coupon_id =  I("coupon_id/d"); //  优惠券id
        $pay_points =  I("pay_points/d",0); //  使用积分
        $user_money =  I("user_money/f",0); //  使用余额
        $user_note = trim(I('user_note'));   //买家留言
        $goods_id = input("goods_id/d"); // 商品id
        $goods_num = input("goods_num/d");// 商品数量
        $item_id = input("item_id/d"); // 商品规格id
        $action = input("action"); // 立即购买
        $payPwd =  I("payPwd",''); // 支付密码
        mb_strlen($user_note) > 50 && exit(json_encode(['status'=>-1,'msg'=>"备注超出限制可输入字符长度！",'result'=>null]));
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
        $cartLogic = new CartLogic();
        $pay = new Pay();
        $cartList = [];
        try {
            $cartLogic->setUserId($this->user_id);
            $pay->setUserId($this->user_id);
            if ($action == 'buy_now') {
                $cartLogic->setGoodsModel($goods_id);
                $cartLogic->setSpecGoodsPriceModel($item_id);
                $cartLogic->setGoodsBuyNum($goods_num);
                $buyGoods = $cartLogic->buyNow();
                array_push($cartList,$buyGoods);
                $pay->payGoodsList($cartList);
            } else {
                $userCartList = $cartLogic->getCartList(1);
                $cartLogic->checkStockCartList($userCartList);
                $cartList = array_merge_recursive($cartList,$userCartList);
                $pay->payCart($cartList);
            }
            $pay->delivery($address['district']);
            $pay->orderPromotion();
            $pay->useCouponById($coupon_id);
            $pay->useUserMoney($user_money);
            $pay->usePayPoints($pay_points);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
        // 提交订单
        if ($_REQUEST['act'] == 'submit_order') {
            $placeOrder = new PlaceOrder($pay);
            $placeOrder->setUserAddress($address);
            $placeOrder->setInvoiceTitle($invoice_title);
            $placeOrder->setUserNote($user_note);
            $placeOrder->setTaxpayer($taxpayer);
            $placeOrder->setPayPsw($payPwd);
            try{
                $placeOrder->addNormalOrder();
            }catch (TpshopException $t) {
                $error = $t->getErrorArr();
                $this->ajaxReturn($error);
            }
            $cartLogic->clear();
            $order = $placeOrder->getOrder();
            $this->ajaxReturn(['status'=>1,'msg'=>'提交订单成功','result'=>$order['order_sn']]);
        }
        $car_price = $pay->toArray();
        $this->ajaxReturn(['status'=>1,'msg'=>'计算成功','result'=>$car_price]);
    }
    /*
     * 订单支付页面
     */
    public function cart4(){
        if(empty($this->user_id)){
            $this->redirect('User/login');
        }
        $order_id = I('order_id/d');
        $order_sn= I('order_sn/s','');
        $order_where = ['user_id'=>$this->user_id];
        if($order_sn)
        {
            $order_where['order_sn'] = $order_sn;
        }else{
            $order_where['order_id'] = $order_id;
        }
        $order = M('Order')->where($order_where)->find();
        empty($order) && $this->error('订单不存在！');
        if($order['order_status'] == 3){
            $this->error('该订单已取消',U("Mobile/Order/order_detail",array('id'=>$order['order_id'])));
        }
        if(empty($order) || empty($this->user_id)){
            $order_order_list = U("User/login");
            header("Location: $order_order_list");
            exit;
        }
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order['order_id']));
            header("Location: $order_detail_url");
            exit;
        }
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        //如果是预售订单，支付尾款
        if($order['pay_status'] == 2 && $order['prom_type'] == 4){
            $pre_sell_info = M('goods_activity')->where(array('act_id'=>$order['prom_id']))->find();
            $pre_sell_info = array_merge($pre_sell_info,unserialize($pre_sell_info['ext_info']));
            if($pre_sell_info['retainage_start'] > time()){
                $this->error('还未到支付尾款时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']));
            }
            if($pre_sell_info['retainage_end'] < time()){
                $this->error('对不起，该预售商品已过尾款支付时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']));
            }
        }
        $payment_where['type'] = 'payment';
        $no_cod_order_prom_type = ['4,5'];//预售订单，虚拟订单不支持货到付款
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //微信浏览器
            if(in_array($order['prom_type'],$no_cod_order_prom_type) || in_array(1,$orderGoodsPromType)){
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = 'weixin';
            }else{
                $payment_where['code'] = array('in',array('weixin','cod'));
            }
        }else{
            if(in_array($order['prom_type'],$no_cod_order_prom_type) || in_array(1,$orderGoodsPromType)){
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = array('neq','cod');
            }
            $payment_where['scene'] = array('in',array('0','1'));
        }
        $payment_where['status'] = 1;
        //预售和抢购暂不支持货到付款
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        if($order['prom_type'] == 4 || in_array(1,$orderGoodsPromType)){
            $payment_where['code'] = array('neq','cod');
        }
        $paymentList = M('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if(($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())){
                unset($paymentList[$key]);
            }
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }

    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $item_id = I("item_id/d"); // 商品规格id
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setGoodsModel($goods_id);
        if($item_id){
            $cartLogic->setSpecGoodsPriceModel($item_id);
        }
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart();
        exit(json_encode($result));
    }
    /**
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress(){
        $regionList = get_region_list();
        $address_list = M('UserAddress')->where("user_id", $this->user_id)->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_address');
    }

    /**
     * 预售商品下单流程
     */
    public function pre_sell_cart()
    {
        $act_id = I('act_id/d');
        $goods_num = I('goods_num/d');
        $address_id = I('address_id/d');
        if(empty($act_id)){
            $this->error('没有选择需要购买商品');
        }
        if(empty($goods_num)){
            $this->error('购买商品数量不能为0', U('Home/Activity/pre_sell', array('act_id' => $act_id)));
        }
        if($address_id){
            $address = M('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id'=>$this->user_id])->order(['is_default'=>'desc'])->find();
        }
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'pre_sell_cart','act_id'=>$act_id,'goods_num'=>$goods_num)));
            exit;
        }else{
            $this->assign('address',$address);
        }
        if($this->user_id == 0){
            $this->error('请先登录');
        }
        $pre_sell_info = M('goods_activity')->where(array('act_id' => $act_id, 'act_type' => 1))->find();
        if(empty($pre_sell_info)){
            $this->error('商品不存在或已下架',U('Home/Activity/pre_sell_list'));
        }
        $pre_sell_info = array_merge($pre_sell_info, unserialize($pre_sell_info['ext_info']));
        if ($pre_sell_info['act_count'] + $goods_num > $pre_sell_info['restrict_amount']) {
            $buy_num = $pre_sell_info['restrict_amount'] - $pre_sell_info['act_count'];
            $this->error('预售商品库存不足，还剩下' . $buy_num . '件', U('Home/Activity/pre_sell', array('id' => $act_id)));
        }
        $goodsActivityLogic = new GoodsActivityLogic();
        $pre_count_info = $goodsActivityLogic->getPreCountInfo($pre_sell_info['act_id'], $pre_sell_info['goods_id']);//预售商品的订购数量和订单数量
        $pre_sell_price['cut_price'] =$goodsActivityLogic->getPrePrice($pre_count_info['total_goods'], $pre_sell_info['price_ladder']);//预售商品价格
        $pre_sell_price['goods_num'] = $goods_num;
        $pre_sell_price['deposit_price'] = floatval($pre_sell_info['deposit']);
        // 提交订单
        if ($_REQUEST['act'] == 'submit_order') {
            $invoice_title = I('invoice_title'); // 发票
            $taxpayer  = I('taxpayer'); // 纳税识别号
            $address_id = I("address_id/d"); //  收货地址id
            if(empty($address_id)){
                exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
            }
            $orderLogic = new OrderLogic();
            $result = $orderLogic->addPreSellOrder($this->user_id, $address_id, $invoice_title, $act_id, $pre_sell_price,$taxpayer); // 添加订单
            exit(json_encode($result));
        }
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司
        $this->assign('pre_sell_info', $pre_sell_info);// 购物车的预售商品
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('pre_sell_price',$pre_sell_price);
        return $this->fetch();
    }

    /**
     * 兑换积分商品
     */
    public function buyIntegralGoods(){
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d');
        $goods_num = input('goods_num');
        $Integral = new Integral();
        $Integral->setUserById($this->user_id);
        $Integral->setGoodsById($goods_id);
        $Integral->setSpecGoodsPriceById($item_id);
        $Integral->setBuyNum($goods_num);
        try{
            $Integral->checkBuy();
            $url = U('Cart/integral', ['goods_id' => $goods_id, 'item_id' => $item_id, 'goods_num' => $goods_num]);
            $result = ['status' => 1, 'msg' => '购买成功', 'result' => ['url' => $url]];
            $this->ajaxReturn($result);
        }catch (TpshopException $t){
            $result = $t->getErrorArr();
            $this->ajaxReturn($result);
        }
    }

    /**
     *  积分商品结算页
     * @return mixed
     */
    public function integral(){
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d');
        $goods_num = input('goods_num/d');
        $address_id = input('address_id/d');
        if(empty($this->user)){
            $this->error('请登录');
        }
        if(empty($goods_id)){
            $this->error('非法操作');
        }
        if(empty($goods_num)){
            $this->error('购买数不能为零');
        }
        $Goods = new Goods();
        $goods = $Goods->where(['goods_id'=>$goods_id])->find();
        if(empty($goods)){
            $this->error('该商品不存在');
        }
        if (empty($item_id)) {
            $goods_spec_list = SpecGoodsPrice::all(['goods_id' => $goods_id]);
            if (count($goods_spec_list) > 0) {
                $this->error('请传递规格参数');
            }
            $goods_price = $goods['shop_price'];
            //没有规格
        } else {
            //有规格
            $specGoodsPrice = SpecGoodsPrice::get(['item_id'=>$item_id,'goods_id'=>$goods_id]);
            if ($goods_num > $specGoodsPrice['store_count']) {
                $this->error('该商品规格库存不足，剩余' . $specGoodsPrice['store_count'] . '份');
            }
            $goods_price = $specGoodsPrice['price'];
            $this->assign('specGoodsPrice', $specGoodsPrice);
        }
        if($address_id){
            $address = Db::name('user_address')->where("address_id" , $address_id)->find();
        }else{
            $address = Db::name('user_address')->where(['user_id' => $this->user_id])->order(['is_default' => 'desc'])->find();
        }
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'integral','goods_id'=>$goods_id,'goods_num'=>$goods_num,'item_id'=>$item_id)));
            exit;
        }else{
            $this->assign('address',$address);
        }
        $point_rate = tpCache('shopping.point_rate');
        $backUrl = Url::build('Goods/goodsInfo',['id'=>$goods_id,'item_id'=>$item_id]);
        $this->assign('backUrl', $backUrl);
        $this->assign('point_rate', $point_rate);
        $this->assign('goods', $goods);
        $this->assign('goods_price', $goods_price);
        $this->assign('goods_num',$goods_num);
        return $this->fetch();
    }

    /**
     *  积分商品价格提交
     * @return mixed
     */
    public function integral2(){
        if ($this->user_id == 0){
            $this->ajaxReturn(['status' => -100, 'msg' => "登录超时请重新登录!", 'result' => null]);
        }
        $goods_id           = input('goods_id/d');
        $item_id            = input('item_id/d');
        $goods_num          = input('goods_num/d');
        $address_id         = input("address_id/d"); //  收货地址id
        $user_note          = input('user_note'); // 给卖家留言
        $invoice_title      = input('invoice_title'); // 发票
        $taxpayer           = input('taxpayer'); // 发票纳税人识别号
        $user_money         = input("user_money/f", 0); //  使用余额
        $payPwd                = input('payPwd');
        $integral = new Integral();
        $integral->setUserById($this->user_id);
        $integral->setGoodsById($goods_id);
        $integral->setBuyNum($goods_num);
        $integral->setSpecGoodsPriceById($item_id);
        $integral->setUserAddressById($address_id);
        $integral->useUserMoney($user_money);
        try{
            $integral->checkBuy();
            $pay = $integral->pay();
            // 提交订单
            if ($_REQUEST['act'] == 'submit_order') {
                $placeOrder = new PlaceOrder($pay);
                $placeOrder->setUserAddress($integral->getUserAddress());
                $placeOrder->setInvoiceTitle($invoice_title);
                $placeOrder->setUserNote($user_note);
                $placeOrder->setTaxpayer($taxpayer);
                $placeOrder->setPayPsw($payPwd);
                $placeOrder->addNormalOrder();
                $order = $placeOrder->getOrder();
                $this->ajaxReturn(['status'=>1,'msg'=>'提交订单成功','result'=>$order['order_id']]);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $pay->toArray()]);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }
	
	 /**
     *  获取发票信息
     * @date2017/10/19 14:45
     */
    public function invoice(){

        $map = [];
        $map['user_id']=  $this->user_id;
        
        $field=[          
            'invoice_title',
            'taxpayer',
            'invoice_desc',	
        ];
        
        $info = M('user_extend')->field($field)->where($map)->find();
        if(empty($info)){
            $result=['status' => -1, 'msg' => 'N', 'result' =>''];
        }else{
            $result=['status' => 1, 'msg' => 'Y', 'result' => $info];
        }
        $this->ajaxReturn($result);            
    }
     /**
     *  保存发票信息
     * @date2017/10/19 14:45
     */
    public function save_invoice(){     
        
        if(IS_AJAX){
            
            //A.1获取发票信息
            $invoice_title = trim(I("invoice_title"));
            $taxpayer      = trim(I("taxpayer"));
            $invoice_desc  = trim(I("invoice_desc"));
            
            //B.1校验用户是否有历史发票记录
            $map            = [];
            $map['user_id'] =  $this->user_id;
            $info           = M('user_extend')->where($map)->find();
            
           //B.2发票信息
            $data=[];  
            $data['invoice_title'] = $invoice_title;
            $data['taxpayer']      = $taxpayer;  
            $data['invoice_desc']  = $invoice_desc;     
            
            //B.3发票抬头
            if($invoice_title=="个人"){
                $data['invoice_title'] ="个人";
                $data['taxpayer']      = "";
            }                              
            
            
            //是否存贮过发票信息
            if(empty($info)){   
                $data['user_id'] = $this->user_id;
                (M('user_extend')->add($data))?
                $status=1:$status=-1;                
             }else{
                (M('user_extend')->where($map)->save($data))?
                $status=1:$status=-1;                
            }            
            $result = ['status' => $status, 'msg' => '', 'result' =>''];           
            $this->ajaxReturn($result); 
            
        }      
    }
}
