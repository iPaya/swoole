<?php
/**
 * @link http://www.ipaya.cn/
 * @copyright Copyright (c) 2018 ipaya.cn
 */

namespace iPaya\Swoole\Http;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $html = '<h1>Hello World!</h1>';
        $html .= '<p>这是默认页面，您需要实现自己的 <code>RequestHandlerInterface</code> 来处理请求。</p>';
        $html .= '<hr>';
        $html .= '<p><strong>帮助</strong> <a href="https://github.com/iPaya/swoole/" target="_blank">源码</a></p>';

        return new HtmlResponse($html);
    }

}
