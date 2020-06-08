<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/8
 * Time: 下午4:09
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;


try {
    $config = [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'passowrd' => 'guest',
        'vhost' => '/',
    ];

    //创建连接
    $connection = new AMQPStreamConnection(...array_values($config));

    //创建通道
    $channel = $connection->channel();

    //为通道创建交换机
    $exchangeName = 'log_system_headers';
    $exchangeType = 'headers';
    $passive = false;
    $durable = true;
    $autoDelete = false;
    $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);

    //通道每次只取一条消息
    $channel->basic_qos(null, 1, null);

    //创建临时队列
    $queueName = '';
    $tmpQueueName = $channel->queue_declare($queueName)[0] ?? '';

    //绑定队列到交换机
    $routingKeyName = '';
    $noWait = false;
    $arguments = [
        'x-match' => 'all',//all是完全匹配，any是只要满足任意一个条件
        'type' => 'log',
        'level' => 'error',
    ];
    $channel->queue_bind($tmpQueueName, $exchangeName, $routingKeyName, $noWait, $arguments);

    //消费消息
    $callback = function (\PhpAmqpLib\Message\AMQPMessage $message) {
        echo "处理错误消息:" . $message->body;
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };

    $consumerTag = '';
    $noLocal = false;
    $noAck = false;//关闭自动消息确认，消费完成后手动确认消费消息。
    $exclusive = false;
    $noWait = false;
    $channel->basic_consume($tmpQueueName, $consumerTag, $noLocal, $noAck, $exclusive, $noWait, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    //echo $e->getFile().$e->getLine();
    throw $e;
}