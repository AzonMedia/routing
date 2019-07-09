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
     * Adds a new routing map.
     * The maps are matched in the order they were added.
     * Returns FALSE if the provided $routingMap is already added.
     * @param RoutingMapInterface $RoutingMap
     * @return bool
     */
    public function add_routing_map(RoutingMapInterface $RoutingMap) : bool
    {
        $ret = FALSE;
        foreach ($this->routing_maps as $RegisteredRoutingMap) {
            if (get_class($RegisteredRoutingMap) === get_class($RoutingMap)) {
                return $ret;
            }
        }
        $this->routing_maps[] = $RoutingMap;
        return $ret;
    }

    /**
     * {@inheritDoc}
     * @param string $uri
     * @return callable|null
     */
    public function match_uri(string $uri) : ?callable
    {
        $ret = NULL;
        foreach ($this->routing_maps as $RoutingMap) {
            $ret = $RoutingMap->match_uri($uri);
            if ($ret) {
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
}