<?php
declare(strict_types=1);

namespace Azonmedia\Routing;

class RouteParser
{
    const VARIABLES_REGEX = '~{([^\/}]+)}~';

    public function parse(string $uri) : ?array
    {
        $regex_uri = preg_replace_callback(self::VARIABLES_REGEX, function($matches) {
            return '(.*)';
        }, $uri, -1, $count);
        
        if ($count) {
            $ret = [
                'path' => $regex_uri,
                'original_path' => $uri,
                'matches' => 0,
                'arguments' => []
            ];
            preg_match_all(self::VARIABLES_REGEX, $uri, $out, \PREG_SET_ORDER);

            foreach($out as $value) {
                $ret['matches']++;
                array_push($ret['arguments'], $value[1]);
            }
            return $ret;
        } else {
            return NULL;
        }
    }
}