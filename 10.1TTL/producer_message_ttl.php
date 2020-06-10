<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/5
 * Time: 下午2:22
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
    $channel->basic_qos(null, 1, null);//通道每次只处理一条消息


    //通道绑定队列
    $queueName = 'message_message_ttl';
    $passive = false;
    $durable = true;//是否持久化队列，服务重启队列不会丢失
    $exclusive = false;//是否独占队列，独占队列只允许当前当前连接使用
    $autoDelete = false;//队列消费完是否自动删除（true当消费者断开服务连接后才会自动删除）
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete);


    for ($i = 1; $i <= 1000; $i++) {
        //构造消息
        $content = $i . ' -> Hello world , This is rabbitmq work queues model ' . date('Y-m-d H:i:s');

        $properties = [
            //消息传递模式
            'delivery_model' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,//持久化
            //设置消息的生命周期
            'expiration' => 60000,
        ];
        $message = new AMQPMessage($content, $properties);

        //发送消息
        $exchangeName = '';//交换机名称，空则为系统的默认交换机
        $routingKeyName = $queueName;//发送消息必须通过交换机指定routing_key发送。
        $channel->basic_publish($message, $exchangeName, $routingKeyName);
    }

    echo "消息发送成功" . PHP_EOL;

    //关闭通道
    $channel->close();

    //关闭连接
    $connection->close();
} catch (\Throwable $e) {
    throw $e;
}

