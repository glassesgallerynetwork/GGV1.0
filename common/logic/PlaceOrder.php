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
 * Author: dyr
 * Date: 2017-12-04
 */

namespace app\common\logic;
use app\common\model\CouponList;
use app\common\model\Order;
use app\common\model\TeamActivity;
use app\common\model\Users;
use app\common\util\TpshopException;
use think\Cache;
use think\Hook;
use think\Model;
use think\Db;
/**
 * 提交下单类
 * Class CatsLogic
 * @package Home\Logic
 */
class PlaceOrder
{
    private $invoiceTitle;
    private $userNote;
    private $taxpayer;
    private $pay;
    private $order;
    private $userAddress;
    private $payPsw;
    private $promType;
    private $promId;

    /**
     * PlaceOrder constructor.
     * @param Pay $pay
     */
    public function __construct(Pay $pay)
    {
        $this->pay = $pay;
        $this->order = new Order();
    }

    /**
     * 设置密码后加密
     * @param $payPsw
     */
    public function setPayPsw($payPsw)
    {
        $this->payPsw = $payPsw;
    }
    
    public function setInvoiceTitle($invoiceTitle)
    {
        $this->invoiceTitle = $invoiceTitle;
    }
    public function setUserNote($userNote)
    {
        $this->userNote = $userNote;
    }
    public function setTaxpayer($taxpayer)
    {
        $this->taxpayer = $taxpayer;
    }

    public function setUserAddress($userAddress)
    {
        $this->userAddress = $userAddress;
    }

    private function setPromType($prom_type)
    {
        $this->promType = $prom_type;
    }
    private function setPromId($prom_id)
    {
        $this->promId = $prom_id;
    }

    public function addNormalOrder()
    {
        $this->check();
        $this->queueInc();
        $this->addOrder();
        $this->addOrderGoods();
        Hook::listen('user_add_order', $this->order);//下单行为
        $reduce = tpCache('shopping.reduce');
        if($reduce== 1 || empty($reduce)){
            minus_stock($this->order);//下单减库存
        }
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
        if ($this->order['order_amount'] == 0) {
            update_pay_status($this->order['order_sn']);
        }
        $this->deductionCoupon();//扣除优惠券
        $this->changUserPointMoney($this->order);//扣除用户积分余额
        $this->queueDec();
    }

    public function addTeamOrder(TeamActivity $teamActivity)
    {
        $this->setPromType(6);
        $this->setPromId($teamActivity['team_id']);
        $this->check();
        $this->queueInc();
        $this->addOrder();
        $this->addOrderGoods();
        Hook::listen('user_add_order', $this->order);//下单行为
        if($teamActivity['team_type'] != 2){
            if(tpCache('shopping.reduce') == 1){
                minus_stock($this->order);//下单减库存
            }
        }
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
        if ($this->order['order_amount'] == 0) {
            update_pay_status($this->order['order_sn']);
        }
        $this->queueDec();
    }



    /**
     * 获取订单表数据
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * 提交订单前检查
     * @throws TpshopException
     */
    public function check()
    {
        $pay_points = $this->pay->getPayPoints();
        $user_money = $this->pay->getUserMoney();
        if ($pay_points || $user_money) {
            $user = $this->pay->getUser();
            if ($user['is_lock'] == 1) {
                throw new TpshopException('提交订单', 0, ['status'=>-5,'msg'=>"账号异常已被锁定，不能使用余额支付！",'result'=>'']);
            }
            if (empty($user['paypwd'])) {
                throw new TpshopException('提交订单', 0, ['status'=>-6,'msg'=>"请先设置支付密码",'result'=>'']);
            }
            if (empty($this->payPsw)) {
                throw new TpshopException('提交订单', 0, ['status'=>-7,'msg'=>"请输入支付密码",'result'=>'']);
            }
             if ($this->payPsw !== $user['paypwd'] && encrypt($this->payPsw) !== $user['paypwd']) {
                throw new TpshopException('提交订单', 0, ['status'=>-8,'msg'=>'支付密码错误','result'=>'']);
            }
        }

    }
    private function queueInc()
    {
        $queue = Cache::get('queue');
        if($queue >= 100){
            throw new TpshopException('提交订单', 0, ['status' => -99, 'msg' => "当前人数过多请耐心排队!" . $queue, 'result' => '']);
        }
        Cache::inc('queue');
    }

