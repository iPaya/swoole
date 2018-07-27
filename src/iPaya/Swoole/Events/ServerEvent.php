<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


use iPaya\Swoole\Server;

class ServerEvent extends Event
{
    /**
     * @var Server
     */
    private $server;

    /**
     * SwooleEvent constructor.
     * @param Server
     */
    public function __construct($server)
    {
        $this->setServer($server);
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Server
     */
    public function setServer($server): void
    {
        $this->server = $server;
    }
}
