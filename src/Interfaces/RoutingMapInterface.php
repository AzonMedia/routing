<?php

namespace Azonmedia\Routing\Interfaces;

interface RoutingMapInterface
{
    public function match(string $uri) : ?callable;
}