    /**
     * 订单提交结束
     */
    private function queueDec()
    {
        Cache::dec('queue');
    }

    /**
     * 插入订单表
     * @throws TpshopException
     */
    private function addOrder()
    {
        $OrderLogic = new OrderLogic();
        $user = $this->pay->getUser();

        $orderData = [
            'order_sn'         =>$OrderLogic->get_order_sn(), // 订单编号
            'user_id'          =>$user['user_id'], // 用户id
            'email'            =>$user['email'],//'邮箱'
            'invoice_title'    =>$this->invoiceTitle, //'发票抬头',
            'goods_price'      =>$this->pay->getGoodsPrice(),//'商品价格',
            'shipping_price'   =>$this->pay->getShippingPrice(),//'物流价格',
            'user_money'       =>$this->pay->getUserMoney(),//'使用余额',
            'coupon_price'     =>$this->pay->getCouponPrice(),//'使用优惠券',
            'integral'         =>$this->pay->getPayPoints(), //'使用积分',
            'integral_money'   =>$this->pay->getIntegralMoney(),//'使用积分抵多少钱',
            'total_amount'     =>$this->pay->getTotalAmount(),// 订单总额
            'order_amount'     =>$this->pay->getOrderAmount(),//'应付款金额',
            'add_time'         =>time(), // 下单时间
        ];
        if(!empty($this->userAddress)){
            $orderData['consignee'] = $this->userAddress['consignee'];// 收货人
            $orderData['province'] = $this->userAddress['province'];//'省份id',
            $orderData['city'] = $this->userAddress['city'];//'城市id',
            $orderData['district'] = $this->userAddress['district'];//'县',
            $orderData['twon'] = $this->userAddress['twon'];// '街道',
            $orderData['address'] = $this->userAddress['address'];//'详细地址'
            $orderData['mobile'] = $this->userAddress['mobile'];//'手机',
            $orderData['zipcode'] = $this->userAddress['zipcode'];//'邮编',
        }else{
            $orderData['consignee'] = $user['nickname'];// 收货人
            $orderData['mobile'] = $user['mobile'];//'手机',
        }
        if(!empty($this->userNote)){
            $orderData['user_note'] = $this->userNote;// 用户下单备注
        }
        if(!empty($this->taxpayer)){
            $orderData['taxpayer'] = $this->taxpayer; //'发票纳税人识别号',
        }
        $orderPromId =  $this->pay->getOrderPromId();
        $orderPromAmount =  $this->pay->getOrderPromAmount();
        if($orderPromId > 0){
            $orderData['order_prom_id'] = $orderPromId;//'订单优惠活动id',
            $orderData['order_prom_amount'] = $orderPromAmount;//'订单优惠活动金额,
        }
        if($this->promType){
            $orderData['prom_type'] = $this->promType;//订单类型
        }
        if($this->promId > 0){
            $orderData['prom_id'] = $this->promId;//活动id
        }
        if($orderData['integral'] > 0 || $orderData['user_money'] > 0){
            $orderData['pay_name'] = $orderData['user_money'] ? '余额支付' : '积分兑换';//支付方式，可能是余额支付或积分兑换，后面其他支付方式会替换
        }

        $this->order->data($orderData, true);
        $orderSaveResult = $this->order->save();
        if ($orderSaveResult === false) {
            throw new TpshopException("订单入库", 0, ['status' => -8, 'msg' => '添加订单失败', 'result' => '']);
        }
    }

