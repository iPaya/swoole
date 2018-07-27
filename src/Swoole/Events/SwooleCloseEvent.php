<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleCloseEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $fd;
    /**
     * @var int
     */
    private $reactorId;

    public function __construct($server, int $fd, int $reactorId)
    {
        parent::__construct($server);
        $this->fd = $fd;
        $this->reactorId = $reactorId;
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

}
