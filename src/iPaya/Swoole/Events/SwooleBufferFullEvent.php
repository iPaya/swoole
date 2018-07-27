<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleBufferFullEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $fd;

    public function __construct($server, int $fd)
    {
        parent::__construct($server);
        $this->fd = $fd;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->fd;
    }

}
