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
 * Author: lhb
 * Date: 2017-05-15
 */

namespace app\common\logic\team;
use app\common\model\Order;
use app\common\model\TeamActivity;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use app\common\model\Users;
use app\common\util\TpshopException;
use think\Db;

/**
 * 拼团活动逻辑类
 */
class Team
{
    private $userId;
    private $user;
    private $teamActivity;
    private $teamId;
    private $foundId;
    private $teamFound;
    private $buyNum;
    private $order;

    private $teamGoods;//虚构的商品模型

    public function setTeamActivityById($team_id)
    {
        if($team_id > 0){
            $this->teamId = $team_id;
            $this->teamActivity = TeamActivity::get($team_id);
        }
    }

    public function getTeamActivity()
    {
        return $this->teamActivity;
    }

    public function setTeamFoundById($found_id)
    {
        if($found_id){
            $this->foundId = $found_id;
            $this->teamFound = TeamFound::get($this->foundId);
        }
    }

    public function setUserById($user_id)
    {
        if($user_id > 0){
            $this->userId = $user_id;
            $this->user = Users::get($user_id);
        }
    }

    public function setBuyNum($buy_num)
    {
        $this->buyNum = $buy_num;
    }

    /**
     * 设置order模型
     * @param $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * 拼团支付后操作
     * @throws \think\Exception
     */
    public function doOrderPayAfter(){
        $teamFound = TeamFound::get(['order_id' => $this->order['order_id']]);
        //团长的单
        if ($teamFound) {
            $teamFound->found_time = time();
            $teamFound->found_end_time = time() + intval($this->teamActivity['time_limit']);
            $teamFound->status = 1;
            $teamFound->save();
        }else{
            //团员的单
            $teamFollow = TeamFollow::get(['order_id' => $this->order['order_id']]);
            if($teamFollow){
                $teamFollow->status = 1;
                $teamFollow->save();
                //更新团长的单
                $teamFollow->team_found->join = $teamFollow['team_found']['join'] + 1;//参团人数+1
                //如果参团人数满足成团条件
                if($teamFollow->team_found->join >= $teamFollow->team_found->need){
                    $teamFollow->team_found->status = 2;//团长成团成功
                    //更新团员成团成功
                    Db::name('team_follow')->where(['found_id'=>$teamFollow->team_found->found_id,'status'=>1])->update(['status'=>2]);
                }
                $teamFollow->team_found->save();
            }

        }
    }

    /**
     * 过滤拼团订单能使用的优惠券列表
     * @param $userCouponList
     * @return array
     */
    public function getCouponOrderList($userCouponList)
    {
        $userCouponArray = collection($userCouponList)->toArray();
        $couponNewList = [];
        foreach ($userCouponArray as $couponKey => $couponItem) {
            if ($this->order['goods_price'] >= $userCouponArray[$couponKey]['coupon']['condition']) {
                $userCouponArray[$couponKey]['coupon']['able'] = 1;
            } else {
                $userCouponArray[$couponKey]['coupon']['able'] = 0;
            }
            $couponNewList[] = $userCouponArray[$couponKey];
        }
        return $couponNewList;
    }


    /**
     * 过滤拼团订单能使用的优惠券列表|api专用
     * @param $userCouponList
     * @return array
     */
    public function getCouponOrderAbleList($userCouponList)
    {
        $userCouponArray = collection($userCouponList)->toArray();
        $couponNewList = [];
        foreach ($userCouponArray as $couponKey => $couponItem) {
            if ($this->order['goods_price'] >= $userCouponArray[$couponKey]['coupon']['condition']) {
                $coupon = $userCouponArray[$couponKey]['coupon'];
                $coupon['id'] = $userCouponArray[$couponKey]['id'];
                $coupon['cid'] = $userCouponArray[$couponKey]['cid'];
                unset($coupon['goods_coupon']);
                $couponNewList[] = $coupon;
            }
        }
        return $couponNewList;
    }

