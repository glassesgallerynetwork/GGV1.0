<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace app\mobile\controller;

use app\common\logic\CouponLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\logic\team\TeamOrder;
use app\common\model\Goods;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\TeamActivity;
use app\common\model\TeamFound;
use app\common\util\TpshopException;
use think\Db;
use think\Page;


class Team extends MobileBase
{
    public $user_id = 0;
    public $user = array();
    /**
     * 构造函数
     */
    public function  __construct()
    {
        parent::__construct();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
    }

    /**
     * 拼团首页
     * @return mixed
     */
    public function index()
    {
        $goods_category = Db::name('goods_category')->where(['level' => 1, 'is_show' => 1])->select();
        $this->assign('goods_category', $goods_category);
        return $this->fetch();
    }

    public function category()
    {
        $id = input('id/d');//一级分类ID
        $tid = input('tid/d');//二级分类ID
        $two_all_ids = input('tid/s');//二级分类全部id
        $goods_category_level_one = Db::name('goods_category')->where(['id' => $id])->find();
        $goods_category_level_two = Db::name('goods_category')->where(['parent_id' => $goods_category_level_one['id']])->select();//二级分类
        $goods_where = ['cat_id1' => $id];
        if($tid){
            $goods_where['cat_id2'] = $tid;
        }
        if($goods_category_level_two){
            $goods_category_level_two_arr = get_arr_column($goods_category_level_two,'id');
            $two_all_ids = implode(',',$goods_category_level_two_arr);
        }
        $this->assign('goods_category_level_one', $goods_category_level_one);
        $this->assign('goods_category_level_two', $goods_category_level_two);
        $this->assign('two_all_ids', $two_all_ids);
        return $this->fetch();
    }


