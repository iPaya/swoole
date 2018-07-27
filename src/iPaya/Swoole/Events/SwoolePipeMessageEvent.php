<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwoolePipeMessageEvent extends SwooleEvent
{
    /**
     * @var int
     */
    public $srcWorkerId;
    /**
     * @var mixed
     */
    public $message;

    public function __construct($server, int $srcWorkerId, $message)
    {
        parent::__construct($server);
        $this->srcWorkerId = $srcWorkerId;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getSrcWorkerId(): int
    {
        return $this->srcWorkerId;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

}
