<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Router,
    RequestHandler,
    Controller,
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
    MapInterface,
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
                new Map('string', Controller::class)
            )
        );
    }

    public function testThrowWhenInvalidHandlersKeyType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, Innmind\HttpFramework\Controller>');

        new Router(
            $this->createMock(RequestMatcher::class),
            new Map('int', Controller::class)
        );
    }

    public function testThrowWhenInvalidHandlersValueType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, Innmind\HttpFramework\Controller>');

        new Router(
            $this->createMock(RequestMatcher::class),
            new Map('string', RequestHandler::class)
        );
    }

    public function testErrorWhenNotMatchingRoute()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            new Map('string', Controller::class)
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
            new Map('string', Controller::class)
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

    public function testInvokation()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            (new Map('string', Controller::class))
                ->put(
                    'foo',
                    new class implements Controller {
                        public function __invoke(ServerRequest $request, Route $route, MapInterface $arguments): Response
                        {
                            $bar = $arguments->get('bar');
                            $baz = $arguments->get('baz');

                            return new Response\Response(
                                $code = StatusCode::of('OK'),
                                $code->associatedReasonPhrase(),
                                $request->protocolVersion(),
                                null,
                                new StringStream("$bar $baz from {$route->name()}")
                            );
                        }
                    }
                )
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('url')
            ->willReturn(Url::fromString('http://localhost:8000/foo/hello/world'));
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
