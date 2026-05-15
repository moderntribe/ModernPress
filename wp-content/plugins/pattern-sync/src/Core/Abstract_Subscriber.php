<?php declare(strict_types=1);

namespace PatternSync\Core;

use PatternSync\Core\Interfaces\Subscriber_Interface;
use Psr\Container\ContainerInterface;

abstract class Abstract_Subscriber implements Subscriber_Interface {

	protected ContainerInterface $container;

	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

}
