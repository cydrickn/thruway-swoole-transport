<?php

namespace Thruway\SwooleTransport\Connection;

use OpenSwoole\Http\Request;

interface ConnectionInterface
{
    public function getRequest(): Request;

    public function getFd(): int;

    public function send(string $data): ConnectionInterface;

    public function close();
}