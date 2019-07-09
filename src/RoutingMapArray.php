<?php
declare(strict_types=1);

namespace Azonmedia\Routing;

use Azonmedia\Routing\Exceptions\RoutingConfigurationException;
use Azonmedia\Routing\Interfaces\RoutingMapInterface;
use Guzaba2\Http\Method;
use Psr\Http\Message\RequestInterface;

/**
 * Class RoutingMapArray
 * @example
 * $routing_map['/'] = [some\ns\home::class, 'method'];
 * $routing_map['/articles'] = [some\ns\articles::class, 'method'];
 *
 * Change
 * $routing_map['/'] => ['GET' => 'callable1', 'POST' => 'callable2']
 *
 * @package Guzaba2\Mvc
 */
class RoutingMapArray
implements RoutingMapInterface
{

    /**
     * @var array
     */
    protected $routing_map = [];

    /**
     * @var array
     */
    protected $routing_map_regex = [];

    protected $RouteParser;

    public function __construct(array $routing_map)
    {
        $this->routing_map = $routing_map;
        $this->RouteParser = new RouteParser();

        foreach ($routing_map as $path => $value) {
            $new_route = $this->RouteParser->parse($path);
            if ($new_route) {
                $this->routing_map_regex[$new_route['path']] = $value + $new_route;
            }
        }

    }

    /**
     * {@inheritDoc}
     * @param string $uri
     * @return callable|null
     * @throws RoutingConfigurationException
     * @throws \ReflectionException
     */
    public function match_uri(string $uri) : ?callable
    {
        $ret = NULL;

        $method_const = Method::HTTP_GET;

        if ( ($route = array_search($uri, $this->routing_map) ) !== FALSE) {
            if (isset($this->routing_map[$route][$method_const])) {
                $controller = $this->routing_map[$route][$method_const];//if only URI matching is done then we use the GET method
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

        } else {
            
        }

        $Request = $Request->withAttribute('controller_callable', $ret);

        return $Request;
    }

    /**
     * {@inheritDoc}
     * @param RequestInterface $Request
     * @return callable|null
     * @throws RoutingConfigurationException
     * @throws \ReflectionException
     */
    public function match_request(RequestInterface $Request) : RequestInterface
    {
        //$ret = $this->match_uri( (string) $Request->getUri() );
        //we must take into account the method as well
        $method_const = $Request->getMethodConstant();

        $path = $Request->getUri()->getPath();
        $ret = NULL;

        //if ( ($route = array_search( $path , $this->routing_map) ) !== FALSE) {
            if (isset($this->routing_map[$path])) {
                $route = $path;
            if (isset($this->routing_map[$route][$method_const])) {
                $controller = $this->routing_map[$route][$method_const];//if only URI matching is done then we use the GET method
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
                        $ret = [new $class_name($Request), $method_name];
                    }
                } elseif (is_string($controller)) {
                    if (!function_exists($controller)) {
                        throw new RoutingConfigurationException(sprintf('The controller for route %s is set as function which is not defined.', $route));
                    }
                    $ret = $controller;
                } elseif (is_callable($controller)) {
                    //it is already a callable (closure or an invokable class)
                    //this is possible if the routes are defined in PHP
                    $ret = $controller;
                }
            } else {
                //alternatively here we can route to a controller displaying a message that the route is defined but the method is not
                //or leave NULL and leave to the next router to find a match
            }
        } else {
            foreach ($this->routing_map_regex as $path_regex => $arr) {
                if (preg_match("~{$path_regex}~", $path, $matches) === 1 && $arr['matches'] === count($matches) - 1) {
                    if (isset($arr[$method_const])) {
                        $controller = $arr[$method_const];
                        
                        $class_name = $controller[0];
                        $method_name = $controller[1];

                        array_shift($matches);
                        $arguments = array_combine($arr['arguments'], $matches);

                        $Request = $Request->withAttribute('controller_arguments', $arguments);

                        $ret = [new $class_name($Request), $method_name];
                        break;
                    }
                }
            }
        }

        $Request = $Request->withAttribute('controller_callable', $ret);

        return $Request;
    }
}