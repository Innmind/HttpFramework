<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Authenticate;

use Innmind\HttpFramework\Authenticate\{
    MalformedAuthorizationHeader,
    Fallback,
};
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    ProtocolVersion,
};
use PHPUnit\Framework\TestCase;

class MalformedAuthorizationHeaderTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Fallback::class, new MalformedAuthorizationHeader);
    }

    public function testInvokation()
    {
        $unauthorize = new MalformedAuthorizationHeader;
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->once())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));

        $response = $unauthorize(
            $request,
            new \Exception
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->statusCode()->value());
        $this->assertSame('Malformed authorization header', $response->body()->toString());
    }
}
