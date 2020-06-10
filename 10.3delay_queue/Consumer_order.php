<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/10
 * Time: 下午2:09
 * 业务场景：演示订单在10分钟内没有支付，需要取消订单及还原库存
 * 消费
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


try {
    $config = [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
    ];

    //连接RabbitMQ服务
    $connection = new AMQPStreamConnection(...array_values($config));

    //创建通道
    $channel = $connection->channel();

    //每次只取一条消息
    $channel->basic_qos(null, 1, null);

    //定义死信交换机和死信队列
    $dlxExchangeName = 'dlx_delay_order_exchange';
    $dlxExchangeType = \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT;
    $passive = false;
    $durable = true;//持久化
    $autoDelete = false;
    $noWait = false;
    $channel->exchange_declare($dlxExchangeName, $dlxExchangeType, $passive, $durable, $autoDelete, $noWait);


    //定义死信队列
    $dlxQueueName = 'dlx_delay_order_queue';
    $passive = false;
    $durable = true;//持久化交换机
    $exclusive = false;
    $autoDelete = false;
    $noWait = false;
    $channel->queue_declare($dlxQueueName, $passive, $durable, $exclusive, $autoDelete, $noWait);


    //绑定死信队列到死信交换机
    $channel->queue_bind($dlxQueueName, $dlxExchangeName, 'dlx_order_message');


    //消费消息
    $callback = function (AMQPMessage $message) {
        $orderNo = json_decode($message->body, true)['order_no'] ?? '';

        //检查支付状态
        $payStatus = rand(0, 1);
        if (!$payStatus) {
            echo "订单：" . $orderNo . "支付失败" . PHP_EOL;
            echo "订单：" . $orderNo . "被取消，库存被还原" . PHP_EOL.PHP_EOL;
        } else {
            echo "订单：" . $orderNo . "支付成功" . PHP_EOL.PHP_EOL;
        }
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };

    $channel->basic_consume($dlxQueueName, '', false, false, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}
