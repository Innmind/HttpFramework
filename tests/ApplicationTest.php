<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Application,
    RequestHandler,
};
use Innmind\OperatingSystem\{
    OperatingSystem,
    Factory,
};
use Innmind\Http\{
    Message\Environment,
    Message\ServerRequest,
    Message\Response,
    ProtocolVersion,
};
use Innmind\Url\Path;
use Innmind\SilentCartographer\OperatingSystem as SilentCartographer;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ApplicationTest extends TestCase
{
    use BlackBox;

    public function testByDefaultItReturnsAnHelloWorld()
    {
        $app = Application::of(
            $this->createMock(OperatingSystem::class),
            new Environment,
        )->disableSilentCartographer();
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));

        $response = $app->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Hello World!', $response->body()->toString());
    }

    public function testAbilityToSpecifyHandler()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $app = Application::of(
            $this->createMock(OperatingSystem::class),
            new Environment,
        )
            ->disableSilentCartographer()
            ->handler(static fn() => $handler);

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }

    public function testLoadDotEnv()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $app = Application::of(
            Factory::build(),
            new Environment,
        )
            ->configAt(Path::of(__DIR__.'/../fixtures/'))
            ->disableSilentCartographer()
            ->handler(function($os, $env) use ($handler) {
                $this->assertTrue($env->contains('FOO'));
                $this->assertTrue($env->contains('BAR'));
                $this->assertSame('bar', $env->get('FOO'));
                $this->assertSame('baz', $env->get('BAR'));

                return $handler;
            });

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }

    public function testDoesntLoadDotEnvWhenConfigFolderDoesntExist()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $app = Application::of(
            Factory::build(),
            new Environment,
        )
            ->configAt(Path::of(__DIR__.'/../unknown/'))
            ->disableSilentCartographer()
            ->handler(function($os, $env) use ($handler) {
                $this->assertFalse($env->contains('FOO'));
                $this->assertFalse($env->contains('BAR'));

                return $handler;
            });

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }

    public function testDoesntLoadDotEnvWhenConfigFolderDoesntContainDotEnvFile()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $app = Application::of(
            Factory::build(),
            new Environment,
        )
            ->configAt(Path::of(__DIR__.'/../src/'))
            ->disableSilentCartographer()
            ->handler(function($os, $env) use ($handler) {
                $this->assertFalse($env->contains('FOO'));
                $this->assertFalse($env->contains('BAR'));

                return $handler;
            });

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }

    public function testEnableSilentCartographerByDefault()
    {
        $this
            ->forAll(Set\Elements::of('PWD', 'SCRIPT_FILENAME'))
            ->then(function($location) {
                $request = $this->createMock(ServerRequest::class);
                $handler = $this->createMock(RequestHandler::class);
                $handler
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($request)
                    ->willReturn($expected = $this->createMock(Response::class));

                $app = Application::of(
                    Factory::build(),
                    new Environment(
                        Map::of('string', 'string')
                            ($location, __DIR__),
                    ),
                )->handler(function($os, $env) use ($handler) {
                    $this->assertInstanceOf(SilentCartographer::class, $os);

                    return $handler;
                });

                $response = $app->handle($request);

                $this->assertSame($expected, $response);
            });
    }

    public function testSilentCartographerWhenSpecified()
    {
        $this
            ->forAll(Set\Elements::of('PWD', 'SCRIPT_FILENAME'))
            ->then(function($location) {
                $request = $this->createMock(ServerRequest::class);
                $handler = $this->createMock(RequestHandler::class);
                $handler
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($request)
                    ->willReturn($expected = $this->createMock(Response::class));

                $app = Application::of(
                    Factory::build(),
                    new Environment(
                        Map::of('string', 'string')
                            ($location, __DIR__),
                    ),
                )
                    ->disableSilentCartographer()
                    ->handler(function($os, $env) use ($handler) {
                        $this->assertNotInstanceOf(SilentCartographer::class, $os);

                        return $handler;
                    });

                $response = $app->handle($request);

                $this->assertSame($expected, $response);
            });
    }

    public function testUseResilientOperatingSystem()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $app = Application::of(
            Factory::build(),
            new Environment,
        )
            ->disableSilentCartographer()
            ->handler(function($os, $env) use ($handler) {
                $this->assertNotInstanceOf(OperatingSystem\Resilient::class, $os);

                return $handler;
            });

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }

    public function testRequestHandlerExceptionsAreNotCaughtByDefault()
    {
        $request = $this->createMock(ServerRequest::class);
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException($expected = new \RuntimeException));

        $app = Application::of(
            Factory::build(),
            new Environment,
        )
            ->disableSilentCartographer()
            ->handler(static fn($os, $env) => $handler);

        try {
            $app->handle($request);

            $this->fail('It should throw');
        } catch (\Throwable $e) {
            $this->assertSame($expected, $e);
        }
    }

    public function testRequestHandlerExceptionsAreCaughtWhenDebugEnvVariableIsSet()
    {
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));
        $handler = $this->createMock(RequestHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException($expected = new \RuntimeException));

        $app = Application::of(
            Factory::build(),
            new Environment(
                Map::of('string', 'string')
                    ('DEBUG', '')
            ),
        )
            ->disableSilentCartographer()
            ->handler(static fn($os, $env) => $handler);

        $response = $app->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->statusCode()->value());
        // No test on the content as we are in the cli and whoops won't render the page
    }
}
