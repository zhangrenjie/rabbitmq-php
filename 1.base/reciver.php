<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/4
 * Time: 下午9:53
 */
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

try {
    //直连模式消费消息
    $config = [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
    ];

    //创建连接
    $connection = new AMQPStreamConnection(...array_values($config));

    //创建通道
    $channel = $connection->channel();

    //通过通道绑定队列(消费者需要跟生产者的队列定义保持一致，因为生产者跟消费者操作的是同一个队列)
    $queueName = 'type1_list';
    $passive = false;//
    $durable = true;//是否持久化
    $exclusive = false;//是否独占使用当前队列，true只允许当前连接独占使用该队列
    $autoDelete = true;//队列是否在消费完成后自动删除队列
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete);

    //通道消费消息
    $consumerTag = '';
    $noLocal = false;
    $noAck = true;//是否开启消息自动确认
    $exclusive = false;
    $noWait = false;

    //消费消息时的回调
    $callback = function ($msg) {
        echo ' [x] Received ', $msg->body, "\n";
    };

    $channel->basic_consume($queueName, $consumerTag, $noLocal, $noAck, $exclusive, $noWait, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}
