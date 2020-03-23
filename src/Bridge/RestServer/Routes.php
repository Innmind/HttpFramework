<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\Rest\Server\{
    Routing\Routes as RestRoutes,
    Definition\HttpResource,
    Action,
};
use Innmind\Router\Route;
use Innmind\Http\Message\Method;
use Innmind\Immutable\Map;

final class Routes
{
    /**
     * @return Map<Route, HttpResource>
     */
    public static function from(RestRoutes $routes): Map
    {
        $map = Map::of(Route::class, HttpResource::class);

        foreach ($routes as $route) {
            switch ($route->action()) {
                case Action::list():
                case Action::get():
                    $method = Method::get();
                    break;

                case Action::create():
                    $method = Method::post();
                    break;

                case Action::update():
                    $method = Method::put();
                    break;

                case Action::remove():
                    $method = Method::delete();
                    break;

                case Action::link():
                    $method = Method::link();
                    break;

                case Action::unlink():
                    $method = Method::unlink();
                    break;

                case Action::options():
                    $method = Method::options();
                    break;
            }

            $map = $map->put(
                new Route(
                    new Route\Name($route->name()->toString().'.'.$route->action()->toString()),
                    $route->template(),
                    $method
                ),
                $route->definition()
            );
        }

        return $map;
    }
}
