<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use function Innmind\HttpFramework\{
    bootstrap,
    env,
};
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
use Innmind\Http\Message\Environment\Environment;
use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    File\File,
    Stream\StringStream,
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

    public function testEnv()
    {
        $env = env(
            new Environment(
                Map::of('string', 'scalar')
                    ('FOO', 'foo')
                    ('BAZ', 'baz')
            ),
            new MemoryAdapter
        );

        $this->assertInstanceOf(MapInterface::class, $env);
        $this->assertSame('string', (string) $env->keyType());
        $this->assertSame('scalar', (string) $env->valueType());
        $this->assertCount(2, $env);
        $this->assertSame('foo', $env->get('foo'));
        $this->assertSame('baz', $env->get('baz'));
    }

    public function testEnvWithDotEnvFile()
    {
        $config = new MemoryAdapter;
        $config->add(new File(
            '.env',
            new StringStream("BAR=42\nFOO_BAR=foobaz")
        ));

        $env = env(
            new Environment(
                Map::of('string', 'scalar')
                    ('FOO', 'foo')
                    ('BAZ', 'baz')
            ),
            $config
        );

        $this->assertInstanceOf(MapInterface::class, $env);
        $this->assertSame('string', (string) $env->keyType());
        $this->assertSame('scalar', (string) $env->valueType());
        $this->assertCount(4, $env);
        $this->assertSame('foo', $env->get('foo'));
        $this->assertSame('baz', $env->get('baz'));
        $this->assertSame('42', $env->get('bar'));
        $this->assertSame('foobaz', $env->get('fooBar'));
    }
}
