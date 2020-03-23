<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Router\Route;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\Map;

interface Controller
{
    /**
     * @param Map<string, string> $arguments
     */
    public function __invoke(
        ServerRequest $request,
        Route $route,
        Map $arguments
    ): Response;
}
