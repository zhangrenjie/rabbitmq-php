<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/5
 * Time: 下午3:40
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

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

    //通道绑定队列
    $queueName = 'work_queue';
    $passive = false;
    $durable = true;//是否持久化队列，服务重启队列不会丢失
    $exclusive = false;//是否独占队列，独占队列只允许当前当前连接使用
    $autoDelete = false;//队列消费完是否自动删除（true当消费者断开服务连接后才会自动删除）
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete);

    //通道每次只取一条消息
    $prefetchSize = null;//最大unacked消息的字节数；
    $prefetchCount = 1;//最大unacked消息的条数；
    $global = null;//上述限制的限定对象，false=限制单个消费者；true=限制整个信道
    $channel->basic_qos($prefetchSize, $prefetchCount, $global);

    //消费消息
    $callback = function (\PhpAmqpLib\Message\AMQPMessage $message) {
        echo $message->body . PHP_EOL;

        //消费完成，手动确认消费信息。
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };


    $consumerTag = '';
    $noLocal = false;
    $noAck = false;//关闭自动确认消费消息
    $exclusive = false;
    $noWait = false;

    $channel->basic_consume($queueName, $consumerTag, $noLocal, $noAck, $exclusive, $noWait, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    //关闭通道
    $channel->close();

    //关闭连接
    $connection->close();


} catch (\Throwable $e) {
    throw $e;
}