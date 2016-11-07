<?php

/*
 * This file is part of the Active Collab Middleware Stack.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\MiddlewareStack;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * @package ActiveCollab\MiddlewareStack
 */
interface MiddlewareStackInterface
{
    /**
     * Process a request.
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface &$request, ResponseInterface $response);

    /**
     * Add middleware.
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * $middleware can be any callable that accepts three arguments:
     *
     *   1. A Request object
     *   2. A Response object
     *   3. A "next" middleware callable
     *
     * @param  callable                  $middleware
     * @return $this
     * @throws \RuntimeException         If middleware is added while the stack is dequeuing
     * @throws \UnexpectedValueException If the middleware doesn't return a Psr\Http\Message\ResponseInterface
     */
    public function &addMiddleware(callable $middleware);

    /**
     * @param callable $handler
     * @return $this
     */
    public function &setExceptionHandler(callable $handler = null);

    /**
     * @param callable $handler
     * @return $this
     */
    public function &setPhpErrorHandler(callable $handler = null);
}
