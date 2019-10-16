<?php
declare(strict_types=1);

namespace Azonmedia\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;

interface RouterInterface
{

    /**
     * Walks through all added routing maps (in the order they were added) and tried to find a match for the provided uri.
     * Stops and returns at the first one that does return a callable.
     * Returns a callable (or NULL if not found) based on the provided URI
     * @param string $uri
     * @return callable|null
     */
    public function match_uri(string $uri) : ?callable;

    /**
     * Walks through all added routing maps (in the order they were added) and tried to find a match for the provided Request.
     * Stops and returns at the first one that does return a callable.
     * Returns a callable (or NULL if not found) based on the provided Request
     * @param RequestInterface $Request
     * @return callable|null
     */
    public function match_request(RequestInterface $Request) : ?RequestInterface;

    /**
     * Merges the provided $routing_map_2 to $routing_map_1
     * The mathing routes or methods are preserved from $routing_map_1.
     * @param array $routing_map_1
     * @param array $routing_map_2
     * @return array The merged routing map
     */
    public static function merge_routes(array $routing_map_1, array $routing_map_2) : array;
}