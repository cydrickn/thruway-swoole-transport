<?php

namespace Thruway\SwooleTransport\Event;

use Thruway\SwooleTransport\Connection\ConnectionInterface;
use Thruway\Event\Event;

class ConnectionOpenEvent extends Event
{
    public function __construct(public readonly ConnectionInterface $connection)
    {
    }
}