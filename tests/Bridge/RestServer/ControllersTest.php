<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\{
    Bridge\RestServer\Controllers,
    Controller,
};
use Innmind\Rest\Server\{
    Controller as RestController,
    Routing\Routes,
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
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class ControllersTest extends TestCase
{
    public function testFrom()
    {
        $definition = new HttpResource(
            'bar',
            new Gateway('watev'),
            new Identity('uuid'),
            Set::of(Property::class)
        );

        $controllers = Controllers::from(
            new Routes(
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
            ),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            $this->createMock(RestController::class),
            Map::of(Route::class, HttpResource::class)
        );

        $this->assertInstanceOf(Map::class, $controllers);
        $this->assertSame('string', (string) $controllers->keyType());
        $this->assertSame(Controller::class, (string) $controllers->valueType());
        $this->assertCount(8, $controllers);
    }
}
