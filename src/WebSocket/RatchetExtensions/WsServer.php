<?php

namespace App\WebSocket\RatchetExtensions;

use Ratchet\WebSocket\WsServer as BaseWsServer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WsServer extends BaseWsServer
{
    public function __construct(MessageComponentInterface $component)
    {
        parent::__construct($component);
    }
}