    /**
     * 拼团首页列表
     */
    public function AjaxTeamList(){
        $p = Input('p',1);
        $id = input('id/d');//一级分类ID
        $tid = input('tid/d');//二级分类ID
        $two_all_ids = input('two_all_ids/s');//二级分类全部id
        $goods_where = [];
        if($id && $two_all_ids){
            $category_three_ids = Db::name('goods_category')->where(['parent_id' => ['in',$two_all_ids]])->getField('id',true);//三级分类id
            $goods_where['cat_id'] = ['in',$category_three_ids];
        }
        if($tid){
            $category_three_ids = Db::name('goods_category')->where(['parent_id' => $tid])->getField('id',true);//三级分类id
            $goods_where['cat_id'] = ['in',$category_three_ids];
        }
        $team_where = ['t.status' => 1, 't.is_lottery' => 0, 'g.is_on_sale' => 1];
        if(count($goods_where) > 0){
            $goods_ids = Db::name('goods')->where($goods_where)->getField('goods_id', true);
            if(!empty($goods_ids)){
                $team_where['t.goods_id'] = ['IN', $goods_ids];
            }else{
                $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>'']);
            }
        }
        $TeamActivity = new TeamActivity();
        $list = $TeamActivity->field('t.*')->alias('t')->join('__GOODS__ g', 'g.goods_id = t.goods_id')
            ->with([
                'goods'=>function($query) {
                    $query->field('goods_id,goods_name,shop_price');
                },
                'specGoodsPrice'=>function($query) {
                    $query->field('item_id,price');
                }
            ])
            ->where($team_where)->group('t.goods_id')->order('t.team_id desc')->page($p, 10)->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$list]);
    }

    public function info(){
        $team_id = input('team_id');
        $goods_id = input('goods_id');
        if(empty($goods_id)){
            $this->error('参数错误', U('Mobile/Team/index'));
        }
        $TeamActivity = new TeamActivity();
        $Goods = new Goods();
        $goods = $Goods->where(['is_on_sale'=>1,'goods_id'=>$goods_id])->find();
        $teamList = $TeamActivity->where('goods_id', $goods_id)->select();
        if (empty($teamList)) {
            $this->error('该商品拼团活动不存在或者已被删除', U('Mobile/Team/index'));
        }
        if(empty($goods)){
            $this->error('此商品不存在或者已下架', U('Mobile/Team/index'));
        }
        foreach($teamList as $teamKey=>$teamVal){
            if($team_id && $teamVal['team_id'] == $team_id){
                $team = $teamVal;
                break;
            }
        }
        if(empty($team)){
            $team = $teamList[0];
        }
        $user_id = cookie('user_id');
        if($user_id){
            $collect = Db::name('goods_collect')->where(array("goods_id"=>$goods_id ,"user_id"=>$user_id))->count();
            $this->assign('collect',$collect);
        }
        $spec_goods_price = Db::name('spec_goods_price')->where("goods_id",$goods_id)->getField("key,price,store_count,item_id,prom_id"); // 规格 对应 价格 库存表
        if($spec_goods_price){
            foreach($spec_goods_price as $specKey=>$specVal){
                $spec_goods_price[$specKey]['team_id'] = 0;
                $spec_goods_price[$specKey]['key_array'] = explode('_', $spec_goods_price[$specKey]['key']);
                foreach($teamList as $teamKey=>$teamVal){
                    if($specVal['item_id'] == $teamVal['item_id'] && $specVal['prom_id'] == $teamVal['team_id'] && $teamVal['status'] == 1){
                        $spec_goods_price[$specKey]['team_id'] = $teamVal['team_id'];
                        continue;
                    }
                }
            }
        }
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true));
        $goods_images_list = Db::name('goods_images')->where("goods_id" , $goods_id)->select(); // 商品图册
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $goodsLogic = new GoodsLogic();
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $filter_spec = $goodsLogic->get_spec($goods_id);
        $this->assign('filter_spec', $filter_spec);//规格参数
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('goods',$goods);
        $this->assign('team', $team);//商品拼团活动主体
        $this->assign('team_id', $team_id);//商品拼团活动主体
        return $this->fetch();
    }

    public function ajaxCheckTeam(){
        $team_id = input('team_id');
        $goods_id = input('goods_id');
        if(empty($goods_id) || empty($team_id) ){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
        }
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->append(['bd_url,front_status_desc,bd_pic,lottery_url'])->with('specGoodsPrice,goods')->where('team_id',$team_id)->find();
        if (empty($team)) {
            $this->ajaxReturn(['status'=>0,'msg'=>'该商品拼团活动不存在或者已被删除']);
        }
        if(empty($team['goods'])){
            $this->ajaxReturn(['status'=>0,'msg'=>'此商品不存在或者已下架']);
        }
        $teamInfo = $team->append(['bd_url','front_status_desc','bd_pic'])->toArray();
        $this->ajaxReturn(['status'=>1,'msg'=>'此商品拼团活动可以购买','result'=>['team'=>$teamInfo]]);

    }

    public function ajaxTeamFound()
    {
        $goods_id = input('goods_id');
        $TeamActivity = new TeamActivity();
        $TeamFound = new TeamFound();
        $team_ids = $TeamActivity->where(['goods_id'=>$goods_id,'status'=>1,'is_lottery'=>0])->getField('team_id',true);
        //活动正常，抽奖团未开奖才获取商品拼团活动拼单
        if (count($team_ids) > 0) {
            $teamFounds = $TeamFound->with('order,teamActivity')->where(['team_id' => ['IN',$team_ids], 'status' => 1])->order('found_id desc')->select();
            if($teamFounds) {
                $teamFounds = collection($teamFounds)->append(['surplus'])->toArray();
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => ['teamFounds' => $teamFounds]]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有相关记录', 'result' => []]);
        }
    }

    /**
     * 下单
     */
    public function addOrder()
    {
        C('TOKEN_ON', false);
        $team_id = input('team_id/d');
        $goods_num = input('goods_num/d');
        $found_id = input('found_id/d');//拼团id，有此ID表示是团员参团,没有表示团长开团
        if ($this->user_id == 0) {
            $this->ajaxReturn(['status' => -101, 'msg' => '购买拼团商品必须先登录', 'result' => '']);
        }
        if (empty($team_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status' => 0, 'msg' => '至少购买一份', 'result' => '']);
        }
        $team = new \app\common\logic\team\Team();
        $team->setUserById($this->user_id);
        $team->setTeamActivityById($team_id);
        $team->setTeamFoundById($found_id);
        $team->setBuyNum($goods_num);
        try{
            $team->buy();
            $teamActivity = $team->getTeamActivity();
            $goods = $team->getTeamBuyGoods();
            $goodsList[0] = $goods;
            $pay = new Pay();
            $pay->setUserId($this->user_id);
            $pay->payGoodsList($goodsList);
            $placeOrder = new PlaceOrder($pay);
            $placeOrder->addTeamOrder($teamActivity);
            $order = $placeOrder->getOrder();
            $team->log($order);
            $this->ajaxReturn(['status' => 1, 'msg' => '提交拼团订单成功', 'result' => ['order_id' => $order['order_id']]]);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /**
     * 结算页
     * @return mixed
     */
    public function order()
    {
        $order_id = input('order_id/d',0);
        $address_id = input('address_id/d');
        if(empty($this->user_id)){
            $this->redirect("User/login");
            exit;
        }
        $Order = new Order();
        $OrderGoods = new OrderGoods();
        $order = $Order->where(['order_id'=>$order_id,'user_id'=>$this->user_id])->find();
        if(empty($order)){
            $this->error('订单不存在或者已取消', U("Mobile/Order/order_list"));
        }
        if ($address_id) {
            $address_where = ['address_id' => $address_id];
        } else {
            $address_where = ["user_id" => $this->user_id];
        }
        $address = Db::name('user_address')->where($address_where)->order(['is_default'=>'desc'])->find();
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'team','order_id'=>$order_id)));
            exit;
        }else{
            $this->assign('address',$address);
        }
        $order_goods = $OrderGoods->with('goods')->where(['order_id' => $order_id])->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
        }
        if($order['order_status'] == 3 ){   //订单已经取消
            $this->error('订单已取消',U("Mobile/Order/order_list"));
        }
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $plugin_where = ['type'=>'payment','status'=>1,'code'=>'weixin'];
        }else{
            $plugin_where = ['type'=>'payment','status'=>1,'scene'=>1];
        }
        $pluginList = Db::name('plugin')->where($plugin_where)->select();
        $paymentList = convert_arr_key($pluginList, 'code');
        //不支持货到付款
        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            //判断当前浏览器显示支付方式
            if (($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())) {
                unset($paymentList[$key]);
            }
        }
        //订单没有使用过优惠券
        if($order['coupon_price'] <= 0){
            $couponLogic = new CouponLogic();
            $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$order_goods['goods_id']], [$order_goods['goods']['cat_id']]);//用户可用的优惠券列表
            $team = new \app\common\logic\team\Team();
            $team->setOrder($order);
            $userCartCouponList = $team->getCouponOrderList($userCouponList);
            $this->assign('userCartCouponList', $userCartCouponList);
        }
        $this->assign('paymentList', $paymentList);
        $this->assign('order', $order);
        $this->assign('order_goods', $order_goods);
        return $this->fetch();
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo()
    {
        $order_id       = input('order_id/d');
        $goods_num      = input('goods_num/d');
        $coupon_id      = input('coupon_id/d');
        $address_id     = input('address_id/d');
        $user_money     = input('user_money/f');
        $pay_points     = input('pay_points/d');
        $payPwd        = trim(input("payPwd")); //  支付密码
        $user_note      = trim(input("user_note")); //  用户备注
        $act            = input('post.act','');
        if(empty($this->user_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'登录超时','result'=>['url'=>U("User/login")]]);
        }
        if(empty($order_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误','result'=>[]]);
        }
        try{
            $teamOrder = new TeamOrder($this->user_id, $order_id);
            $teamOrder->changNum($goods_num);//更改数量
            $teamOrder->pay();//获取订单结账信息
            $teamOrder->useUserAddressById($address_id);//设置配送地址
            $teamOrder->useCouponById($coupon_id);//使用优惠券
            $teamOrder->useUserMoney($user_money);//使用余额
            $teamOrder->usePayPoints($pay_points);//使用积分
            $order = $teamOrder->getOrder();//获取订单信息
            $orderGoods = $teamOrder->getOrderGoods();//获取订单商品信息
            if ($act == 'submit_order') {
                $teamOrder->setUserNote($user_note);//设置用户备注
                $teamOrder->setPayPsw($payPwd);//设置支付密码
                $teamOrder->submit();//确认订单
                $this->ajaxReturn(['status' => 1, 'msg' => '提交成功', 'result' => ['order_amount'=>$order['order_amount']]]);
            }else{
                $couponLogic = new CouponLogic();
                $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$orderGoods['goods_id']], [$orderGoods['goods']['cat_id']]);//用户可用的优惠券列表
                $team = new \app\common\logic\team\Team();
                $team->setOrder($order);
                $userCartCouponList = $team->getCouponOrderList($userCouponList);
                $result = [
                    'order'=>$order,
                    'order_goods'=>$orderGoods,
                    'couponList'=>$userCartCouponList
                ];
                $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $result]);
            }
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }

    }

    /**
     * 拼团分享页
     * @return mixed
     */
    public function found()
    {
        $found_id = input('id');
        if (empty($found_id)) {
            $this->error('参数错误', U('Mobile/Team/index'));
        }
        $teamFound = TeamFound::get($found_id);
        $teamFollow = $teamFound->teamFollow()->where('status','IN', [1,2])->select();
        $team = $teamFound->teamActivity;

        if(time() - $teamFound['found_time'] > $team['time_limit']){
            //时间到了
            if($teamFound['join'] < $teamFound['need']){
                //人数没齐
                $teamFound->status = 3;//成团失败
                $teamFound->save();
                //更新团员成团失败
                Db::name('team_follow')->where(['found_id'=>$found_id,'status'=>1])->update(['status'=>3]);
            }
        }
        $this->assign('teamFollow', $teamFollow);//团员
        $this->assign('team', $team);//活动
        $this->assign('teamFound', $teamFound);//团长
        return $this->fetch();
    }

    public function ajaxGetMore(){
        $p = input('p/d',0);
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->with('goods')->where(['status'=>1])->page($p,4)->order(['is_recommend'=>'desc','sort'=>'desc'])->select();
        if(empty($team)){
            $this->ajaxReturn(['status'=>0,'msg'=>'已显示完所有记录']);
        }else{
            $result = collection($team)->append(['virtual_sale_num'])->toArray();
            $this->ajaxReturn(['status'=>1,'msg'=>'','result'=>$result]);
        }
    }

    public function lottery(){
        $team_id = input('team_id/d',0);
        $team_lottery = Db::name('team_lottery')->where('team_id',$team_id)->select();
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->with('specGoodsPrice,goods')->where('team_id',$team_id)->find();
        $this->assign('team',$team);
        $this->assign('team_lottery',$team_lottery);
        return $this->fetch();
    }

}