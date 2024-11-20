<?php

namespace app\test\controller;

use think\Controller;
use think\queue\Job;

//处理消息队列任务的控制器(出列的任务要跟数据库进行交互的控制器)
class Action extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        echo "【处理任务】" . date("Y-m-d H:i:s", time()) . "\n";
        // 如有必要,可以根据业务需求和数据库中的最新数据,判断该任务是否仍有必要执行.
        $check = $this->CheckStock($data);

        if (!$check) //没有库存了，就删除队列
        {
            $job->delete();
            return;
        }

        //插入订单数据库操作
        $result = $this->OrderDone($data);

        if ($result) {
            echo "已下单完成，删除队列任务\n";
            //如果任务执行成功， 记得删除任务
            $job->delete();
        } else {
            //通过这个方法可以检查这个任务已经重试了几次了
            if ($job->attempts() > 3) {
                echo "任务失败\n";
                $job->delete();

                // 也可以重新发布这个任务
                //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
            }
        }
    }


    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function CheckStock($data)
    {
        return true; //如果有库存就返回true，如果没有库存就返回false
    }

    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function OrderDone($data)
    {
        echo "正在下单中....\n";
        return true; //true:下单成功 false:下单失败
    }
}
