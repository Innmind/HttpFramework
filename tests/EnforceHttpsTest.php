<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    EnforceHttps,
    RequestHandler,
};
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    ProtocolVersion,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class EnforceHttpsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new EnforceHttps($this->createMock(RequestHandler::class))
        );
    }

    public function testRedirectWhenNonHttps()
    {
        $handle = new EnforceHttps(
            $inner = $this->createMock(RequestHandler::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('http://localhost/'));
        $request
            ->expects($this->once())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));
        $inner
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame(
            308,
            $handle($request)->statusCode()->value()
        );
    }

    public function testForward()
    {
        $handle = new EnforceHttps(
            $inner = $this->createMock(RequestHandler::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::of('https://localhost/'));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));

        $this->assertSame($response, $handle($request));
    }
}
