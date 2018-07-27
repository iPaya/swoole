<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole;


use iPaya\Swoole\Http\Request;
use iPaya\Swoole\Http\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response;
use Swoole\Http\Response as SwooleResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;

class HttpServer extends Server
{
    /**
     * @var string
     */
    private $documentRoot;
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;
    /**
     * @var string 脚本文件名，此文件为虚拟文件
     */
    private $scriptFile = '/index.php';
    /**
     * @var bool 是否开启 URL 地址重写
     */
    private $enableRewrite = false;
    /**
     * @var bool
     */
    private $debug = false;


    /**
     * HttpServer constructor.
     *
     * @param string $documentRoot
     * @param int $port
     */
    public function __construct(string $documentRoot, int $port = 9080)
    {
        parent::__construct($port);
        $this->setDocumentRoot($documentRoot);
        $this->setRequestHandler(new RequestHandler());
    }

    /**
     * @inheritdoc
     */
    public function swooleEvents()
    {
        $events = parent::swooleEvents();

        return array_merge($events, [
            'request' => 'onSwooleRequest'
        ]);
    }

    /**
     * @param SwooleRequest $request
     */
    public function onSwooleRequest(SwooleRequest $request)
    {
        try {
            $uri = $request->server['request_uri'];
            $file = $this->getDocumentRoot() . $uri;

            if ($uri == '/' || $uri == $this->getScriptFile()) {
                // 默认页面
                $psrResponse = $this->doHandleRequest($request);
            } else {
                $extension = pathinfo($uri, PATHINFO_EXTENSION);

                if (is_file($file)) {
                    // 文件存在
                    if ($extension != 'php') {
                        // 非 PHP 文件，直接发送，推荐使用 Nginx 来处理
                        $psrResponse = $this->doHandleNonPhpFileRequest($file);
                    } else {
                        $psrResponse = $this->doHandlePhpFileRequest($file);
                    }
                } elseif (is_dir($file)) {
                    // 为目录
                    $psrResponse = $this->doHandleDirRequest($file);
                } else {
                    // 文件不存在
                    if ($this->isEnableRewrite()) {
                        // 已开启 URL 地址重写
                        $psrResponse = $this->doHandleRequest($request);
                    } else {
                        $psrResponse = $this->doHandle404Request($file);
                    }
                }
            }
        } catch (\Throwable $throwable) {
            $psrResponse = $this->doHandleThrowable($throwable);
        }

        $this->sendResponse($request->fd, $psrResponse);
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @param string $documentRoot
     */
    public function setDocumentRoot(string $documentRoot): void
    {
        $this->documentRoot = $documentRoot;
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
    }

    /**
     * @return string
     */
    public function getScriptFile(): string
    {
        return $this->scriptFile;
    }

    /**
     * @param string $scriptFile
     */
    public function setScriptFile(string $scriptFile): void
    {
        $this->scriptFile = '/' . ltrim($scriptFile, "\/");
    }

    /**
     * @param SwooleRequest $request
     * @return ResponseInterface
     */
    protected function doHandleRequest(SwooleRequest $request): ResponseInterface
    {
        $psrRequest = Request::createFromSwooleRequest($request);
        return $this->getRequestHandler()->handle($psrRequest);
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->requestHandler;
    }

    /**
     * @param RequestHandlerInterface $requestHandler
     */
    public function setRequestHandler(RequestHandlerInterface $requestHandler): void
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @param string $file
     * @return ResponseInterface
     */
    protected function doHandleNonPhpFileRequest(string $file): ResponseInterface
    {
        $headers = [];
        $size = filesize($file);

        $headers['content-length'] = [$size];

        $mimeType = getMimeType($file);
        if ($mimeType) {
            $headers['content-type'] = [$mimeType];
        }

        return new \Zend\Diactoros\Response(fopen($file, 'r'), 200, $headers);
    }

    /**
     * @param string $file
     * @return ResponseInterface
     */
    protected function doHandlePhpFileRequest(string $file): ResponseInterface
    {
        ob_start();
        include $file;

        return new HtmlResponse(ob_get_clean(), 200);
    }

    /**
     * @param string $file
     * @return ResponseInterface
     */
    protected function doHandleDirRequest(string $file): ResponseInterface
    {
        return new HtmlResponse("doHandleDirRequest: $file", 200);
    }

    /**
     * @return bool
     */
    public function isEnableRewrite(): bool
    {
        return $this->enableRewrite;
    }

    /**
     * @param bool $enableRewrite
     */
    public function setEnableRewrite(bool $enableRewrite): void
    {
        $this->enableRewrite = $enableRewrite;
    }

    /**
     * @param string $file
     * @return ResponseInterface
     */
    protected function doHandle404Request(string $file): ResponseInterface
    {
        ob_start();

        echo '<h1>Not Found</h1>';
        if ($this->isDebug()) {
            echo '<hr/>';
            echo '<div>File <code>' . $file . '</code> not found.</div>';
        }

        $html = ob_get_clean();
        return new HtmlResponse($html, 404);
    }

    /**
     * @param \Throwable $throwable
     * @return ResponseInterface
     */
    protected function doHandleThrowable(\Throwable $throwable): ResponseInterface
    {
        @ob_end_flush();

        $contents = 'Fatal error: ' . $throwable->getMessage() . ' in ' . $throwable->getFile() . ':' . $throwable->getLine() . "\n";
        $contents .= "Stack trace:\n";
        $contents .= $throwable->getTraceAsString();

        return new TextResponse($contents, 500);
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * 向客户端发送响应
     *
     * @param int $fd
     * @param ResponseInterface $response
     */
    protected function sendResponse(int $fd, ResponseInterface $response): void
    {
        /** @var SwooleResponse $swooleResponse */
        $swooleResponse = Response::create($fd);

        // send status
        $swooleResponse->status($response->getStatusCode());

        $headers = $response->getHeaders();

        // set cookies
        foreach ($headers['set-cookie'] ?? [] as $value) {
            $swooleResponse->header('set-cookie', $value);
        }
        unset($headers['set-cookie']);

        // send headers
        foreach ($headers as $name => $values) {
            $swooleResponse->header($name, implode(', ', $values));
        }

        // send contents
        if ($response->getBody()->getSize() != null && $response->getBody()->getSize() > 0) {
            $swooleResponse->write($response->getBody());
        }

        // 发送结束信息
        $swooleResponse->end();
    }

    /**
     * @return \Swoole\Http\Server
     */
    protected function createSwooleServer(): \Swoole\Http\Server
    {
        $server = new \Swoole\Http\Server($this->getHost(), $this->getPort());

        return $server;
    }

}
