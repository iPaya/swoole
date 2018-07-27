<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


abstract class SwooleEvent extends Event
{
    /**
     * @var \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server
     */
    private $server;

    /**
     * SwooleEvent constructor.
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $server
     */
    public function __construct($server)
    {
        $this->setServer($server);
    }

    /**
     * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $server
     */
    public function setServer($server): void
    {
        $this->server = $server;
    }
}
