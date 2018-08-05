<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Router,
    RequestHandler,
    Exception\UnexpectedValueException,
};
use Innmind\Router\{
    RequestMatcher,
    Route,
    Route\Name,
    Exception\NoMatchingRouteFound,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode\StatusCode,
};
use Innmind\Url\Url;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            new Router(
                $this->createMock(RequestMatcher::class),
                new Map('string', 'callable')
            )
        );
    }

    public function testThrowWhenInvalidHandlersKeyType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, callable>');

        new Router(
            $this->createMock(RequestMatcher::class),
            new Map('int', 'callable')
        );
    }

    public function testThrowWhenInvalidHandlersValueType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, callable>');

        new Router(
            $this->createMock(RequestMatcher::class),
            new Map('string', RequestHandler::class)
        );
    }

    public function testErrorWhenNotMatchingRoute()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            new Map('string', 'callable')
        );
        $request = $this->createMock(ServerRequest::class);
        $matcher
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->will($this->throwException(new NoMatchingRouteFound));

        $response = $route($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->statusCode()->value());
    }

    public function testErrorWhenNoHandlerForMatchedRoute()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            new Map('string', 'callable')
        );
        $request = $this->createMock(ServerRequest::class);
        $matcher
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(Route::of(new Name('foo'), Str::of('GET /foo')));

        $response = $route($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(501, $response->statusCode()->value());
    }

    public function testThrowWhenHandlerDoesntReturnResponse()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            (new Map('string', 'callable'))
                ->put('foo', function(){})
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('url')
            ->willReturn(Url::fromString('/foo'));
        $matcher
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(Route::of(new Name('foo'), Str::of('GET /foo')));

        $this->expectException(UnexpectedValueException::class);

        $route($request);
    }

    public function testInvokation()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            (new Map('string', 'callable'))
                ->put('foo', function(Name $route, string $baz, string $bar, ServerRequest $request): Response {
                    return new Response\Response(
                        $code = StatusCode::of('OK'),
                        $code->associatedReasonPhrase(),
                        $request->protocolVersion(),
                        null,
                        new StringStream("$bar $baz from $route")
                    );
                })
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('url')
            ->willReturn(Url::fromString('/foo/hello/world'));
        $matcher
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(Route::of(new Name('foo'), Str::of('GET /foo/{bar}/{baz}')));

        $response = $route($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->statusCode()->value());
        $this->assertSame('hello world from foo', (string) $response->body());
    }
}
