<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleFinishEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $taskId;
    /**
     * @var mixed
     */
    private $data;

    public function __construct($server, int $taskId, $data)
    {
        parent::__construct($server);
        $this->taskId = $taskId;
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
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

}
