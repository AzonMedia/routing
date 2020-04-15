<?php
declare(strict_types=1);

namespace Azonmedia\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RoutingMapInterface
{

    public function get_routing_map() : iterable ;

    /**
     * Returns a callable if a route is found to correspond to the provided Request, otherwise returns NULL.
     * @param RequestInterface $Request
     * @return RequestInterface
     */
    public function match_request(ServerRequestInterface $Request) : ServerRequestInterface;

    public function get_meta_data(ServerRequestInterface $Request) : ?array ;
}