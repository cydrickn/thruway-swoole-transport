<?php

namespace Thruway\SwooleTransport\Connection;

use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Server;

class Connection implements ConnectionInterface
{
    public function __construct(public readonly Request $request, private readonly Server $server)
    {
        // Noting to implement
    }

    public function getFd(): int
    {
        return $this->request->fd;
    }

    public function send(string $data): ConnectionInterface
    {
        $this->server->push($this->getFd(), $data);

        return $this;
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}