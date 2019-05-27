<?php

namespace Azonmedia\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;

interface RoutingMapInterface
{
    public function match_uri(string $uri) : ?callable;

    public function match_request(RequestInterface $Request) : ?callable;
}