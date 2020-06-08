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
    $exchangeType = \PhpAmqpLib\Exchange\AMQPExchangeType::HEADERS;
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
    ];
    $message1 = new AMQPMessage($content1, $properties);

    //设置消息的header
    $headerInfo = [
        'type' => 'log',
        'level' => 'error',
    ];
    $headers = new \PhpAmqpLib\Wire\AMQPTable($headerInfo);
    $headers->set('shortshort', -5, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORTSHORT);
    $headers->set('short', -1024, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORT);
    $message1->set('application_headers', $headers);

    //构造消息
    $content2 = 'This is a log message2 from the exchange which named \'log_system_headers\'';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//投递模式
    ];
    $message2 = new AMQPMessage($content2, $properties);

    //设置消息的header
    $headerInfo = [
        'type' => 'log',
        'level' => 'notice',
    ];
    $headers = new \PhpAmqpLib\Wire\AMQPTable($headerInfo);
    $headers->set('shortshort', -5, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORTSHORT);
    $headers->set('short', -1024, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORT);
    $message2->set('application_headers', $headers);


    //构造消息
    $content3 = 'This is a log message3 from the exchange which named \'log_system_headers\'';
    $properties = [
        'dilivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//投递模式
    ];
    $message3 = new AMQPMessage($content3, $properties);

    //设置消息的header
    $headerInfo = [
        'type' => 'log',
        'level' => 'warning',
    ];
    $headers = new \PhpAmqpLib\Wire\AMQPTable($headerInfo);
    $headers->set('shortshort', -5, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORTSHORT);
    $headers->set('short', -1024, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORT);
    $message3->set('application_headers', $headers);


    //生产者将消息发到到交换机
    $channel->basic_publish($message1, $exchangeName, $routingKeyName);
    $channel->basic_publish($message2, $exchangeName, $routingKeyName);
    $channel->basic_publish($message3, $exchangeName, $routingKeyName);

    //关闭连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}

