<?php
declare(strict_types=1);

namespace Azonmedia\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;

interface RoutingMapInterface
{

    /**
     * Returns a callable if a route is found to correspond to the provided URI, otherwise returns NULL.
     * @param string $uri
     * @return callable|null
     */
    public function match_uri(string $uri) : ?callable;

    /**
     * Returns a callable if a route is found to correspond to the provided Request, otherwise returns NULL.
     * @param RequestInterface $Request
     * @return RequestInterface
     */
    public function match_request(RequestInterface $Request) : RequestInterface;
}