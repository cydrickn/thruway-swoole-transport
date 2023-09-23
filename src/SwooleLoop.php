<?php

namespace Thruway\SwooleTransport;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class SwooleLoop implements LoopInterface
{
    public function addReadStream($stream, $listener)
    {
        // Not applicable
    }

    public function addWriteStream($stream, $listener)
    {
        // Not applicable
    }

    public function removeReadStream($stream)
    {
        // Not applicable
    }

    public function removeWriteStream($stream)
    {
        // Not applicable
    }

    public function addTimer($interval, $callback)
    {
        // Not applicable
    }

    public function addPeriodicTimer($interval, $callback)
    {
        // Not applicable
    }

    public function cancelTimer(TimerInterface $timer)
    {
        // Not applicable
    }

    public function futureTick($listener)
    {
        // Not applicable
    }

    public function addSignal($signal, $listener)
    {
        // Not applicable
    }

    public function removeSignal($signal, $listener)
    {
        // Not applicable
    }

    public function run()
    {
        // Not applicable
    }

    public function stop()
    {
        // Not applicable
    }
}