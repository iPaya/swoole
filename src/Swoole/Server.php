<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole;


use iPaya\Swoole\Events\StartServerEvent;
use iPaya\Swoole\Events\SwooleBufferEmptyEvent;
use iPaya\Swoole\Events\SwooleBufferFullEvent;
use iPaya\Swoole\Events\SwooleCloseEvent;
use iPaya\Swoole\Events\SwooleConnectEvent;
use iPaya\Swoole\Events\SwooleFinishEvent;
use iPaya\Swoole\Events\SwooleManagerStartEvent;
use iPaya\Swoole\Events\SwooleManagerStopEvent;
use iPaya\Swoole\Events\SwoolePacketEvent;
use iPaya\Swoole\Events\SwoolePipeMessageEvent;
use iPaya\Swoole\Events\SwooleReceiveEvent;
use iPaya\Swoole\Events\SwooleShutdownEvent;
use iPaya\Swoole\Events\SwooleStartEvent;
use iPaya\Swoole\Events\SwooleTaskEvent;
use iPaya\Swoole\Events\SwooleWorkerErrorEvent;
use iPaya\Swoole\Events\SwooleWorkerExitEvent;
use iPaya\Swoole\Events\SwooleWorkerStartEvent;
use iPaya\Swoole\Events\SwooleWorkerStopEvent;
use iPaya\Swoole\Helpers\OptionResolver;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Server
 * @package iPaya\Swoole
 * @property bool $enableTask
 */
abstract class Server
{
    /**
     * @var bool 是否开启 Task 功能
     */
    public $enableTask = false;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server
     */
    private $swooleServer;
    private $swooleSettings = [];
    /**
     * @var array Task 相关设置，只有在 Server::$enableTask 为 true 时有效
     */
    private $taskSettings = [
        'workerNum' => 4,
    ];

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var int 监听端口
     */
    private $port;

    /**
     * @var string 绑定的主机 IP 地址
     */
    private $host = '0.0.0.0';

    /**
     *
     * @param int $port
     */
    public function __construct(int $port)
    {
        $this->setPort($port);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port): void
    {
        $this->port = $port;
    }

    /**
     * @return bool
     */
    protected function beforeStart(): bool
    {
        $event = new StartServerEvent($this);
        $this->trigger('beforeStart', $event);

        return $event->isValid();
    }

    public function start()
    {
        $server = $this->createSwooleServer();
        $this->setSwooleServer($server);

        $this->registerSwooleSettings();
        $this->registerSwooleEvents();

        if (!$this->beforeStart()) {
            exit(1);
        }

        $server->start();
    }

    /**
     * @return \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server
     */
    abstract protected function createSwooleServer();

    /**
     *
     */
    public function registerSwooleSettings()
    {
        $server = $this->getSwooleServer();
        $settings = $this->getSwooleSettings();

        // 处理 task 相关设置
        if ($this->isEnableTask()) {
            $taskSettings = $this->getTaskSettings();
            if (!isset($taskSettings['workerNum']) || $taskSettings['workerNum'] <= 0) {
                $this->stderr('<error>启用 Task 功能必须设置 $taskSettings[workerNum] 并大于 0.</error>');
                exit(1);
            }

            OptionResolver::resolve($settings, $this->getTaskSettings(), [
                'task_worker_num' => 'workerNum',
                'task_ipc_mode' => 'ipcMode',
                'task_max_request' => 'maxRequest',
                'task_tmpdir' => 'tmpDir'
            ]);
        } else {
            // 未开启 task 功能，取消关于 task 的设置
            unset(
                $settings['task_worker_num'],
                $settings['task_ipc_mode'],
                $settings['task_max_request'],
                $settings['task_tmpdir']
            );
        }

        $server->set($settings);
    }

    /**
     * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server
     */
    public function getSwooleServer()
    {
        return $this->swooleServer;
    }

