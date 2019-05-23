<?php


namespace Azonmedia\Routing;


use Azonmedia\Routing\Interfaces\RoutingMapInterface;
use Psr\Http\Message\RequestInterface;

class Router
{

    /**
     * @var array of RoutingMapInterface
     */
    protected $routing_maps = [];

    public function __construct(RoutingMapInterface $RoutingMap)
    {
        $this->add_routing_map($RoutingMap);
    }

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

    public function match_request(RequestInterface $Request) : ?callable
    {
        $ret = NULL;
        foreach ($this->routing_maps as $RoutingMap) {
            $ret = $RoutingMap->match_request($uri);
            if ($ret) {
                break;
            }
        }
        return $ret;
    }
}