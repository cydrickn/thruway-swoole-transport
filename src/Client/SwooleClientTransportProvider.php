<?php

namespace Thruway\SwooleTransport\Client;

use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Http\Client;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use React\EventLoop\LoopInterface;
use Thruway\Peer\ClientInterface;
use Thruway\Serializer\JsonSerializer;
use Thruway\Transport\AbstractClientTransportProvider;

class SwooleClientTransportProvider extends AbstractClientTransportProvider
{
    protected ?Client $swooleClient;

    public function __construct(private string $host, private int $port, private string $path = '/')
    {
        $this->swooleClient = null;
    }

    public function setSwooleClient(Client $client): void
    {
        $this->swooleClient = $client;
    }

    public function startTransportProvider(ClientInterface $peer, LoopInterface $loop): void
    {
        $this->client = $peer;
        $this->loop = $loop;

        if ($this->swooleClient === null) {
            $this->swooleClient = new Client($this->host, $this->port);
        }
        $this->swooleClient->setHeaders([
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Sec-WebSocket-Protocol' => 'wamp.2.json, wamp.2.msgpack'
        ]);

        $upgraded = $this->swooleClient->upgrade($this->path);
        if (!$upgraded) {
            throw new \Exception('Unable to start');
        }

        $transport = new SwooleClientTransport($this->swooleClient);
        $transport->setSerializer(new JsonSerializer());
        $this->client->onOpen($transport);

        Coroutine::create(function () use ($transport) {
            while ($this->swooleClient->connected) {
                /* @var $data \OpenSwoole\WebSocket\Frame */
                $data = $this->swooleClient->recv();
                Coroutine::create(function (Frame|bool $data) use ($transport) {
                    if (!($data instanceof Frame)) {
                        return;
                    }
                    if ($data->opcode === Server::WEBSOCKET_OPCODE_TEXT) {
                        $this->client->onMessage($transport, $transport->getSerializer()->deserialize($data->data));
                    } elseif ($data->opcode === Server::WEBSOCKET_OPCODE_CLOSE) {
                        $this->client->onClose('close');
                    }
                }, $data);
            }
        });
    }
}