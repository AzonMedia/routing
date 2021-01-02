<?php
declare(strict_types=1);

namespace Azonmedia\Routing;

use Azonmedia\Exceptions\RunTimeException;
use Azonmedia\Http\Method;
use Azonmedia\Routing\Exceptions\RoutingConfigurationException;
use Azonmedia\Routing\Interfaces\RoutingMapInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    protected array $routing_map = [];

    /**
     * Contains the routing meta data.
     * Multidimensional associative array like $routine_meta_data[$route][$method] = ['class' => 'some_class']
     * @var array
     */
    protected array $routing_meta_data = [];

    /**
     * @var array
     */
    protected array $routing_map_regex = [];

    /**
     * @var RouteParser
     */
    protected RouteParser $RouteParser;

    /**
     * RoutingMapArray constructor.
     * @param array $routing_map
     * @param array $routing_meta_data
     */
    public function __construct(array $routing_map, array $routing_meta_data = [])
    {
        $this->routing_map = $routing_map;
        $this->routing_meta_data = $routing_meta_data;

        $this->RouteParser = new RouteParser();

        foreach ($routing_map as $path => $value) {
            $new_route = $this->RouteParser->parse($path);
            if ($new_route) {
                if (isset($this->routing_map_regex[$new_route['path']])) {
                    $controller_1 = $this->routing_map_regex[$new_route['path']][array_key_first($this->routing_map_regex[$new_route['path']])];
                    $controller_2 = $value[array_key_first($value)];
                    if (is_array($controller_1)) {
                        $controller_1 = $controller_1[0].'::'.$controller_1[1];
                    }
                    if (is_array($controller_2)) {
                        $controller_2 = $controller_2[0].'::'.$controller_2[1];
                    }
                    $message = sprintf(
                        'The regex %1$s is already set in the routing_map_regex by %2$s (%3$s) and is attempted to be set again by %4$s (%5$s).',
                        $new_route['path'],
                        $controller_1,
                        $this->routing_map_regex[$new_route['path']]['original_path'],
                        $controller_2,
                        $new_route['original_path']
                    );
                    throw new RunTimeException($message);
                }
                $this->routing_map_regex[$new_route['path']] = $value + $new_route;
            }
        }
    }

    /**
     * Returns all routes serving $method and optionally filtered by $regex
     * @param int $method
     * @param string $regex
     * @return array
     */
    public function get_routes(int $method, string $regex = '') : array
    {
        $ret = [];
        foreach ($this->routing_map as $route => $methods) {
            foreach ($methods as $route_method => $controller) {
                if ($method & $route_method) {
                    if ($regex) {
                        if (preg_match($regex, $route)) {
                            $ret[] = $route;
                        }
                    } else {
                        $ret[] = $route;
                    }
                }
            }

        }
        return $ret;
    }

    /**
     * Adds a route
     * @param string $route
     * @param int $method Method constant from Azonmedia\Http\Method
     * @param callable $controller
     */
    public function add_route(string $route, int $method, callable $controller) : void
    {
        if (isset($this->routing_map[$route])) {
            foreach ($this->routing_map[$route] as $existing_method => $existing_controller) {
                if ($existing_method & $method) {
                    throw new \RuntimeException(sprintf('The method %s already exists (%s) for route %s.', $method, $existing_method, $route));
                }
            }
        }
        $this->routing_map[$route][$method] = $controller;
    }

    /**
     * Returns the whole routing map.
     * @return iterable
     */
    public function get_routing_map() : array
    {
        return $this->routing_map;
    }

    /**
     * @return array
     */
    public function get_all_meta_data() : array
    {
        return $this->routing_meta_data;
    }

    /**
     * Returns the meta data (if there is such) for the route based on the $Request
     * @param ServerRequestInterface $Request
     * @return array|null
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Azonmedia\Exceptions\RunTimeException
     */
    public function get_meta_data(ServerRequestInterface $Request) : ?array
    {
        $ret = NULL;
        $route = $Request->getUri()->getPath();
        $method = Method::get_method_constant($Request);
        if (isset($this->routing_meta_data[$route][$method])) {
            $ret = $this->routing_meta_data[$route][$method];
        } elseif ($route[-1] === '/' && isset($this->routing_meta_data[substr($route, 0, -1)][$method]) ) { //try the same route without the trailing /
            $ret = $this->routing_meta_data[substr($route, 0, -1)][$method];
        } elseif ($route[-1] !== '/' && isset($this->routing_meta_data[$route.'/'][$method]) ) { //try the same route with added trailing /
            $ret = $this->routing_meta_data[$route.'/'][$method];
        }
        return $ret;
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
        return NULL;
    }

    /**
     * Matches /some/path to routes /some/path/ & /some/path
     * Matches /some/path/ to routes /some/path/ & /some/path
     * The presence or lack of a trailing / in either the route or the provided path does not matter.
     * It also supports matching with variables like /some/path/{value}.
     * {@inheritDoc}
     * @param RequestInterface $Request
     * @return RequestInterface
     * @throws RoutingConfigurationException
     * @throws \ReflectionException
     */
    public function match_request(ServerRequestInterface $Request) : ServerRequestInterface
    {

        $Request = $Request->withAttribute('route_meta_data', $this->get_meta_data($Request));

        //$ret = $this->match_uri( (string) $Request->getUri() );
        //we must take into account the method as well
        //$method_const = $Request->getMethodConstant();
        $method_const = Method::get_method_constant($Request);

        $path = $Request->getUri()->getPath();
        $ret = NULL;

        $matched_route = '';

        $path_wo_trailing_slash = $path;
        if ($path[strlen($path) -1] === '/') {
            $path_wo_trailing_slash = substr($path_wo_trailing_slash, 0, strlen($path) -1);
        }
        $path_with_trailing_slash = $path;
        if ($path[strlen($path) -1] !== '/') {
            $path_with_trailing_slash .= '/';
        }

        if (isset($this->routing_map[$path])) {
            //leave path unmodified
        } elseif (isset($this->routing_map[$path_wo_trailing_slash])) {
            $path = $path_wo_trailing_slash;
        } elseif (isset($this->routing_map[$path_with_trailing_slash])) {
            $path = $path_with_trailing_slash;
        }

        if (isset($this->routing_map[$path])) {
            foreach ($this->routing_map[$path] as $method => $controller) {
                if ($method_const & $method) { //bitwise
                    $controller_to_execute = $controller;
                    break 1;
                }
            }
        } else {
            foreach ($this->routing_map_regex as $path_regex => $arr) {
                if (preg_match("~^{$path_regex}$~", $path, $matches) === 1 && $arr['matches'] === count($matches) - 1) {
                    foreach ($arr as $method => $controller) {
                        if (! \is_int($method)) {
                            /**
                             * We have different structure in routing_map_regex and need to skip non-numeric keys
                             * 
                             * ["/api/role/(.*)"]=>
                                    array(7) {
                                    [28]=>
                                    array(2) {
                                        [0]=>
                                        string(41) "Guzaba2\Orm\ActiveRecordDefaultController"
                                        [1]=>
                                        string(4) "read"
                                    }
                                    [160]=>
                                    array(2) {
                                        [0]=>
                                        string(41) "Guzaba2\Orm\ActiveRecordDefaultController"
                                        [1]=>
                                        string(6) "update"
                                    }
                                    [2]=>
                                    array(2) {
                                        [0]=>
                                        string(41) "Guzaba2\Orm\ActiveRecordDefaultController"
                                        [1]=>
                                        string(6) "delete"
                                    }
                                    ["path"]=>
                                    string(14) "/api/role/(.*)"
                                    ["original_path"]=>
                                    string(16) "/api/role/{uuid}"
                                    ["matches"]=>
                                    int(1)
                                    ["arguments"]=>
                                    array(1) {
                                        [0]=>
                                        string(4) "uuid"
                                    }
                                    }

                             * 
                             */
                            continue;
                        }

                        if ($method_const & $method) { //bitwise
                            //$class_name = $controller[0];
                            //$method_name = $controller[1];

                            array_shift($matches);
                            $arguments = array_combine($arr['arguments'], $matches);

                            //$Request = $Request->withAttribute('controller_arguments', $arguments);

                            //$ret = [new $class_name($Request), $method_name];
                            $matched_route = $arr['original_path'];

                            $controller_to_execute = $controller;
                            break 2;
                        }
                    }
                }
            }
        }
        if (!empty($controller_to_execute)) {

            if (is_array($controller_to_execute)) {
                //this needs to be converted to a callable if the provided method is not static
                //if it is static then this is a valid callable
                if (count($controller_to_execute) != 2) {
                    throw new RoutingConfigurationException(sprintf('An invalid number of elements %s is provided as controller for route %s. If the controller is set as an array the number of elements should be 2.', count($controller_to_execute), $route));
                }
                $class_name = $controller_to_execute[0];
                $method_name = $controller_to_execute[1];
                if (!class_exists($class_name)) {
                    throw new RoutingConfigurationException(sprintf('The class %s configured as controller for route %s does not exist.', $class_name, $path));
                }
                $RClass = new \ReflectionClass($class_name);
                if (!$RClass->hasMethod($method_name)) {
                    throw new RoutingConfigurationException(sprintf('The method %s::%s() configured as controller for route %s does not exist.', $class_name, $method_name, $path));
                }
                $RMethod = $RClass->getMethod($method_name);
                if ($RMethod->isStatic()) {
                    $ret = $controller_to_execute;
                } else {
                    //the method is dynamic and an instance needs to be created
                    if (!empty($arguments)) {
                        $Request = $Request->withAttribute('controller_arguments', $arguments);
                    }
                    $ret = [new $class_name($Request), $method_name];
                }
            } elseif (is_string($controller_to_execute)) {
                if (!function_exists($controller_to_execute)) {
                    throw new RoutingConfigurationException(sprintf('The controller %s for route %s is set as function which is not defined.', $controller_to_execute, $route));
                }
                $ret = $controller_to_execute;
            } elseif (is_callable($controller_to_execute)) {
                //it is already a callable (closure or an invokable class)
                //this is possible if the routes are defined in PHP
                $ret = $controller_to_execute;
            }

        }


        $Request = $Request->withAttribute('controller_callable', $ret);//do not set this attribute to the request instance passed to the controller as this will be a circular reference and will postpone the object destruction
        $Request = $Request->withAttribute('matched_route', $matched_route);

        return $Request;
    }
}