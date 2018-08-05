<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpFramework\Exception\UnexpectedValueException;
use Innmind\Router\{
    RequestMatcher,
    Exception\NoMatchingRouteFound,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode\StatusCode,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Sequence,
};

final class Router implements RequestHandler
{
    private $match;
    private $handlers;

    public function __construct(
        RequestMatcher $match,
        MapInterface $handlers
    ) {
        if (
            (string) $handlers->keyType() !== 'string' ||
            (string) $handlers->valueType() !== 'callable'
        ) {
            throw new \TypeError('Argument 2 must be of type MapInterface<string, callable>');
        }

        $this->match = $match;
        $this->handlers = $handlers;
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

        if (!$this->handlers->contains((string) $route->name())) {
            return new Response\Response(
                $code = StatusCode::of('NOT_IMPLEMENTED'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion()
            );
        }

        $handle = $this->handlers->get((string) $route->name());
        $variables = $route
            ->template()
            ->extract($request->url())
            ->reduce(
                new Map('string', 'mixed'),
                static function(MapInterface $variables, string $variable, string $value): MapInterface {
                    return $variables->put(
                        $variable,
                        $value
                    );
                }
            )
            ->put('route', $route->name())
            ->put('request', $request);

        $arguments = $this->computeArguments($handle, $variables);

        $response = $handle(...$arguments);

        if (!$response instanceof Response) {
            throw new UnexpectedValueException;
        }

        return $response;
    }

    private function computeArguments(callable $handler, MapInterface $variables): Sequence
    {
        $refl = new \ReflectionFunction(\Closure::fromCallable($handler));
        $arguments = new Sequence;

        foreach ($refl->getParameters() as $parameter) {
            $arguments = $arguments->add($variables[$parameter->name] ?? null);
        }

        return $arguments;
    }
}
