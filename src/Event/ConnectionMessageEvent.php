<?php

namespace Thruway\SwooleTransport\Event;

use Thruway\SwooleTransport\Connection\ConnectionInterface;
use Thruway\Event\Event;

class ConnectionMessageEvent extends Event
{
    public function __construct(public readonly ConnectionInterface $connection, public readonly string $data)
    {
    }
}