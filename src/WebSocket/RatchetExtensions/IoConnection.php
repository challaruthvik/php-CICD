<?php

namespace App\WebSocket\RatchetExtensions;

use Ratchet\Server\IoConnection as BaseIoConnection;

class IoConnection extends BaseIoConnection
{
    public $WebSocket;
    public $resourceId;
    public $remoteAddress;
    public $httpHeadersReceived;
}