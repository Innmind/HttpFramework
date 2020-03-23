<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\{
    Bridge\RestServer\BridgeController,
    Controller,
};
use Innmind\Rest\Server\{
    Controller as RestController,
    Definition\HttpResource,
    Definition\Gateway,
    Definition\Identity as IdentityProperty,
    Definition\Property,
    Identity\Identity,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Router\Route;
use Innmind\Immutable\{
    Map,
    Str,
    Set,
};
use PHPUnit\Framework\TestCase;

class BridgeControllerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Controller::class,
            new BridgeController(
                $this->createMock(RestController::class),
                Map::of(Route::class, HttpResource::class)
            )
       );
    }

    public function testThrowWhenInvalidDefinitionMapKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<Innmind\Router\Route, Innmind\Rest\Server\Definition\HttpResource>');

        new BridgeController(
            $this->createMock(RestController::class),
            Map::of('string', HttpResource::class)
        );
    }

    public function testThrowWhenInvalidDefinitionMapValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<Innmind\Router\Route, Innmind\Rest\Server\Definition\HttpResource>');

        new BridgeController(
            $this->createMock(RestController::class),
            Map::of(Route::class, 'callable')
        );
    }

    public function testInvokationWithoutIdentity()
    {
        $route = Route::of(
            new Route\Name('foo'),
            Str::of('GET /foo')
        );
        $definition = new HttpResource(
            'bar',
            new Gateway('watev'),
            new IdentityProperty('uuid'),
            Set::of(Property::class)
        );
        $handle = new BridgeController(
            $controller = $this->createMock(RestController::class),
            Map::of(Route::class, HttpResource::class)
                ($route, $definition)
        );
        $request = $this->createMock(ServerRequest::class);
        $controller
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $request,
                $definition,
                null
            )
            ->willReturn($expected = $this->createMock(Response::class));

        $this->assertSame($expected, $handle(
            $request,
            $route,
            Map::of('string', 'string')
        ));
    }

    public function testInvokationWithIdentity()
    {
        $route = Route::of(
            new Route\Name('foo'),
            Str::of('GET /foo')
        );
        $definition = new HttpResource(
            'bar',
            new Gateway('watev'),
            new IdentityProperty('uuid'),
            Set::of(Property::class)
        );
        $handle = new BridgeController(
            $controller = $this->createMock(RestController::class),
            Map::of(Route::class, HttpResource::class)
                ($route, $definition)
        );
        $request = $this->createMock(ServerRequest::class);
        $controller
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $request,
                $definition,
                new Identity('foobar')
            )
            ->willReturn($expected = $this->createMock(Response::class));

        $this->assertSame($expected, $handle(
            $request,
            $route,
            Map::of('string', 'string')
                ('identity', 'foobar')
        ));
    }
}
