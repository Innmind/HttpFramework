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
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testByDefaultItReturnsAnHelloWorld()
    {
        $app = Application::of(
            $this->createMock(OperatingSystem::class),
            new Environment,
        );
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
        )->handler(fn() => $handler);

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
            ->handler(function($os, $env) use ($handler) {
                $this->assertFalse($env->contains('FOO'));
                $this->assertFalse($env->contains('BAR'));

                return $handler;
            });

        $response = $app->handle($request);

        $this->assertSame($expected, $response);
    }
}
