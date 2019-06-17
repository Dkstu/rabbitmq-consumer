<?php

require_once __DIR__.'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'router';
$queue = 'test-consumer';
$consumerTag = 'consumer';

$host = "127.0.0.1";
$port = "5672";
$user = "guest";
$pass = "guest";
$vhost = "/";

$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);

$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);

function process_message($message)
{
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
}

$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}
register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait();
}
