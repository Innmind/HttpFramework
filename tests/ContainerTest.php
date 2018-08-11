<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\Controller;
use Innmind\Router\{
    RequestMatcher,
    Route,
    Route\Name,
};
use Innmind\Http\Message\ServerRequest;
use Innmind\Compose\ContainerBuilder\ContainerBuilder;
use Innmind\Url\{
    PathInterface,
    Path,
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testLoad()
    {
        $container = (new ContainerBuilder)(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('routes', Set::of(PathInterface::class))
                ->put('controllers', new Map('string', Controller::class))
        );

        $request = $this->createMock(ServerRequest::class);

        $this->assertSame(
            404,
            $container->get('router')($request)->statusCode()->value()
        );
    }

    public function testOverrideRequestMatcher()
    {
        $container = (new ContainerBuilder)(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('routes', Set::of(PathInterface::class))
                ->put('controllers', new Map('string', Controller::class))
                ->put('requestMatcher', new class implements RequestMatcher {
                    public function __invoke(ServerRequest $request): Route {
                        return Route::of(new Name('foo'), Str::of('GET /'));
                    }
                })
        );

        $request = $this->createMock(ServerRequest::class);

        $this->assertSame(
            501,
            $container->get('router')($request)->statusCode()->value()
        );
    }
}
