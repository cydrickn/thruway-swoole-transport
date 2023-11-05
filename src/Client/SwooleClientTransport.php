<?php

namespace Thruway\SwooleTransport\Client;

use OpenSwoole\Coroutine\Http\Client;
use React\EventLoop\LoopInterface;
use Thruway\Message\Message;
use Thruway\Peer\ClientInterface;
use Thruway\SwooleTransport\SwooleTransport;
use Thruway\Transport\AbstractTransport;

class SwooleClientTransport extends AbstractTransport
{
    public function __construct(protected Client $client)
    {
    }

    public function getTransportDetails(): array
    {
        return ['type' => 'swoole'];
    }

    public function sendMessage(Message $msg): void
    {
        $this->client->push($this->getSerializer()->serialize($msg));
    }
}