<?php

namespace React\Espresso;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Stack extends \Pimple\Container
{
    public function __construct($app)
    {
        parent::__construct($app);

        $this['loop'] = function () {
            return Factory::create();
        };

        $this['socket'] = function ($stack) {
            return new SocketServer($stack['loop']);
        };

        $this['http'] = function ($stack) {
            return new HttpServer($stack['socket']);
        };

        $isFactory = is_object($app) && method_exists($app, '__invoke');
        $this['app'] = $isFactory ? $this->protect($app) : $app;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $this['http']->on('request', $this['app']);
        $this['socket']->listen($port, $host);
        $this['loop']->run();
    }
}