    /**
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $swooleServer
     */
    public function setSwooleServer($swooleServer): void
    {
        $this->swooleServer = $swooleServer;
    }

    /**
     * @return array
     */
    public function getSwooleSettings(): array
    {
        return $this->swooleSettings;
    }

    /**
     * @param array $swooleSettings
     */
    public function setSwooleSettings(array $swooleSettings): void
    {
        $this->swooleSettings = $swooleSettings;
    }

    /**
     * @return bool
     */
    public function isEnableTask(): bool
    {
        return $this->enableTask;
    }

    /**
     * @param bool $enableTask
     */
    public function setEnableTask(bool $enableTask): void
    {
        $this->enableTask = $enableTask;
    }

    /**
     * @return array
     */
    public function getTaskSettings(): array
    {
        return $this->taskSettings;
    }

    /**
     * @param array $taskSettings
     */
    public function setTaskSettings(array $taskSettings): void
    {
        $this->taskSettings = $taskSettings;
    }

    /**
     * @param string $message
     * @param bool $withTimestamp
     */
    public function stderr(string $message, $withTimestamp = false): void
    {
        if (!$withTimestamp) {
            $content = $message;
        } else {
            list($micro, $second) = explode(' ', microtime());
            $micro = str_pad(floor($micro * 1000), 3, '0', STR_PAD_RIGHT);
            $prefix = '[' . date('Y-m-d H:i:s ', $second) . $micro . ']';
            $content = $prefix . ' ' . $message;
        }

        $this->getOutput()->getErrorOutput()->writeln($content);
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput(): ConsoleOutput
    {
        if ($this->output == null) {
            $this->output = new ConsoleOutput();
        }
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * 注册 Swoole 事件回调函数
     */
    public function registerSwooleEvents()
    {
        $swooleServer = $this->getSwooleServer();
        foreach ($this->swooleEvents() as $event => $method) {
            $swooleServer->on($event, [$this, $method]);
        }
    }

    /**
     * @return array
     */
    public function swooleEvents()
    {
        return [
            'start' => 'onSwooleStart',
            'shutdown' => 'onSwooleShutdown',
            'managerStart' => 'onSwooleManagerStart',
            'managerStop' => 'onSwooleManagerStop',
            'workerStart' => 'onSwooleWorkerStart',
            'workerStop' => 'onSwooleWorkerStop',
            'workerExit' => 'onSwooleWorkerExit',
            'workerError' => 'onSwooleWorkerError',
            'connect' => 'onSwooleConnect',
            'receive' => 'onSwooleReceive',
            'close' => 'onSwooleClose',
            'bufferFull' => 'onSwooleBufferFull',
            'bufferEmpty' => 'onSwooleBufferEmpty',
            'packet' => 'onSwoolePacket',
            'pipeMessage' => 'onSwoolePipeMessage',
            'task' => 'onSwooleTask',
            'finish' => 'onSwooleFinish',
        ];
    }

    /**
     * @param string $eventName
     * @param callable $listener
     */
    public function on(string $eventName, $listener): void
    {
        $this->getEventDispatcher()->addListener($eventName, $listener);
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher == null) {
            $this->eventDispatcher = new EventDispatcher();
        }
        return $this->eventDispatcher;
    }

    /**
     * @param string $log
     */
    public function log(string $log): void
    {
        $this->stdout($log, true);
    }

    /**
     * @param string $message
     * @param bool $withTimestamp
     */
    public function stdout(string $message, $withTimestamp = false): void
    {
        if (!$withTimestamp) {
            $content = $message;
        } else {
            list($micro, $second) = explode(' ', microtime());
            $micro = str_pad(floor($micro * 1000), 3, '0', STR_PAD_RIGHT);
            $prefix = '[' . date('Y-m-d H:i:s ', $second) . $micro . ']';
            $content = $prefix . ' ' . $message;
        }
        $this->getOutput()->writeln($content);
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     */
    public function onSwooleStart($server): void
    {
        $this->trigger('swoole.start', new SwooleStartEvent($server));
    }

    /**
     * @param string $eventName
     * @param Event|null $event
     * @return Event
     */
    public function trigger(string $eventName, Event $event = null): Event
    {
        return $this->getEventDispatcher()->dispatch($eventName, $event);
    }

    public function onSwooleShutdown($server): void
    {
        $this->trigger('swoole.shutdown', new SwooleShutdownEvent($server));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     */
    public function onSwooleManagerStart($server): void
    {
        $this->trigger('swoole.managerStart', new SwooleManagerStartEvent($server));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     */
    public function onSwooleManagerStop($server): void
    {
        $this->trigger('swoole.managerStop', new SwooleManagerStopEvent($server));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $workerId
     */
    public function onSwooleWorkerStart($server, int $workerId): void
    {
        $this->trigger('swoole.workerStart', new SwooleWorkerStartEvent($server, $workerId));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $workerId
     */
    public function onSwooleWorkerStop($server, int $workerId): void
    {
        $this->trigger('swoole.workerStop', new SwooleWorkerStopEvent($server, $workerId));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $workerId
     */
    public function onSwooleWorkerExit($server, int $workerId): void
    {
        $this->trigger('swoole.workerExit', new SwooleWorkerExitEvent($server, $workerId));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $workerId
     * @param int $workerPid
     * @param int $exitCode
     * @param int $signal
     */
    public function onSwooleWorkerError($server, int $workerId, int $workerPid, int $exitCode, int $signal): void
    {
        $this->trigger('swoole.workerExit', new SwooleWorkerErrorEvent($server, $workerId, $workerPid, $exitCode, $signal));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onSwooleConnect($server, int $fd, int $reactorId): void
    {
        $this->trigger('swoole.connect', new SwooleConnectEvent($server, $fd, $reactorId));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     * @param $reactorId
     * @param string $data
     */
    public function onSwooleReceive($server, int $fd, int $reactorId, string $data): void
    {
        $this->trigger('swoole.receive', new SwooleReceiveEvent($server, $fd, $reactorId, $data));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onSwooleClose($server, int $fd, int $reactorId): void
    {
        $this->trigger('swoole.close', new SwooleCloseEvent($server, $fd, $reactorId));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     */
    public function onSwooleBufferFull($server, int $fd): void
    {
        $this->trigger('swoole.bufferFull', new SwooleBufferFullEvent($server, $fd));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $fd
     */
    public function onSwooleBufferEmpty($server, int $fd): void
    {
        $this->trigger('swoole.bufferEmpty', new SwooleBufferEmptyEvent($server, $fd));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param string $data
     * @param array $clientInfo
     */
    public function onSwoolePacket($server, string $data, array $clientInfo)
    {
        $this->trigger('swoole.packet', new SwoolePacketEvent($server, $data, $clientInfo));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $srcWorkerId
     * @param mixed $message
     */
    public function onSwoolePipeMessage($server, int $srcWorkerId, $message)
    {
        $this->trigger('swoole.pipeMessage', new SwoolePipeMessageEvent($server, $srcWorkerId, $message));
    }

    /**
     * @param \Swoole\Server|\Swoole\Http\Server|\Swoole\WebSocket\Server $server
     * @param int $taskId
     * @param int $srcWorkerId
     * @param $data
     */
    public function onSwooleTask($server, int $taskId, int $srcWorkerId, $data)
    {
        $this->trigger('swoole.task', new SwooleTaskEvent($server, $taskId, $srcWorkerId, $data));
    }

    /**
     * @param $server
     * @param int $taskId
     * @param $data
     */
    public function onSwooleFinish($server, int $taskId, $data)
    {
        $this->trigger('swoole.finish', new SwooleFinishEvent($server, $taskId, $data));
    }

    /**
     * 初始化
     */
    protected function initialize(): void
    {
    }
}
