<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleWorkerExitEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $workerId;

    public function __construct($server, int $workerId)
    {
        parent::__construct($server);
        $this->workerId = $workerId;
    }
}
