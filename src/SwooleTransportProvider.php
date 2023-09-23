<?php

namespace Thruway\SwooleTransport;

use Thruway\SwooleTransport\Event\ConnectionCloseEvent;
use Thruway\SwooleTransport\Event\ConnectionMessageEvent;
use Thruway\SwooleTransport\Event\ConnectionOpenEvent;
use Thruway\SwooleTransport\Server\WebsocketServer;
use Thruway\Event\ConnectionCloseEvent as ThruwayConnectionCloseEvent;
use Thruway\Event\ConnectionOpenEvent as ThruwayConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Event\RouterStopEvent;
use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Message\HelloMessage;
use Thruway\Serializer\JsonSerializer;
use Thruway\Transport\AbstractRouterTransportProvider;

class SwooleTransportProvider extends AbstractRouterTransportProvider
{
    public \SplObjectStorage $sessions;

    public function __construct(private WebsocketServer $server)
    {
        $this->server->addSubscriber($this);
        $this->sessions = new \SplObjectStorage();
    }

    public function handleRouterStart(RouterStartEvent $event): void
    {
        if (!$this->server->isStarted()) {
            $this->server->start();
        }
        $this->sessions = new \SplObjectStorage();
    }

    public function handleRouterStop(RouterStopEvent $event): void
    {
        foreach ($this->sessions as $session) {
            /* @var \Thruway\Session $session */
            $session->shutdown();
        }

        $this->server->shutdown();
        $this->sessions = new \SplObjectStorage();
    }

    public function handleServerConnectionOpen(ConnectionOpenEvent $event): void
    {
        $event->stopPropagation();

        $transport = new SwooleTransport($event->connection, $this->loop);
        $transport->setSerializer(new JsonSerializer());
        $transport->setTrusted(false);

        $session = $this->router->createNewSession($transport);
        $this->sessions->attach($event->connection, $session);

        $this->router->getEventDispatcher()->dispatch('connection_open', new ThruwayConnectionOpenEvent($session));
    }

    public function handleServerConnectionClose(ConnectionCloseEvent $event): void
    {
        $event->stopPropagation();

        $session = $this->sessions[$event->connection];
        $this->sessions->detach($event->connection);
        $this->router->getEventDispatcher()->dispatch('connection_close', new ThruwayConnectionCloseEvent($session));

        unset($this->sessions[$event->connection]);
    }

    public function handleServerConnectionMessage(ConnectionMessageEvent $event): void
    {
        $event->stopPropagation();

        $session = $this->sessions[$event->connection];
        $msg = $event->data;

        try {
            //$this->router->onMessage($transport, $transport->getSerializer()->deserialize($msg));
            $msg = $session->getTransport()->getSerializer()->deserialize($msg);

            if ($msg instanceof HelloMessage) {

                $details = $msg->getDetails();

                $details->transport = (object) $session->getTransport()->getTransportDetails();

                $msg->setDetails($details);
            }

            $session->dispatchMessage($msg);
        } catch (DeserializationException $e) {
            Logger::alert($this, "Deserialization exception occurred.");
        } catch (\Exception $e) {
            Logger::alert($this, "Exception occurred during onMessage: ".$e->getMessage());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'router.start' => ['handleRouterStart', 10],
            'router.stop'  => ['handleRouterStop', 10],
            WebsocketServer::EVENT_CONNECTION_OPEN => ['handleServerConnectionOpen', 100],
            WebsocketServer::EVENT_CONNECTION_CLOSE => ['handleServerConnectionClose', 100],
            WebsocketServer::EVENT_CONNECTION_MESSAGE => ['handleServerConnectionMessage', 100],
        ];
    }
}