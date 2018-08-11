<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Authenticate,
    RequestHandler,
    Authenticate\Fallback,
};
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class AuthenticateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new Authenticate(
                $this->createMock(RequestHandler::class),
                $this->createMock(Authenticator::class),
                new Map('string', Fallback::class)
            )
        );
    }

    public function testThrowWhenInvalidFallbacksKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type MapInterface<string, Innmind\HttpFramework\Authenticate\Fallback>');

        new Authenticate(
            $this->createMock(RequestHandler::class),
            $this->createMock(Authenticator::class),
            new Map('int', Fallback::class)
        );
    }

    public function testThrowWhenInvalidFallbacksValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type MapInterface<string, Innmind\HttpFramework\Authenticate\Fallback>');

        new Authenticate(
            $this->createMock(RequestHandler::class),
            $this->createMock(Authenticator::class),
            new Map('string', 'callable')
        );
    }

    public function testThrowWhenAuthenticationThrowsAndNoFallback()
    {
        $authenticate = new Authenticate(
            $handler = $this->createMock(RequestHandler::class),
            $authenticator = $this->createMock(Authenticator::class),
            new Map('string', Fallback::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $authenticator
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException($expected = new \Exception));
        $handler
            ->expects($this->never())
            ->method('__invoke');

        try {
            $authenticate($request);

            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame($expected, $e);
        }
    }

    public function testFallbackWhenAuthenticationThrows()
    {
        $authenticate = new Authenticate(
            $handler = $this->createMock(RequestHandler::class),
            $authenticator = $this->createMock(Authenticator::class),
            (new Map('string', Fallback::class))
                ->put('Exception', $fallback = $this->createMock(Fallback::class))
        );
        $request = $this->createMock(ServerRequest::class);
        $authenticator
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException($e = new \Exception));
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->with($request, $e)
            ->willReturn($expected = $this->createMock(Response::class));
        $handler
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame($expected, $authenticate($request));
    }

    public function testForwardHandling()
    {
        $authenticate = new Authenticate(
            $handler = $this->createMock(RequestHandler::class),
            $this->createMock(Authenticator::class),
            new Map('string', Fallback::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($expected = $this->createMock(Response::class));

        $this->assertSame($expected, $authenticate($request));
    }
}
