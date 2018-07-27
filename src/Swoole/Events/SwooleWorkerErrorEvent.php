<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwooleWorkerErrorEvent extends SwooleEvent
{
    /**
     * @var int
     */
    private $workerId;
    /**
     * @var int
     */
    private $workerPid;
    /**
     * @var int
     */
    private $exitCode;
    /**
     * @var int
     */
    private $signal;

    public function __construct($server, int $workerId, int $workerPid, int $exitCode, int $signal)
    {
        parent::__construct($server);
        $this->workerId = $workerId;
        $this->workerPid = $workerPid;
        $this->exitCode = $exitCode;
        $this->signal = $signal;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * @return int
     */
    public function getWorkerPid(): int
    {
        return $this->workerPid;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return int
     */
    public function getSignal(): int
    {
        return $this->signal;
    }

}
