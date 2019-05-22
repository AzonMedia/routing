<?php


namespace Guzaba2\Mvc;


use Guzaba2\Base\Base;

/**
 * Class RoutingMapArray
 * @example
 * $routing_map['/'] = [some\ns\home::class, 'method'];
 * $routing_map['/articles'] = [some\ns\articles::class, 'method'];
 * @package Guzaba2\Mvc
 */
class RoutingMapArray extends Base
{

    /**
     * @var array
     */
    protected $routing_map = [];

    public function __construct(array $routing_map)
    {
        $this->routing_map;
    }

    public function match(string $uri) : ?callable
    {
        if ( ($key = array_search($uri, $this->routing_map) ) !== FALSE) {
            $controller = $this->routing_map[$key];
            if (is_array($controller)) {
                //this needs to be converted to a callable if the provided method is not static
                //if it is static then this is a valid callable
                if (count($controller) !=2 ) {
                    throw new
                }
            }
        }
    }
}