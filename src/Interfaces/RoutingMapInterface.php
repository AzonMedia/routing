<?php

namespace Guzaba2\Mvc\Mvc\Interfaces;

interface RoutingMapInterface
{
    public function match(string $uri, ?string &$rewritten_uri = NULL) : ?callable;
}