<?php

namespace app\test\controller;

use think\Controller;

//引入消息队列的类文件
use think\Queue;

//引入处理逻辑的控制器
use app\test\controller\Action;

//消息队列 发布任务控制器
class Index extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    //发布消息队列的任务
    public function push()
    {
        // 队列任务延迟执行的时间
        $QueueDelay = 2;

        // 队列处理类 得到命名空间类名
        $QueueHandle = Action::class;

        //队列任务传递的自定义数据
        $QueueData = ['busid' => '40', 'subid' => '1'];

        //队列的名称，如果队列不存在，就创建新队列
        $QueueName = "TestQueue";

        //延迟执行
        $result = Queue::later($QueueDelay, $QueueHandle, $QueueData, $QueueName);

        echo $result === FALSE ? "入列失败" : date("Y-m-d H:i:s") . " - 新任务入列成功\n";
    }
}
