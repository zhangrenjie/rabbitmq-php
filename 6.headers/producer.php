<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/8
 * Time: 下午2:25
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
    $exchangeName = 'log_system_headers';
    $exchangeType = 'headers';
    $passive = false;
    $durable = true;//持久化
    $autoDelete = false;
    $exchange = $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);

    //定义路由键
    $routingKeyName = '';//headers模式下，routing_key为空


    //构造消息
    $content1 = 'This is a log message1 from the exchange which named \'log_system_headers\'';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//投递模式
        'headers' => [//设置消息的header
            'type' => 'log',
            'level' => 'error',
        ],
    ];
    $message1 = new AMQPMessage($content1, $properties);

    //构造消息
    $content2 = 'This is a log message2 from the exchange which named \'log_system_headers\'';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//投递模式
        'headers' => [//设置消息的header
            'type' => 'log',
            'level' => 'notice',
        ],
    ];
    $message2 = new AMQPMessage($content2, $properties);

    //构造消息
    $content3 = 'This is a log message3 from the exchange which named \'log_system_headers\'';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//投递模式
        'headers' => [//设置消息的header
            'type' => 'log',
            'level' => 'warning',
        ],
    ];
    $message3 = new AMQPMessage($content3, $properties);

    //生产者将消息发到到交换机
    $channel->basic_publish($message1, $exchangeName, $routingKeyName);
    $channel->basic_publish($message2, $exchangeName, $routingKeyName);

    //关闭连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}

