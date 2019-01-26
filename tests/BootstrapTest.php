<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use function Innmind\HttpFramework\bootstrap;
use Innmind\HttpFramework\{
    Router,
    Controller,
    EnforceHttps,
    RequestHandler,
    Authenticate,
    Authenticate\Condition,
};
use Innmind\Router\{
    RequestMatcher,
    Route,
};
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Rest\Server\{
    Gateway,
    Definition\Directory,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
    Str,
};
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $handlers = bootstrap();

        $this->assertInternalType('callable', $handlers['router']);
        $this->assertInstanceOf(
            Router::class,
            $handlers['router'](
                $this->createMock(RequestMatcher::class),
                new Map('string', Controller::class)
            )
        );
        $this->assertInternalType('callable', $handlers['enforce_https']);
        $this->assertInstanceOf(
            EnforceHttps::class,
            $handlers['enforce_https']($this->createMock(RequestHandler::class))
        );
        $this->assertInternalType('callable', $handlers['authenticate']);
        $authenticate = $handlers['authenticate'](
            $this->createMock(Authenticator::class),
            new Condition('~^/~')
        );
        $this->assertInternalType('callable', $authenticate);
        $this->assertInstanceOf(
            AUthenticate::class,
            $authenticate($this->createMock(RequestHandler::class))
        );

        $this->assertInternalType('callable', $handlers['bridge']['rest_server']);
        $rest = $handlers['bridge']['rest_server'](
            Map::of('string', Gateway::class),
            Directory::of('api', Set::of(Directory::class)),
            Route::of(new Route\Name('capabilities'), Str::of('OPTIONS /\*'))
        );
        $this->assertInternalType('array', $rest);
        $this->assertInstanceOf(SetInterface::class, $rest['routes']);
        $this->assertSame(Route::class, (string) $rest['routes']->type());
        $this->assertInstanceOf(MapInterface::class, $rest['controllers']);
        $this->assertSame('string', (string) $rest['controllers']->keyType());
        $this->assertSame(Controller::class, (string) $rest['controllers']->valueType());
        $this->assertTrue($rest['controllers']->contains('capabilities'));
    }
}
