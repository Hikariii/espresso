<?php

namespace React\Espresso;

use Silex\ControllerCollection as BaseControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;
use React\Http\Request as ReactRequest;
use React\Http\Response as ReactResponse;

class ControllerCollection extends BaseControllerCollection
{
    /**
     * @param string $pattern
     * @param null $to
     * @return \Silex\Controller
     */
    public function match($pattern, $to = null)
    {
        $to = (null === $to ? $this->defaultController : $to);
        $wrapped = $this->wrapController($to);

        return parent::match($pattern, $wrapped);
    }

    /**
     * @param $controller
     * @return callable
     */
    private function wrapController($controller)
    {
        return function (Request $sfRequest) use ($controller) {

            /** @var ReactRequest $request */
            /** @var ReactResponse $response */
            $request = $sfRequest->attributes->get('react.espresso.request');
            $response = $sfRequest->attributes->get('react.espresso.response');

            call_user_func_array($controller, $this->getArguments($sfRequest, $request, $response, $controller));

            return new SymfonyStreamedResponse();
        };
    }

    /**
     * @param Request $sfRequest
     * @param ReactRequest $request
     * @param ReactResponse $response
     * @param $controller
     * @return array
     */
    public function getArguments(Request $sfRequest, ReactRequest $request, ReactResponse $response, $controller)
    {
        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && !$controller instanceof \Closure) {
            $r = new \ReflectionObject($controller);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }

        return $this->doGetArguments($sfRequest, $request, $response, $controller, $r->getParameters());
    }

    /**
     * @param Request $sfRequest
     * @param ReactRequest $request
     * @param ReactResponse $response
     * @param $controller
     * @param array $parameters
     * @return array
     */
    protected function doGetArguments(Request $sfRequest, ReactRequest $request, ReactResponse $response, $controller, array $parameters)
    {
        $attributes = $sfRequest->attributes->all();
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                $arguments[] = $attributes[$param->name];
            } elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;
            } elseif ($param->getClass() && $param->getClass()->isInstance($response)) {
                $arguments[] = $response;
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($controller)) {
                    $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                } elseif (is_object($controller)) {
                    $repr = get_class($controller);
                } else {
                    $repr = $controller;
                }

                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }
}
