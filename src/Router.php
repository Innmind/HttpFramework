<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Router\{
    RequestMatcher,
    Exception\NoMatchingRouteFound,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class Router implements RequestHandler
{
    private RequestMatcher $match;
    /** @var Map<string, Controller> */
    private Map $controllers;

    /**
     * @param Map<string, Controller> $controllers
     */
    public function __construct(RequestMatcher $match, Map $controllers)
    {
        assertMap('string', Controller::class, $controllers, 2);

        $this->match = $match;
        $this->controllers = $controllers;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            $route = ($this->match)($request);
        } catch (NoMatchingRouteFound $e) {
            return new Response\Response(
                $code = StatusCode::of('NOT_FOUND'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion(),
            );
        }

        if (!$this->controllers->contains($route->name()->toString())) {
            return new Response\Response(
                $code = StatusCode::of('NOT_IMPLEMENTED'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion(),
            );
        }

        $handle = $this->controllers->get($route->name()->toString());

        return $handle(
            $request,
            $route,
            $route->template()->extract(
                $request
                    ->url()
                    ->withoutScheme()
                    ->withoutAuthority(),
            ),
        );
    }
}
