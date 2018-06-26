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
 * Author: 当燃
 * Date: 2016-03-19
 */
namespace app\common\logic;

use app\common\util\TpshopException;
use think\Model;
use think\Db;

/**
 * 订单类
 * Class CatsLogic
 * @package Home\Logic
 */
class Order
{

    private $order;
    private $user_id = 0;

    public function setOrderById($order_id)
    {
        $this->order = \app\common\model\Order::get($order_id);
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * 用户删除订单
     * @return array
     * @throws TpshopException
     */
    public function userDelOrder()
    {
        $validate = validate('order');
        $order_id = $this->order['order_id'];
        if (!$validate->scene('del')->check(['order_id' => $order_id])) {
            throw new TpshopException('用户删除订单', 0, ['status' => 0, 'msg' => $validate->getError()]);
        }
        if (empty($this->user_id)) {
            throw new TpshopException('用户删除订单', 0, ['status' => 0, 'msg' => '非法操作']);
        }
        $row = Db::name('order')->where(['user_id' => $this->user_id, 'order_id' => $order_id])->update(['deleted' => 1]);
        if (!$row) {
            Db::name('order_goods')->where(['order_id' => $order_id])->update(['deleted' => 1]);
            throw new TpshopException('用户删除订单', 0, ['status' => 0, 'msg' => '删除失败']);
        }
    }

    /**
     * 管理员删除订单
     * @return array
     * @throws \think\Exception
     */
    public function adminDelOrder()
    {
        Db::name('order_goods')->where('order_id',$this->order['order_id'])->delete();
        $this->order->delete();
    }

    /**
     * 订单操作记录
     * @param $action_note|备注
     * @param $status_desc|状态描述
     * @param $action_user
     * @return mixed
     */
    public function orderActionLog($action_note, $status_desc, $action_user = 0)
    {
        $data = [
            'order_id' => $this->order['order_id'],
            'action_user' => $action_user,
            'action_note' => $action_note,
            'order_status' => $this->order['order_status'],
            'pay_status' => $this->order['pay_status'],
            'log_time' => time(),
            'status_desc' => $status_desc,
            'shipping_status' => $this->order['shipping_status'],
        ];
        return Db::name('order_action')->add($data);//订单操作记录
    }
}