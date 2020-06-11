<?php
/**
 * Created by PhpStorm.
 * User: zhangrenjie
 * Date: 2020/6/10
 * Time: 下午2:09
 * 生产者将Channel设置成confirm模式，一旦Channel进入confirm模式，所有在该Channel上面发布的消息都将会被指派一个唯一的ID(从1开始)，
 * 一旦消息被投递到所有匹配的队列之后，broker就会发送一个确认给生产者(包含消息的唯一ID)，这就使得生产者知道消息已经正确到达目的队列了，
 * 如果消息和队列是可持久化的，那么确认消息会在将消息写入磁盘之后发出，broker回传给生产者的确认消息中delivery-tag域包含了确认消息的序列号，
 * 此外broker也可以设置basic.ack的multiple域，表示到这个序列号之前的所有消息都已经得到了处理；
 *
 *
 *confirm模式最大的好处在于他是异步的，一旦发布一条消息，生产者应用程序就可以在等Channel返回确认的同时继续发送下一条消息，
 * 当消息最终得到确认之后，生产者应用便可以通过回调方法来处理该确认消息，如果RabbitMQ因为自身内部错误导致消息丢失，
 * 就会发送一条nack消息，生产者应用程序同样可以在回调方法中处理该nack消息；
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

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

    //开启通道的confirm模式，
    $channel->confirm_select();

    //成功投递消息
    $channel->set_ack_handler(function (AMQPMessage $message) {
        echo '消息' . $message->getBody() . '投递成功' . PHP_EOL;
    });

    //未成功投递消息
    $channel->set_nack_handler(function (AMQPMessage $message) {
        echo '消息' . $message->getBody() . '投递投递失败' . PHP_EOL;
    });

    //一旦有消息不能被路由就会进入到该回调函数触发
    $channel->set_return_listener(
        function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
            echo "Message returned with content " . $message->body . PHP_EOL;
        }
    );

    //创建工作消息队列
    $queueName = 'test_queue_confirm';
    $passive = false;
    $durable = true;//持久化交换机
    $exclusive = false;
    $autoDelete = false;
    $noWait = false;

    //--定义工作消息队列参数
    $arguments = new AMQPTable();
    $arguments->set('x-message-ttl', 30000);//队列中订单消息600s过期
    $channel->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete, $noWait, $arguments);

    //定义消息
    $content = json_encode([
        'order_no' => time() . rand(10, 99),
    ]);

    $message = new AMQPMessage($content, [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,//设置消息持久化
        'expiration' => 50000,//直接定义该条消息的生命周期，当队列设置了x-message-ttl，消息的生命周期为最小值
    ]);

    //发送消息
    $exchangeName = '';
    $routingKeyName = $queueName;

    for ($i = 0; $i < 10; $i++) {
        //投递消息
        $channel->basic_publish($message, $exchangeName, $routingKeyName);
    }
    //Waits for pending acks, nacks and returns from the server
    $channel->wait_for_pending_acks_returns();//set wait time


    //关闭通道和连接
    $channel->close();
    $connection->close();

} catch (\Throwable $e) {
    throw $e;
}
