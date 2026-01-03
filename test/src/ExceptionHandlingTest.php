<?php

/*
 * This file is part of the Active Collab Middleware Stack.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace ActiveCollab\MiddlewareStack\Test;

use ActiveCollab\MiddlewareStack\MiddlewareStack;
use ActiveCollab\MiddlewareStack\Test\Base\TestCase;
use ParseError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use RuntimeException;
use Exception;
use Throwable;

class ExceptionHandlingTest extends TestCase
{
    public function testStackExecution(): void
    {
        $this->expectExceptionMessage("Bubbles");
        $this->expectException(RuntimeException::class);

        $stack = new MiddlewareStack();

        $stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$inner_pre_exec, &$inner_post_exec) {
            throw new RuntimeException('Bubbles');
        });

        $request = new ServerRequest();
        $stack->process($request, new Response());
    }

    public function testExceptionHandler(): void
    {
        $stack = new MiddlewareStack();
        $stack->setExceptionHandler(function (Exception $e, ServerRequestInterface $request, ResponseInterface $response) {
            return $response->withStatus(500, 'Exception: ' . $e->getMessage());
        });

        $stack->addMiddleware(
            function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$inner_pre_exec, &$inner_post_exec) {
                throw new RuntimeException('Bubbles');
            },
        );

        $response = (new Response())->withHeader('X-Testing-MiddlewareStack', 'yes!');

        $request = new ServerRequest();
        $response = $stack->process($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('yes!', $response->getHeaderLine('X-Testing-MiddlewareStack'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Exception: Bubbles', $response->getReasonPhrase());
    }

    public function testPhpErrorHandling()
    {
        if (version_compare(PHP_VERSION, '7', '<')) {
            return;
        }

        $stack = new MiddlewareStack();
        $stack->setPhpErrorHandler(
            function (Throwable $e, ServerRequestInterface $request, ResponseInterface $response) {
                return $response->withStatus(500, 'PHP error: ' . $e->getMessage());
            },
        );

        $stack->addMiddleware(
            function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$inner_pre_exec, &$inner_post_exec) {
                throw new ParseError('Syntax error in your code');
            },
        );

        $response = (new Response())->withHeader('X-Testing-MiddlewareStack', 'yes!');

        $request = new ServerRequest();
        $response = $stack->process($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('yes!', $response->getHeaderLine('X-Testing-MiddlewareStack'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('PHP error: Syntax error in your code', $response->getReasonPhrase());
    }
}
