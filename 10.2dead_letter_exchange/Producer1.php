<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/10
 * Time: 下午2:09
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

    //定义死信交换机和死信队列
    $dlxExchangeName = 'dxl_exchange_1';
    $dlxExchangeType = \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT;
    $passive = false;
    $durable = true;//持久化
    $autoDelete = false;
    $channel->exchange_declare($dlxExchangeName, $dlxExchangeType, $passive, $durable, $autoDelete);

    //定义死信队列
    $dlxQueueName = 'dxl_message_queue_1';
    $passive = false;
    $durable = true;//持久化交换机
    $exclusive = false;
    $autoDelete = false;
    $noWait = false;
    $channel->queue_declare($dlxQueueName, $passive, $durable, $exclusive, $autoDelete, $noWait);

    //绑定死信队列到死信交换机
    $channel->queue_bind($dlxQueueName, $dlxExchangeName, 'dlx_message');


    //创建工作消息队列
    $queueName = 'test_dlx_queue1';
    $passive = false;
    $durable = true;//持久化交换机
    $exclusive = false;
    $autoDelete = false;
    $noWait = false;
    //--定义工作消息队列参数
    $arguments = new AMQPTable();
    $arguments->set('x-message-ttl', 60000);//队列中消息60s过期
    $arguments->set('x-dead-letter-exchange', $dlxExchangeName);//给队列中的死信配置死信交换机
    $arguments->set('x-dead-letter-routing-key', 'dlx_message');//给队列中的死信配置死信的路由键

    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete, $noWait, $arguments);

    //定义消息
    $content = '该条消息已经在队列中过期，测试死信队列';
    $message = new AMQPMessage($content, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//设置消息持久化
        'expiration' => 50000,//直接定义该条消息的生命周期，当队列设置了x-message-ttl，消息的生命周期为最小值
    ]);

    //发送消息
    $exchangeName = '';
    $routingKeyName = $queueName;
    $channel->basic_publish($message, $exchangeName, $routingKeyName);

    //关闭通道和连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}
