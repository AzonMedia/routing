<?php

declare(strict_types=1);

namespace Azonmedia\Routing;

use Azonmedia\Routing\Interfaces\RouterInterface;
use Azonmedia\Routing\Interfaces\RoutingMapInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Router
 * @package Azonmedia\Routing
 */
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
     * @param int $method
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

    /**
     * Checks is the provided $RoutingMap already added.
     * @param RoutingMapInterface $RoutingMap
     * @return bool
     */
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
     * Always returns aa Request. If there is a match the attribute controller_callable should be checked to exist.
     * {@inheritDoc}
     * @param RequestInterface $Request
     * @return ServerRequestInterface
     */
    public function match_request(ServerRequestInterface $Request) : ServerRequestInterface
    {
        $MatchedRequest = $Request;
        /** @var RoutingMapInterface $RoutingMap */
        foreach ($this->routing_maps as $RoutingMap) {
            $TestMatchedRequest = $RoutingMap->match_request($Request);
            if ($TestMatchedRequest->getAttribute('controller_callable')) {
                $MatchedRequest = $TestMatchedRequest;
                break;
            }
        }
        return $MatchedRequest;
    }

    /**
     * Returns the meta data (if there is such) for the route based on the $Request
     * @param ServerRequestInterface $Request
     * @return array|null
     */
    public function get_meta_data(ServerRequestInterface $Request) : ?array
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