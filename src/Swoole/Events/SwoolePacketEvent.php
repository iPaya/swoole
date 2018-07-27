<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Events;


class SwoolePacketEvent extends SwooleEvent
{
    /**
     * @var string
     */
    public $data;
    /**
     * @var array
     */
    public $clientInfo;

    public function __construct($server, string $data, array $clientInfo)
    {
        parent::__construct($server);
        $this->data = $data;
        $this->clientInfo = $clientInfo;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getClientInfo(): array
    {
        return $this->clientInfo;
    }

}
