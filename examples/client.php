<?php

require_once __DIR__ . '/../vendor/autoload.php';

co::run(function () {
    $client = new \Thruway\Peer\Client('realm1');
    $client->addTransportProvider(new \Thruway\SwooleTransport\Client\SwooleClientTransportProvider('0.0.0.0', 9999));
    $client->on('open', function (\Thruway\ClientSession $session) {
        $session->register('plus', function () {
            return 'test';
        });
    });
    $client->start(false);
});