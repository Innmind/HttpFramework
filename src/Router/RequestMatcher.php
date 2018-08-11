<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Router;

use Innmind\Router\{
    RequestMatcher as RequestMatcherInterface,
    Route,
};
use Innmind\Http\Message\ServerRequest;

/**
 * Used as an indirection in the container to reference the real router matcher
 */
final class RequestMatcher implements RequestMatcherInterface
{
    private $match;

    public function __construct(RequestMatcherInterface $match)
    {
        $this->match = $match;
    }

    public function __invoke(ServerRequest $request): Route
    {
        return ($this->match)($request);
    }
}
