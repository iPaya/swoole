<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleReceiveEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $fd;
    /**
     * @var int
     */
    private $reactorId;
    /**
     * @var int
     */
    private $data;

    /**
     * SwooleReceiveEvent constructor.
     *
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     * @param int $reactorId
     * @param string $data
     */
    public function __construct($server, int $fd, int $reactorId, string $data)
    {
        parent::__construct($server);
        $this->fd = $fd;
        $this->reactorId = $reactorId;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->fd;
    }

    /**
     * @return int
     */
    public function getReactorId(): int
    {
        return $this->reactorId;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

}
