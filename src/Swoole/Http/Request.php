<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Http;


use Swoole\Http\Request as SwooleRequest;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use function Zend\Diactoros\normalizeServer;
use function Zend\Diactoros\normalizeUploadedFiles;

class Request extends ServerRequest
{
    /**
     * @param SwooleRequest $swooleRequest
     * @return Request
     */
    public static function createFromSwooleRequest(SwooleRequest $swooleRequest): Request
    {
        $serverParams = array_change_key_case($swooleRequest->server, CASE_UPPER);
        $headers = array_change_key_case($swooleRequest->header, CASE_UPPER);

        $files = normalizeUploadedFiles($swooleRequest->files ?? []);
        $server = normalizeServer($serverParams);

        $stream = new Stream('php://temp', 'rw');
        $stream->write($swooleRequest->rawContent());
        $stream->rewind();

        $uri = 'http://' . $swooleRequest->header['host'] . $swooleRequest->server['request_uri'];

        $method = $swooleRequest->server['request_method'];
        $queryParams = $swooleRequest->get ?? [];
        $cookies = $swooleRequest->cookie ?? [];
        if ($swooleRequest->get) {
            $uri .= '?' . http_build_query($queryParams);
        }

        return new static(
            $server,
            $files,
            $uri,
            $method,
            $stream,
            $headers,
            $cookies,
            $queryParams,
            $swooleRequest->post
        );
    }
}
