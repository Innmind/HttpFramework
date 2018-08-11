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

        $response = $unauthorize(
            $this->createMock(ServerRequest::class),
            new \Exception
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->statusCode()->value());
    }
}
