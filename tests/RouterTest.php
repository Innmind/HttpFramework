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
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Stream\Readable\Stream;
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
                Map::of('string', Controller::class)
            )
        );
    }

    public function testThrowWhenInvalidHandlersKeyType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, Innmind\HttpFramework\Controller>');

        new Router(
            $this->createMock(RequestMatcher::class),
            Map::of('int', Controller::class)
        );
    }

    public function testThrowWhenInvalidHandlersValueType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, Innmind\HttpFramework\Controller>');

        new Router(
            $this->createMock(RequestMatcher::class),
            Map::of('string', RequestHandler::class)
        );
    }

    public function testErrorWhenNotMatchingRoute()
    {
        $route = new Router(
            $matcher = $this->createMock(RequestMatcher::class),
            Map::of('string', Controller::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));
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
            Map::of('string', Controller::class)
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));
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
            (Map::of('string', Controller::class))
                ->put(
                    'foo',
                    new class implements Controller {
                        public function __invoke(ServerRequest $request, Route $route, Map $arguments): Response
                        {
                            $bar = $arguments->get('bar');
                            $baz = $arguments->get('baz');

                            return new Response\Response(
                                $code = StatusCode::of('OK'),
                                $code->associatedReasonPhrase(),
                                $request->protocolVersion(),
                                null,
                                Stream::ofContent("$bar $baz from {$route->name()->toString()}")
                            );
                        }
                    }
                )
        );
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->any())
            ->method('url')
            ->willReturn(Url::of('http://localhost:8000/foo/hello/world'));
        $request
            ->expects($this->any())
            ->method('protocolVersion')
            ->willReturn(new ProtocolVersion(2, 0));
        $matcher
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(Route::of(new Name('foo'), Str::of('GET /foo/{bar}/{baz}')));

        $response = $route($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->statusCode()->value());
        $this->assertSame('hello world from foo', $response->body()->toString());
    }
}
