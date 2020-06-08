<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/4
 * Time: 下午9:07
 */
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

try {
    //直连模式
    $config = [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',

    ];

    //创建连接
    $connection = new AMQPStreamConnection(...array_values($config));

    //创建一个通道，复用connction连接
    $channel = $connection->channel();


    //通道绑定对应的消息队列
    $queueName = 'type1_list';
    $passive = false;//交换器不在事先被声明，如果其不存在将抛出错误。
    $durable = true;//队列是否持久化，true持久化（消息存磁盘，MQ重启队列不丢失，消息丢失），false不持久化（MQ服务重启队列与消息都丢失）
    $exclusive = false;//是否独占队列，true独占队列（仅允许当前连接可用），false非独占队列
    $autoDelete = true;//是否在消费完成后自动删除该队列，true自动删除，false不自动删除(注意：只有消费者消费完成切关闭连接才删除队列)
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete);

    //创建消息
    $content = 'Hello world , This is type 1 ,' . date('Y-m-d H:i:s');
    $properties = [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//持久化存储消息
    ];
    $message = new AMQPMessage($content, $properties);

    //通过通道发送消息
    $exchangeName = '';//交换机名称
    $routingKeyName = $queueName;//routing_key名称
    $channel->basic_publish($message, $exchangeName, $routingKeyName);

    echo "直连发送成功" . PHP_EOL;

    //关闭通道和连接
    $channel->close();
    $connection->close();
} catch (\Throwable $e) {
    throw $e;
}



