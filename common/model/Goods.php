<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\common\model;
use think\Db;
use think\Model;
class Goods extends Model {
    public function FlashSale()
    {
        return $this->hasOne('FlashSale','id','prom_id');
    }

    public function PromGoods()
    {
        return $this->hasOne('PromGoods','id','prom_id')->cache(true,10);
    }
    public function GroupBuy()
    {
        return $this->hasOne('GroupBuy','id','prom_id');
    }
    public function brand()
    {
        return $this->hasOne('brand','id','brand_id');
    }
    public function getDiscountAttr($value, $data)
    {
        if ($data['market_price'] == 0) {
            $discount = 10;
        } else {
            $discount = round($data['shop_price'] / $data['market_price'], 2) * 10;
        }
        return $discount;
    }
    /**
     * 获取商品评价
     * 好评数差评数中评数及其百分比,和总评数
     * @param $value
     * @param $data
     * @return array|false|\PDOStatement|string|Model
     */
    public function getCommentStatisticsAttr($value, $data)
    {
        $comment_where = ['is_show' => 1, 'goods_id' => $data['goods_id'], 'user_id' => ['gt', 0]]; //公共条件
        $field = "sum(case when img !='' and img not like 'N;%' then 1 else 0 end) as img_sum,"
            . "sum(case when goods_rank >= 4 and goods_rank <= 5 then 1 else 0 end) as high_sum," .
            "sum(case when goods_rank >= 3 and goods_rank <4 then 1 else 0 end) as center_sum," .
            "sum(case when goods_rank < 3 then 1 else 0 end) as low_sum,count(comment_id) as total_sum";
        $comment_statistics = Db::name('comment')->field($field)->where($comment_where)->group('goods_id')->find();
        if ($comment_statistics) {
            $comment_statistics['high_rate'] = ceil($comment_statistics['high_sum'] / $comment_statistics['total_sum'] * 100); // 好评率
            $comment_statistics['center_rate'] = ceil($comment_statistics['center_sum'] / $comment_statistics['total_sum'] * 100); // 好评率
            $comment_statistics['low_rate'] = ceil($comment_statistics['low_sum'] / $comment_statistics['total_sum'] * 100); // 好评率
        } else {
            $comment_statistics = ['img_sum' => 0, 'high_sum' => 0, 'high_rate' => 100, 'center_sum' => 0, 'center_rate' => 0, 'low_sum' => 0, 'low_rate' => 0, 'total_sum' => 0];
        }
        return $comment_statistics;
    }
    public function getPriceLadderAttr($value)
    {
        if(!empty($value)){
            return json_decode($value, true);
        }else {
            return $value;
        }
    }
}
