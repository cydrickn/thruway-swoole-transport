<?php

namespace Thruway\SwooleTransport\Event;

use Thruway\SwooleTransport\Connection\ConnectionInterface;
use Thruway\Event\Event;

class ConnectionCloseEvent extends Event
{
    public function __construct(public readonly ConnectionInterface $connection)
    {
    }
}