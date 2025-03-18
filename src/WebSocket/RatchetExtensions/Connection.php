<?php

namespace App\WebSocket\RatchetExtensions;

use React\Socket\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public $decor;
}