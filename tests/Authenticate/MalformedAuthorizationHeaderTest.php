<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Authenticate;

use Innmind\HttpFramework\Authenticate\{
    MalformedAuthorizationHeader,
    Fallback,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
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

        $response = $unauthorize(
            $this->createMock(ServerRequest::class),
            new \Exception
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(400, $response->statusCode()->value());
        $this->assertSame('Malformed authorization header', (string) $response->body());
    }
}
