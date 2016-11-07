<?php

/*
 * This file is part of the Active Collab Middleware Stack.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\MiddlewareStack\Test;

use ActiveCollab\MiddlewareStack\MiddlewareStack;
use ActiveCollab\MiddlewareStack\Test\Base\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @package ActiveCollab\MiddlewareStack\Test
 */
class StackExecutionTest extends TestCase
{
    public function testStackExecution()
    {
        $stack = new MiddlewareStack();

        $execution_counter = 1;

        $inner_pre_exec = $inner_post_exec = false;
        $middle_pre_exec = $middle_post_exec = false;
        $outer_pre_exec = $outer_post_exec = false;

        $stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$inner_pre_exec, &$inner_post_exec) {
            $inner_pre_exec = $execution_counter++;

            if (is_callable($next)) {
                $response = $next($request, $response);
            }

            $inner_post_exec = $execution_counter++;

            return $response;
        });

        $stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$middle_pre_exec, &$middle_post_exec) {
            $middle_pre_exec = $execution_counter++;

            if (is_callable($next)) {
                $response = $next($request, $response);
            }

            $middle_post_exec = $execution_counter++;

            return $response;
        });

        $stack->addMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$outer_pre_exec, &$outer_post_exec) {
            $outer_pre_exec = $execution_counter++;

            if (is_callable($next)) {
                $response = $next($request, $response);
            }

            $outer_post_exec = $execution_counter++;

            return $response;
        });

        $request = new ServerRequest();
        $response = (new Response())->withHeader('X-Testing-MiddewareStack', 'yes!');

        $response = $stack->process($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('yes!', $response->getHeaderLine('X-Testing-MiddewareStack'));

        $this->assertSame(1, $outer_pre_exec);
        $this->assertSame(2, $middle_pre_exec);
        $this->assertSame(3, $inner_pre_exec);

        $this->assertSame(4, $inner_post_exec);
        $this->assertSame(5, $middle_post_exec);
        $this->assertSame(6, $outer_post_exec);
    }

    public function testRequestAttributes()
    {
        $stack = new MiddlewareStack();

        $execution_counter = 1;

        $inner_pre_exec = $inner_post_exec = false;
        $middle_pre_exec = $middle_post_exec = false;
        $outer_pre_exec = $outer_post_exec = false;

        $stack->addMiddleware(function (ServerRequestInterface &$request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$inner_pre_exec, &$inner_post_exec) {
            $counter = $request->getAttribute('counter');

            if (empty($counter)) {
                $request = $request->withAttribute('counter', 1);
            } else {
                $request = $request->withAttribute('counter', $counter + 1);
            }

            if (is_callable($next)) {
                /** @var ServerRequestInterface $request */
                $response = $next($request, $response);
            }

            $counter = $request->getAttribute('counter');

            if (empty($counter)) {
                $request = $request->withAttribute('counter', 1);
            } else {
                $request = $request->withAttribute('counter', $counter + 1);
            }

            return $response;
        });

        $stack->addMiddleware(function (ServerRequestInterface &$request, ResponseInterface $response, callable $next = null) use (&$execution_counter, &$middle_pre_exec, &$middle_post_exec) {
            $counter = $request->getAttribute('counter');

            if (empty($counter)) {
                $request = $request->withAttribute('counter', 1);
            } else {
                $request = $request->withAttribute('counter', $counter + 1);
            }

            if (is_callable($next)) {
                /** @var ServerRequestInterface $request */
                $response = $next($request, $response);
            }

            $counter = $request->getAttribute('counter');

            if (empty($counter)) {
                $request = $request->withAttribute('counter', 1);
            } else {
                $request = $request->withAttribute('counter', $counter + 1);
            }

            return $response;
        });

        $response = (new Response())->withHeader('X-Testing-MiddewareStack', 'yes!');

        $request = new ServerRequest();

        $response = $stack->process($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('yes!', $response->getHeaderLine('X-Testing-MiddewareStack'));

        $this->assertEquals(4, $request->getAttribute('counter'));
    }
}
