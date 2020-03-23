<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Controller;
use Innmind\Rest\Server\{
    Routing\Routes,
    Controller as RestController,
};
use Innmind\Immutable\Map;

final class Controllers
{
    /**
     * @return Map<string, Controller>
     */
    public static function from(
        Routes $routes,
        RestController $create,
        RestController $get,
        RestController $list,
        RestController $options,
        RestController $remove,
        RestController $update,
        RestController $link,
        RestController $unlink,
        Map $routesToDefinitions
    ): Map {
        $controllers = Map::of('string', Controller::class);

        foreach ($routes as $route) {
            $controller = ${$route->action()->toString()};

            $controllers = $controllers->put(
                $route->name()->toString().'.'.$route->action()->toString(),
                new BridgeController($controller, $routesToDefinitions)
            );
        }

        return $controllers;
    }
}
