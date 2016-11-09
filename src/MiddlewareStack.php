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

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use SplDoublyLinkedList;
use SplStack;
use Throwable;
use UnexpectedValueException;

/**
 * @package ActiveCollab\MiddlewareStack
 */
class MiddlewareStack implements MiddlewareStackInterface
{
    /**
     * Middleware call stack.
     *
     * @var \SplStack
     * @link http://php.net/manual/class.splstack.php
     */
    protected $stack;

    /**
     * Middleware stack lock.
     *
     * @var bool
     */
    protected $middleware_lock = false;

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            $response = $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            $response = $this->handlePhpError($e, $request, $response);
        }

        if (!$response instanceof ResponseInterface) {
            throw new RuntimeException('Response expected');
        }

        $response = $this->finalizeProcessing($response);

        return $response;
    }

    /**
     * Finalize response.
     *
     * @param  ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalizeProcessing(ResponseInterface $response)
    {
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function &addMiddleware(callable $callable)
    {
        if ($this->middleware_lock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        $next = $this->stack->top();
        $this->stack[] = function (ServerRequestInterface $req, ResponseInterface $res) use ($callable, $next) {
            $result = call_user_func_array($callable, [$req, $res, $next]);
            if ($result instanceof ResponseInterface === false) {
                throw new UnexpectedValueException('Middleware must return instance of \Psr\Http\Message\ResponseInterface');
            }

            return $result;
        };

        return $this;
    }

    /**
     * Seed middleware stack with first callable.
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $kernel = null)
    {
        if (!is_null($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if ($kernel === null) {
            $kernel = $this;
        }
        $this->stack = new SplStack();
        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
        $this->stack[] = $kernel;
    }

    /**
     * Call middleware stack.
     *
     * @param ServerRequestInterface $req A request object
     * @param ResponseInterface      $res A response object
     *
     * @return ResponseInterface
     */
    protected function callMiddlewareStack(ServerRequestInterface $req, ResponseInterface $res)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        /** @var callable $start */
        $start = $this->stack->top();
        $this->middleware_lock = true;
        $resp = call_user_func_array($start, [$req, $res]);
        $this->middleware_lock = false;

        return $resp;
    }

    /**
     * Invoke application.
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after compiling the routes registered in the Router and dispatching
     * the Request object to the appropriate Route callback routine.
     *
     * @param ServerRequestInterface $request  The most recent Request object
     * @param ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }

    /**
     * @var callable|null
     */
    private $exception_handler;

    /**
     * {@inheritdoc}
     */
    public function &setExceptionHandler(callable $handler = null)
    {
        $this->exception_handler = $handler;

        return $this;
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Exception              $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @return ResponseInterface
     * @throws Exception
     */
    protected function handleException(Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->exception_handler) {
            return call_user_func_array($this->exception_handler, [$e, $request, $response]);
        }

        throw $e; // No handlers found, so just throw the exception
    }

    /**
     * @var callable|null
     */
    private $php_error_handler;

    /**
     * {@inheritdoc}
     */
    public function &setPhpErrorHandler(callable $handler = null)
    {
        $this->php_error_handler = $handler;

        return $this;
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Throwable              $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @return ResponseInterface
     * @throws Throwable
     */
    protected function handlePhpError(Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->php_error_handler) {
            return call_user_func_array($this->php_error_handler, [$e, $request, $response]);
        }

        throw $e; // No handlers found, so just throw the exception
    }
}
