<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Thruway\SwooleTransport\Server\WebsocketServer;
use Thruway\SwooleTransport\SwooleLoop;
use Thruway\SwooleTransport\SwooleTransportProvider;
use Thruway\Peer\Router;

$server = new WebsocketServer('127.0.0.1', 9999);
$transportProvider = new SwooleTransportProvider($server);

$router = new Router(new SwooleLoop());
$router->addTransportProvider($transportProvider);

$server->addListener(WebsocketServer::EVENT_SERVER_START, function () use ($transportProvider, $server) {
    \Thruway\Logging\Logger::info($transportProvider, 'Websocket listening on 0.0.0.0:9000');
    $server->shutdown();
});

$router->start(false);