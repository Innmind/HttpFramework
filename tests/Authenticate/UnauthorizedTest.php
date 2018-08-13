<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Authenticate;

use Innmind\HttpFramework\Authenticate\{
    Unauthorized,
    Fallback,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class UnauthorizedTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Fallback::class, new Unauthorized);
    }

    public function testInvokation()
    {
        $unauthorize = new Unauthorized;
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn(Url::fromString('http://user:password@sub.example.com:8000/somewhere'));

        $response = $unauthorize($request, new \Exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->statusCode()->value());
        $this->assertTrue($response->headers()->has('WWW-Authenticate'));
        $this->assertSame(
            'WWW-Authenticate : Basic realm=sub.example.com:8000',
            (string) $response->headers()->get('WWW-Authenticate')
        );
    }
}