    public function buy()
    {
        if (empty($this->teamActivity) || $this->teamActivity['status'] != 1) {
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => '']);
        }
        if ($this->teamActivity['is_lottery'] == 1) {
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该商品拼团活动已开奖', 'result' => '']);
        }
        $this->teamGoods = $goods = $this->teamActivity->goods;
        $spec_goods_price = $this->teamActivity->specGoodsPrice;
        if (empty($goods) || $goods['is_on_sale'] != 1 || $goods['prom_type'] != 6) {
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => '']);
        }
        if ($this->teamActivity['item_id'] > 0) {
            $this->teamGoods['spec_key'] = $spec_goods_price['key'];
            $this->teamGoods['spec_key_name'] = $spec_goods_price['key_name'];
            $this->teamGoods['sku'] = $spec_goods_price['sku'];
            $this->teamGoods['prom_id'] = $spec_goods_price['prom_id'];
            $this->teamGoods['prom_type'] = $spec_goods_price['prom_type'];
            $this->teamGoods['shop_price'] = $spec_goods_price['price'];
            if(empty($spec_goods_price) || $spec_goods_price['prom_type'] != 6){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => '']);
            }
            if($this->buyNum > $spec_goods_price['store_count']){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '商品库存仅剩余'.$spec_goods_price['store_count'], 'result' => '']);
            }
        }
        if($this->buyNum > $goods['store_count']){
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '商品库存仅剩余'.$goods['store_count'], 'result' => '']);
        }
        if ($this->buyNum <= 0) {
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '至少购买一份', 'result' => '']);
        }
        if ($this->teamActivity['buy_limit'] != 0 && $this->buyNum > $this->teamActivity['buy_limit']) {
            throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '购买数已超过该活动单次购买限制数(' . $this->teamActivity['buy_limit'] . ')', 'result' => '']);
        }
        if($this->foundId){
            if(empty($this->teamFound) || $this->teamFound['status'] != 1){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该拼单数据不存在或已失效', 'result' => '']);
            }
            if($this->teamFound['user_id'] == $this->userId){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '不能自己开团自己拼', 'result' => '']);
            }
            if($this->teamActivity['team_type'] == 2){
                //抽奖团，只能拼一次团
                $teamYouSelfFollow = Db::name('team_follow')->where(['follow_user_id' => $this->userId, 'team_id' => $this->teamId, 'status' => ['in', '1,2']])->find();
                if($teamYouSelfFollow){
                    throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '你已经参与过该拼团活动。', 'result' => '']);
                }
            }
            if($this->teamFound['team_id'] != $this->teamActivity['team_id']){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该拼单数据不存在或已失效', 'result' => '']);
            }
            if($this->teamFound['join'] >= $this->teamFound['need']){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该单已成功结束', 'result' => '']);
            }
            if(time() - $this->teamFound['found_time'] > $this->teamActivity['time_limit']){
                throw new TpshopException('拼团购买商品',0,['status' => 0, 'msg' => '该拼单已过期', 'result' => '']);
            }
        }
        $this->teamGoods['goods_price'] = $this->teamActivity['team_price'];
        $this->teamGoods['goods_num'] = $this->buyNum;
    }

    public function getTeamBuyGoods()
    {
        return $this->teamGoods;
    }

    public function log(Order $order)
    {
        if($this->teamFound){
            /**团员拼团s**/
            $team_follow_data = [
                'follow_user_id' => $this->userId,
                'follow_user_nickname' => $this->user['nickname'],
                'follow_user_head_pic' => $this->user['head_pic'],
                'follow_time' => time(),
                'order_id' => $order['order_id'],
                'found_id' => $this->teamFound['found_id'],
                'found_user_id' => $this->teamFound['user_id'],
                'team_id' => $this->teamActivity['team_id'],
            ];
            Db::name('team_follow')->insert($team_follow_data);
            /***团员拼团e***/
        }else{
            /***团长开团s***/
            $team_found_data = [
                'found_time'=>time(),
                'found_end_time' => time() + intval($this->teamActivity['time_limit']),
                'user_id' => $this->userId,
                'team_id' => $this->teamActivity['team_id'],
                'nickname' => $this->user['nickname'],
                'head_pic' =>  $this->user['head_pic'],
                'order_id' => $order['order_id'],
                'need' => $this->teamActivity['needer'],
                'price'=> $this->teamActivity['team_price'],
                'goods_price' => $this->teamGoods['shop_price'],
            ];
            Db::name('team_found')->insert($team_found_data);
            /***团长开团e***/
        }
    }



}