    /**
     * 插入订单商品表
     */
    private function addOrderGoods()
    {
        if($this->pay->getOrderPromAmount() > 0) {
            $orderDiscounts = $this->pay->getOrderPromAmount() + $this->pay->getCouponPrice();  //整个订单优惠价钱
        }else{
            $orderDiscounts = $this->pay->getCouponPrice();  //整个订单优惠价钱
        }
        $payList = $this->pay->getPayList();
        $goods_ids = get_arr_column($payList,'goods_id');
        $goodsArr = Db::name('goods')->where('goods_id', 'IN', $goods_ids)->getField('goods_id,cost_price,give_integral');
        $orderGoodsAllData = [];
        foreach ($payList as $payKey => $payItem) {
            $totalPriceToRatio = $payItem['member_goods_price'] / $this->pay->getGoodsPrice();  //商品价格占总价的比例
            $finalPrice = round($payItem['member_goods_price'] - ($totalPriceToRatio * $orderDiscounts), 3);
            $orderGoodsData['order_id'] = $this->order['order_id']; // 订单id
            $orderGoodsData['goods_id'] = $payItem['goods_id']; // 商品id
            $orderGoodsData['goods_name'] = $payItem['goods_name']; // 商品名称
            $orderGoodsData['goods_sn'] = $payItem['goods_sn']; // 商品货号
            $orderGoodsData['goods_num'] = $payItem['goods_num']; // 购买数量
            $orderGoodsData['final_price'] = $finalPrice; // 每件商品实际支付价格
            $orderGoodsData['goods_price'] = $payItem['goods_price']; // 商品价               为照顾新手开发者们能看懂代码，此处每个字段加于详细注释
            if (!empty($payItem['spec_key'])) {
                $orderGoodsData['spec_key'] = $payItem['spec_key']; // 商品规格
                $orderGoodsData['spec_key_name'] = $payItem['spec_key_name']; // 商品规格名称
            } else {
                $orderGoodsData['spec_key'] = ''; // 商品规格
                $orderGoodsData['spec_key_name'] = ''; // 商品规格名称
            }
            $orderGoodsData['sku'] = $payItem['sku']; // sku
            $orderGoodsData['member_goods_price'] = $payItem['member_goods_price']; // 会员折扣价
            $orderGoodsData['cost_price'] = $goodsArr[$payItem['goods_id']]['cost_price']; // 成本价
            $orderGoodsData['give_integral'] = $goodsArr[$payItem['goods_id']]['give_integral']; // 购买商品赠送积分
            if ($payItem['prom_type']) {
                $orderGoodsData['prom_type'] = $payItem['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $orderGoodsData['prom_id'] = $payItem['prom_id']; // 活动id
            } else {
                $orderGoodsData['prom_type'] = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $orderGoodsData['prom_id'] = 0; // 活动id
            }
            array_push($orderGoodsAllData, $orderGoodsData);
        }
        Db::name('order_goods')->insertAll($orderGoodsAllData);
    }

    /**
     * 扣除优惠券
     */
    public function deductionCoupon()
    {
        $couponId = $this->pay->getCouponId();
        if($couponId > 0){
            $user = $this->pay->getUser();
            $couponList = new CouponList();
            $userCoupon = $couponList->where(['status'=>0,'id'=>$couponId])->find();
            if($userCoupon){
                $userCoupon->uid = $user['user_id'];
                $userCoupon->order_id = $this->order['order_id'];
                $userCoupon->use_time = time();
                $userCoupon->status =  1;
                $userCoupon->save();
                Db::name('coupon')->where('id', $userCoupon['cid'])->setInc('use_num');// 优惠券的使用数量加一
            }
        }
    }

    /**
     * 扣除用户积分余额
     * @param Order $order
     */
    public function changUserPointMoney(Order $order)
    {
        if($this->pay->getPayPoints() > 0 || $this->pay->getUserMoney() > 0){
            $user = $this->pay->getUser();
            $user = Users::get($user['user_id']);
            if($this->pay->getPayPoints() > 0){
                $user->pay_points = $user->pay_points - $this->pay->getPayPoints();// 消费积分
            }
            if($this->pay->getUserMoney() > 0){
                $user->user_money = $user->user_money - $this->pay->getUserMoney();// 抵扣余额
            }
            $user->save();
            $accountLogData = [
                'user_id' => $order['user_id'],
                'user_money' => -$this->pay->getUserMoney(),
                'pay_points' => -$this->pay->getPayPoints(),
                'change_time' => time(),
                'desc' => '下单消费',
                'order_sn'=>$order['order_sn'],
                'order_id'=>$order['order_id'],
            ];
            Db::name('account_log')->insert($accountLogData);
        }
    }
    /**
     * 这方法特殊，只限拼团使用。
     * @param $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}