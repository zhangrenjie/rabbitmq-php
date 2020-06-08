<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/7
 * Time: 上午12:57
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
    $exchangeName = 'log_system_topic';
    $exchangeType = \PhpAmqpLib\Exchange\AMQPExchangeType::TOPIC;
    $passive = false;
    $durable = false;
    $autoDelete = false;
    $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);

    //通道每次只取一条消息
    $channel->basic_qos(null, 1, null);

    //创建临时队列
    $queueName = '';
    $tmpQueueName = $channel->queue_declare($queueName)[0] ?? '';


    //绑定队列到交换机
    $routingKeyName = 'log.error.#';
    $channel->queue_bind($tmpQueueName, $exchangeName, $routingKeyName);

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
    throw $e;
}
