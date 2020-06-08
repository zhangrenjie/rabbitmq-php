<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/7
 * Time: 上午12:11
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
    $exchangeName = 'log_system_topic';
    $exchangeType = 'topic';
    $passive = false;
    $durable = false;//持久化
    $autoDelete = false;
    $exchange = $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);

    //定义路由键
    $routingKeyName = 'log.notice.info';

    //构造消息
    $content = 'This is a log message [' . $routingKeyName . ']';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ];
    $message = new AMQPMessage($content, $properties);

    //生产者将消息发到到交换机
    $channel->basic_publish($message, $exchangeName, $routingKeyName);

    //关闭连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}

