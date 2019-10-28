<?php
declare(strict_types=1);

namespace Azonmedia\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;

interface RoutingMapInterface
{

    public function get_routing_map() : iterable ;

    /**
     * Returns a callable if a route is found to correspond to the provided Request, otherwise returns NULL.
     * @param RequestInterface $Request
     * @return RequestInterface
     */
    public function match_request(RequestInterface $Request) : RequestInterface;

    public function get_meta_data(RequestInterface $Request) : ?array ;
}