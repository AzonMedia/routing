<?php
declare(strict_types=1);

namespace Azonmedia\Routing;

use Azonmedia\Routing\Interfaces\RouterInterface;
use Azonmedia\Routing\Interfaces\RoutingMapInterface;
use Psr\Http\Message\RequestInterface;

class Router
implements RouterInterface
{

    /**
     * @var array of RoutingMapInterface
     */
    protected $routing_maps = [];

    /**
     * Router constructor.
     * Expects to have a RoutingMap provided - this is the promary routing map.
     * @param RoutingMapInterface $RoutingMap
     */
    public function __construct(RoutingMapInterface $RoutingMap)
    {
        $this->add_routing_map($RoutingMap);
    }


    /**
     * Returns all routes serving $method and optionally filtered by $regex
     * @param string $method
     * @param string $regex
     * @return array
     */
    public function get_routes(int $method, string $regex = '') : array {
        $ret = [];
        foreach ($this->routing_maps as $RoutingMap) {
            $ret = [...$ret, ...$RoutingMap->get_routes($method, $regex)];
        }
        return $ret;
    }

    /**
     * Adds a new routing map.
     * The maps are matched in the order they were added.
     * Returns FALSE if the provided $routingMap is already added.
     * @param RoutingMapInterface $RoutingMap
     * @return bool
     */
    public function add_routing_map(RoutingMapInterface $RoutingMap) : bool
    {
        $ret = FALSE;
        if (!$this->has_routing_map($RoutingMap)) {
            $this->routing_maps[] = $RoutingMap;
            $ret = TRUE;
        }
        return $ret;
    }

    public function has_routing_map(RoutingMapInterface $RoutingMap) : bool
    {
        $ret = FALSE;
        foreach ($this->routing_maps as $RegisteredRoutingMap) {
            if (get_class($RegisteredRoutingMap) === get_class($RoutingMap)) {
                $ret = TRUE;
                break;
            }
        }
        return $ret;
    }

    /**
     * {@inheritDoc}
     * @param RequestInterface $Request
     * @return callable|null
     */
    public function match_request(RequestInterface $Request) : ?RequestInterface
    {
        $ret = NULL;
        foreach ($this->routing_maps as $RoutingMap) {
            $ret = $RoutingMap->match_request($Request);
            if ($ret) {
                break;
            }
        }
        return $ret;
    }

    public function get_meta_data(RequestInterface $Request) : ?array
    {
        $ret = NULL;
        foreach ($this->routing_maps as $RoutingMap) {
            $ret = $RoutingMap->get_meta_data($Request);
            if ($ret) {
                break;
            }
        }
        return $ret;
    }

    /**
     * {@inheritDoc}
     * @param array $routing_map_1
     * @param array $routing_map_2
     * @return array
     */
    public static function merge_routes(array $routing_map_1, array $routing_map_2) : array
    {
        $ret = $routing_map_1;
        foreach ($routing_map_2 as $route => $methods) {
            if (!isset($ret[$route])) {
                $ret[$route] = $methods;
            } else {
                foreach ($methods as $method => $controller) {
                    //the methods are bitmasks and can combine multiple methods handled by the same controller
                    $method_found = FALSE;
                    foreach ($ret[$route] as $ret_method => $ret_controller) {
                        if ($ret_method & $method) {
                            $method_found = TRUE;
                        }
                    }
                    if (!$method_found) {
                        $ret[$route][$method] = $controller;
                    }
                }
            }
        }
        return $ret;
    }
}