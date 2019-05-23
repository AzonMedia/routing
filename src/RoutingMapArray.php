<?php

namespace Azonmedia\Routing;


use Azonmedia\Routing\Exceptions\RoutingConfigurationException;

/**
 * Class RoutingMapArray
 * @example
 * $routing_map['/'] = [some\ns\home::class, 'method'];
 * $routing_map['/articles'] = [some\ns\articles::class, 'method'];
 * @package Guzaba2\Mvc
 */
class RoutingMapArray
{

    /**
     * @var array
     */
    protected $routing_map = [];

    public function __construct(array $routing_map)
    {
        $this->routing_map = $routing_map;
    }

    public function match(string $uri) : ?callable
    {
        $ret = NULL;
        if ( ($route = array_search($uri, $this->routing_map) ) !== FALSE) {
            $controller = $this->routing_map[$route];
            if (is_array($controller)) {
                //this needs to be converted to a callable if the provided method is not static
                //if it is static then this is a valid callable
                if (count($controller) != 2) {
                    throw new RoutingConfigurationException(sprintf('An invalid number of elements %s is provided as controller for route %s. If the controller is set as an array the number of elements should be 2.'), count($controller), $route);
                }
                $class_name = $controller[0];
                $method_name = $controller[1];
                if (!class_exists($class_name)) {
                    throw new RoutingConfigurationException(sprintf('The class configured as controller for route %s does not exist.', $route));
                }
                $RClass = new \ReflectionClass($class_name);
                if (!$RClass->hasMethod($method_name)) {
                    throw new RoutingConfigurationException(sprintf('The method configured as controller for route %s does not exist.', $route));
                }
                $RMethod = $RClass->getMethod($method_name);
                if ($RMethod->isStatic()) {
                    $ret = $controller;
                } else {
                    //the method is dynamic and an instance needs to be created
                    $ret = [new $class_name(), $method_name];
                }
            } elseif (is_string($controller)) {
                if (!function_exists($controller)) {
                    throw new RoutingConfigurationException(sprintf('The controller for route %s is set as function which is not defined.', $route));
                }
                $ret = $controller;
            } if (is_callable($controller)) {
                //it is already a callable (closure or an invokable class)
                //this is possible if the routes are defined in PHP
                $ret = $controller;
            }
        }
        return $ret;
    }
}