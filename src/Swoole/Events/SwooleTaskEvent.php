<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleTaskEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $taskId;
    /**
     * @var int
     */
    private $srcWorkerId;
    /**
     * @var mixed
     */
    private $data;

    public function __construct($server, int $taskId, int $srcWorkerId, $data)
    {
        parent::__construct($server);
        $this->taskId = $taskId;
        $this->srcWorkerId = $srcWorkerId;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
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
    public function getData()
    {
        return $this->data;
    }

}
