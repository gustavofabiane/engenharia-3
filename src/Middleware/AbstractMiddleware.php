<?php

namespace Middleware;

use Psr\Container\ContainerInterface as ContainerInterface;

/**
 * Description of AbstractMiddleware
 *
 * @author gusta
 */
class AbstractMiddleware {

    /**
     * Container object
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

}
