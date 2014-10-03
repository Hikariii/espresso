<?php

namespace React\Espresso;

use Silex\ControllerCollection as BaseControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

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
            $request = $sfRequest->attributes->get('react.espresso.request');
            $response = $sfRequest->attributes->get('react.espresso.response');

            call_user_func($controller, $request, $response);

            return new SymfonyStreamedResponse();
        };
    }
}
