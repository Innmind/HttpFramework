<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Bridge\RestServer\Routes;
use Innmind\Rest\Server\{
    Routing\Routes as RestRoutes,
    Routing\Route as RestRoute,
    Routing\Name,
    Definition\HttpResource,
    Definition\Gateway,
    Definition\Identity,
    Definition\Property,
    Action,
};
use Innmind\Router\Route;
use Innmind\Immutable\{
    MapInterface,
    Set,
};
use PHPUnit\Framework\TestCase;

class RoutesTest extends TestCase
{
    public function testFrom()
    {
        $definition = new HttpResource(
            'bar',
            new Gateway('watev'),
            new Identity('uuid'),
            Set::of(Property::class)
        );

        $routes = Routes::from(new RestRoutes(
            RestRoute::of(
                Action::get(),
                new Name('bar.get'),
                $definition
            ),
            RestRoute::of(
                Action::list(),
                new Name('bar.list'),
                $definition
            ),
            RestRoute::of(
                Action::create(),
                new Name('bar.create'),
                $definition
            ),
            RestRoute::of(
                Action::update(),
                new Name('bar.update'),
                $definition
            ),
            RestRoute::of(
                Action::remove(),
                new Name('bar.remove'),
                $definition
            ),
            RestRoute::of(
                Action::link(),
                new Name('bar.link'),
                $definition
            ),
            RestRoute::of(
                Action::unlink(),
                new Name('bar.unlink'),
                $definition
            ),
            RestRoute::of(
                Action::options(),
                new Name('bar.options'),
                $definition
            )
        ));

        $this->assertInstanceOf(MapInterface::class, $routes);
        $this->assertSame(Route::class, (string) $routes->keyType());
        $this->assertSame(HttpResource::class, (string) $routes->valueType());
        $this->assertCount(8, $routes);
        $this->assertCount(1, $routes->values()->distinct());
    }
}
