<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Controller;
use Innmind\Rest\Server\{
    Routing\Routes,
    Controller as RestController,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Controllers
{
    /**
     * @return MapInterface<string, Controller>
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
        MapInterface $routesToDefinitions
    ): MapInterface {
        $controllers = new Map('string', Controller::class);

        foreach ($routes as $route) {
            $controller = ${(string) $route->action()};

            $controllers = $controllers->put(
                $route->name().'.'.$route->action(),
                new BridgeController($controller, $routesToDefinitions)
            );
        }

        return $controllers;
    }
}
