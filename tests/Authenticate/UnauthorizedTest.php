<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Authenticate;

use Innmind\HttpFramework\Authenticate\{
    Unauthorized,
    Fallback,
};
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    ProtocolVersion,
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
            ->willReturn(Url::of('http://user:password@sub.example.com:8000/somewhere'));
        $request
            ->expects($this->once())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));

        $response = $unauthorize($request, new \Exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->statusCode()->value());
        $this->assertTrue($response->headers()->contains('WWW-Authenticate'));
        $this->assertSame(
            'WWW-Authenticate: Basic realm=sub.example.com:8000',
            $response->headers()->get('WWW-Authenticate')->toString(),
        );
    }
}
