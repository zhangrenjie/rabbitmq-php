<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/6
 * Time: 下午12:21
 */

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

try {
    //配置信息
    $config = [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
    ];

    //连接RabbitMQ服务端
    $connection = new AMQPStreamConnection(...array_values($config));

    //创建连接通道
    $channel = $connection->channel();

    //给通道绑定交换机，fanout广播模式中生产者只能将消息发送交换机
    $exchangeName = 'register';//交换机名称
    $exchangeType = 'fanout';//定义广播类型交换机
    $passive = false;
    $durable = true;
    $autoDelete = false;
    $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);


    for ($i = 1; $i <= 100; $i++) {
        //构造消息
        $content = 'This message ' . $i . ' is from the publish-subscribe mode .';
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//设置消息传递模式为持久化存储
        ];
        $message = new AMQPMessage($content, $properties);

        //生产者发送消息给交换机
        $routingKey = '';//广播时候不需要指定routing_key，交换机会将消息推送给所有绑定的队列。
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }


    //关闭通道与连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}