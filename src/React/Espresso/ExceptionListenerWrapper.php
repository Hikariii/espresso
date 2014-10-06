<?php

namespace React\Espresso;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

class ExceptionListenerWrapper extends \Silex\ExceptionListenerWrapper
{

    public function __invoke(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->callback = $this->app['callback_resolver']->resolveCallback($this->callback);

        if (!$this->shouldRun($exception)) {
            return;
        }

        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $sfRequest = $event->getRequest();
        $request = $sfRequest->attributes->get('react.espresso.request');
        $response = $sfRequest->attributes->get('react.espresso.response');
        call_user_func($this->callback, $exception, $request, $response, $code);
        $response = new SymfonyStreamedResponse();

        $this->ensureResponse($response, $event);
    }
}
