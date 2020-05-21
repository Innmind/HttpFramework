<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Application,
    RequestHandler,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Http\{
    Message\Environment,
    Message\ServerRequest,
    Message\Response,
    ProtocolVersion,
};
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
}
