<?php

namespace React\Espresso;

use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Stack extends \Pimple\Container
{
	public function __construct($app, array $values = array())
	{
		parent::__construct($values);

		$this['uri'] = null;

		$this['loop'] = function () {
			return Factory::create();
		};

		$this['socket'] = function ($stack) {
			return new SocketServer($stack['uri'], $stack['loop']);
		};

		$this['http'] = function ($stack) {
			return new HttpServer($stack['socket']);
		};

		$isFactory = is_object($app) && method_exists($app, '__invoke');
		$this['app'] = $isFactory ? $this->protect($app) : $app;
	}

	public function listen($port, $host = '127.0.0.1')
	{
		$this['uri'] = $host . ':' . $port;
		$this['http']->on('request', $this['app']);
		$this['loop']->run();
	}
}
