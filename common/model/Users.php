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

class Users extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    /**
     * 用户下线分销金额
     * @param $value
     * @param $data
     * @return float|int
     */
    public function getRebateMoneyAttr($value, $data){
        $sum_money = DB::name('rebate_log')->where(['status' => 3,'buy_user_id'=>$data['user_id']])->sum('money');
        $rebate_money = empty($sum_money) ? (float)0 : $sum_money;
        return  $rebate_money;
    }

}
