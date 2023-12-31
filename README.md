# Swoole Thruway Transport

OpenSwoole / Swoole Websocket Transport for Thruway Router

## Prerequisite:
- PHP version >= 8.1
- [OpenSwoole](https://openswoole.com/docs/get-started/installation)
- [voryx/thruway](https://github.com/voryx/Thruway)

## Installation

```sh
composer require cydrickn/thruway-swoole-transport
```

## Example

### Ouside the worker start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Thruway\SwooleTransport\Server\WebsocketServer;
use Thruway\SwooleTransport\SwooleLoop;
use Thruway\SwooleTransport\SwooleTransportProvider;
use Thruway\Peer\Router;

$server = new WebsocketServer('127.0.0.1', 9999);
$transportProvider = new SwooleTransportProvider($server);

$router = new Router(new SwooleLoop());
$router->addTransportProvider($transportProvider);

$server->addListener(WebsocketServer::EVENT_SERVER_START, function () use($transportProvider, $server) {
    \Thruway\Logging\Logger::info($transportProvider, 'Websocket listening on 0.0.0.0:9000');
    $server->shutdown();
});

$router->start(false);
```

### Inside Worker Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Thruway\SwooleTransport\Server\WebsocketServer;
use Thruway\SwooleTransport\SwooleLoop;
use Thruway\SwooleTransport\SwooleTransportProvider;
use Thruway\Peer\Router;

$server = new WebsocketServer('127.0.0.1', 9999);

$server->addListener(WebsocketServer::EVENT_SERVER_START, function () {
    \Thruway\Logging\Logger::info(null, 'Websocket listening on 0.0.0.0:9000');
});

$server->addListener(WebsocketServer::EVENT_WORKER_START, function () use($server) {
    $transportProvider = new SwooleTransportProvider($server);

    $router = new Router(new SwooleLoop());
    $router->addTransportProvider($transportProvider);

    $router->start(false);
});
```


