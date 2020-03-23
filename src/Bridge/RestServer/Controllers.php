<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Controller;
use Innmind\Rest\Server\{
    Routing\Routes,
    Controller as RestController,
    Definition\HttpResource,
};
use Innmind\Router\Route;
use Innmind\Immutable\Map;

final class Controllers
{
    /**
     * @param Map<Route, HttpResource> $routesToDefinitions
     *
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
        /** @var Map<string, Controller> */
        $controllers = Map::of('string', Controller::class);

        foreach ($routes as $route) {
            /** @var RestController */
            $controller = ${$route->action()->toString()};

            $controllers = ($controllers)(
                $route->name()->toString().'.'.$route->action()->toString(),
                new BridgeController($controller, $routesToDefinitions),
            );
        }

        return $controllers;
    }
}
