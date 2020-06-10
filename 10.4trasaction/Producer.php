<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/10
 * Time: 下午2:09
 * 业务场景：演示订单在10分钟内没有支付，需要取消订单及还原库存
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

    //创建工作消息队列
    $queueName = 'test_queue_transaction';
    $passive = false;
    $durable = true;//持久化交换机
    $exclusive = false;
    $autoDelete = false;
    $noWait = false;

    //--定义工作消息队列参数
    $arguments = new AMQPTable();
    $arguments->set('x-message-ttl', 30000);//队列中订单消息600s过期
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete, $noWait, $arguments);

    //定义消息
    $content = json_encode([
        'order_no' => time() . rand(10, 99),
    ]);

    $message = new AMQPMessage($content, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//设置消息持久化
        'expiration' => 50000,//直接定义该条消息的生命周期，当队列设置了x-message-ttl，消息的生命周期为最小值
    ]);

    //发送消息
    $exchangeName = '';
    $routingKeyName = $queueName;

    //开启事物模式
    $channel->tx_select();
    try {
        $channel->basic_publish($message, $exchangeName, $routingKeyName);
        //throw new Exception('asd');//手动抛出异常测试事物

        //提交事物
        $channel->tx_commit();
    } catch (\Throwable $e) {
        $channel->tx_rollback();
        echo "消息发送失败" . PHP_EOL;
    }


    //关闭通道和连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}
