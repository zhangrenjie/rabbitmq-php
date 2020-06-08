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
    $exchangeName = 'logs_system';
    $exchangeType = 'direct';
    $passive = false;
    $durable = false;
    $autoDelete = false;
    $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);

    //生产者将消息发给发送给交换机
    $logLevels = ['deprecate', 'notice', 'warning', 'error'];
    foreach ($logLevels as $level) {
        $routingKeyName = $level;//路由键名称

        //构造消息
        $content = 'The log level is ' . $level . PHP_EOL;
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];
        $message = new AMQPMessage($content, $properties);

        //发送消息
        $channel->basic_publish($message, $exchangeName, $routingKeyName);
    }

    //关闭通道与连接
    $channel->close();
    $connection->close();


} catch (\Throwable $e) {
    throw $e;
}

