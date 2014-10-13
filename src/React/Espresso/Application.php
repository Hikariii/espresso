<?php

namespace React\Espresso;

use React\Http\Request;
use React\Http\Response;
use Silex\Application as BaseApplication;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class Application extends BaseApplication
{
    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        $this['handle_exceptions'] = false;

        parent::__construct($values);

        $app = $this;

        $this['controllers_factory'] = function () use ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $this['closure_factory'] = function () use ($app) {
            return new ExceptionClosureFactory($app);
        };
    }

    /**
     * Registers an error handler.
     *
     * @param mixed $callback Error handler callback, takes an Exception argument
     * @param int   $priority The higher this value, the earlier an event
     *                        listener will be triggered in the chain (defaults to -8)
     */
    public function error($callback, $priority = -8)
    {
        $this['closure_factory']->setErrorController($callback);
        $this->on(KernelEvents::EXCEPTION, new ExceptionListenerWrapper($this, $callback), $priority);
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $sfRequest = $this->buildSymfonyRequest($request, $response);
        $this->handle($sfRequest, HttpKernelInterface::MASTER_REQUEST, $this['handle_exceptions']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return SymfonyRequest
     */
    private function buildSymfonyRequest(Request $request, Response $response)
    {
        $sfRequest = SymfonyRequest::create($request->getPath(), $request->getMethod());
        $sfRequest->attributes->set('react.espresso.request', $request);
        $sfRequest->attributes->set('react.espresso.response', $response);

        return $sfRequest;
    }
}
