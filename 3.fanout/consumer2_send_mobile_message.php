<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/6
 * Time: 下午12:21
 */

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

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

    //给通道创建交换机(fanout广播模式中生产者只能将消息发送交换机),也可以绑定交换机
    $exchangeName = 'register';//交换机名称
    $exchangeType = \PhpAmqpLib\Exchange\AMQPExchangeType::FANOUT;//定义广播类型交换机
    $passive = false;//是否检测同名队列
    $durable = true;
    $autoDelete = false;
    $channel->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete);
    $channel->basic_qos(null, 1, null);

    //创建临时队列
    $queueName = '';//临时队列不需要指定队列名字
    $passive = false;//是否检测同名队列
    $durable = false;//持久化
    $autoDelete = false;
    $noWait = false;
    $tmpQueueName = $channel->queue_declare($queueName, $passive, $durable, $autoDelete, $noWait)[0];

    //给交换机绑定队列
    $routingkeyName = '';
    $channel->queue_bind($tmpQueueName, $exchangeName, $routingkeyName);

    //消费消息
    $callback = function (\PhpAmqpLib\Message\AMQPMessage $message) {
        sleep(2);
        echo "消费者2发送手机短信：" . $message->body . PHP_EOL;

        //手动发送消息确认
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };

    $comsumerTag = '';
    $noLocal = false;
    $noAck = false;//关闭自动确认
    $exclusive = false;
    $noWait = false;
    $channel->basic_consume($tmpQueueName, $comsumerTag, $noLocal, $noAck, $exclusive, $noWait, $callback);

    //进程挂起
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    //关闭通道与连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}