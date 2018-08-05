<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    EnforceHttps,
    RequestHandler,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
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
            ->willReturn(Url::fromString('http://localhost/'));
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
            ->willReturn(Url::fromString('https://localhost/'));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));

        $this->assertSame($response, $handle($request));
    }
}
