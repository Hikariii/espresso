<?php

namespace React\Espresso;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use React\Http\Request;
use React\Http\Response;

class ExceptionClosureFactory
{
    /** @var Application */
    protected $app;
    protected $errorContoller;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Registers the controller for handling errors.
     *
     * @param $callback
     * @return $this
     */
    public function setErrorController($callback)
    {
        $this->errorContoller = $callback;
        return $this;
    }

    /**
     * Get the error controller
     *
     * @return mixed
     */
    public function getErrorController()
    {
        return $this->errorContoller;
    }

    /**
     * @param $callable
     * @param Request $request
     * @param Response $response
     * @return callable
     */
    public function createClosure($callable, Request $request, Response $response)
    {
        if ($this->errorContoller === null) {
            return $callable;
        }

        return function () use ($request, $response, $callable) {
            try
            {
                $args = func_get_args();
                return call_user_func_array($callable, $args);
            } catch (\Exception $exception)
            {
                return $this->callErrorHandler($exception, $request, $response);
            }
        };
    }

    /**
     * @param \Exception $exception
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function callErrorHandler(\Exception $exception, Request $request, Response $response)
    {
        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $args = [$exception, $request, $response, $code];
        return call_user_func_array($this->errorContoller, $args);
    }
}
