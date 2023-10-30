<?php

namespace Thruway\SwooleTransport\Server;

use Thruway\SwooleTransport\Connection\Connection;
use Thruway\SwooleTransport\Event\ConnectionCloseEvent;
use Thruway\SwooleTransport\Event\ConnectionMessageEvent;
use Thruway\SwooleTransport\Event\ConnectionOpenEvent;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\Server as BaseServer;
use OpenSwoole\Timer;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Thruway\Event\EventDispatcher;
use Thruway\Event\EventDispatcherInterface;
use Thruway\Event\EventSubscriberInterface;

class WebsocketServer
{
    public const EVENT_WORKER_START = 'server.worker.start';
    public const EVENT_WORKER_EXIT = 'server.worker.exit';
    public const EVENT_SERVER_START = 'server.start';
    public const EVENT_CONNECTION_OPEN = 'server.connection.open';
    public const EVENT_CONNECTION_CLOSE = 'server.connection.close';
    public const EVENT_CONNECTION_MESSAGE = 'server.connection.message';

    public const HANDLER_WORKER_START = 'WorkerStart';
    public const HANDLER_WORKER_EXIT = 'WorkerExit';
    public const HANDLER_START = 'Start';
    public const HANDLER_OPEN = 'Open';
    public const HANDLER_MESSAGE = 'Message';
    public const HANDLER_CLOSE = 'Close';

    private Server $server;
    private array $connections;
    private EventDispatcherInterface $eventDispatcher;
    private bool $started = false;

    public function __construct(
        public readonly string $host,
        public readonly int $port = 0,
        public readonly int $workerNum = 1
    ) {
        $this->server = new Server($host, $port, BaseServer::POOL_MODE, Constant::SOCK_TCP);
        $this->server->set([
            'worker_num' => $this->workerNum,
            'websocket_subprotocol' => 'wamp.2.json',
            'open_websocket_close_frame' => true,
            'open_websocket_ping_frame' => true,
            'open_websocket_pong_frame' => true,
            "enable_reuse_port" => true,
        ]);
        $this->eventDispatcher = new EventDispatcher();
    }

    public function start(): void
    {
        $this->started = true;
        $this->server->on(self::HANDLER_WORKER_START, function () {
            $this->connections = [];

            $this->eventDispatcher->dispatch(self::EVENT_WORKER_START);
        });

        $this->server->on(self::HANDLER_WORKER_EXIT, function () {
            Timer::clearAll();
            $this->eventDispatcher->dispatch(self::EVENT_WORKER_EXIT);
        });

        $this->server->on(self::HANDLER_START, function () {
            $this->eventDispatcher->dispatch(self::EVENT_SERVER_START);
        });

        $this->server->on(self::HANDLER_OPEN, function (Server $server, Request $request) {
            $connection = new Connection($request, $server);
            $this->connections[$request->fd] = $connection;

            $this->eventDispatcher->dispatch(self::EVENT_CONNECTION_OPEN, new ConnectionOpenEvent($connection));
        });

        $this->server->on(self::HANDLER_MESSAGE, function (Server $server, Frame $frame) {
            // TODO: Implement OP_CLOSE, OP_PING, OP_PONG

            $connection = $this->connections[$frame->fd];
            if ($frame->opcode === Server::WEBSOCKET_OPCODE_CLOSE) {
                // Implement opcode close
                return;
            } elseif ($frame->opcode === Server::WEBSOCKET_OPCODE_PING) {
                $pongFrame = new Frame;
                // Setup a new data frame to send back a pong to the client
                $pongFrame->opcode = Server::WEBSOCKET_OPCODE_PONG;
                $server->push($frame->fd, $pongFrame);

                return;
            } elseif ($frame->opcode === Server::WEBSOCKET_OPCODE_PONG) {
                // Implement opcode poing
                return;
            }

            $this->eventDispatcher->dispatch(self::EVENT_CONNECTION_MESSAGE, new ConnectionMessageEvent($connection, $frame->data));
        });

        $this->server->on(self::HANDLER_CLOSE, function (Server $server, int $fd) {
            if (array_key_exists($fd, $this->connections)) {
                $connection = $this->connections[$fd];
                unset($this->connections[$fd]);
                $this->eventDispatcher->dispatch(self::EVENT_CONNECTION_CLOSE, new ConnectionCloseEvent($connection));
            }
        });

        $this->server->start();
    }

    public function shutdown(): void
    {
        $this->server->shutdown();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function addSubscriber(EventSubscriberInterface $eventSubscriber): void
    {
        $this->eventDispatcher->addSubscriber($eventSubscriber);
    }

    public function addListener(string $event, callable $callback, int $priority = 0): void
    {
        $this->eventDispatcher->addListener($event, $callback, $priority);
    }

    public function getRootServer(): Server
    {
        return $this->server;
    }
}