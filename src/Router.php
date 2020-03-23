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

final class Router implements RequestHandler
{
    private RequestMatcher $match;
    private Map $controllers;

    public function __construct(
        RequestMatcher $match,
        Map $controllers
    ) {
        if (
            (string) $controllers->keyType() !== 'string' ||
            (string) $controllers->valueType() !== Controller::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type Map<string, %s>',
                Controller::class
            ));
        }

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
                $request->protocolVersion()
            );
        }

        if (!$this->controllers->contains($route->name()->toString())) {
            return new Response\Response(
                $code = StatusCode::of('NOT_IMPLEMENTED'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion()
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
            )
        );
    }
}
