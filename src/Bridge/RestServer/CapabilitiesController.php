<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Controller;
use Innmind\Rest\Server\Controller\Capabilities as RestCapabilities;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Router\Route;
use Innmind\Immutable\Map;

final class CapabilitiesController implements Controller
{
    private RestCapabilities $handle;

    public function __construct(RestCapabilities $handle)
    {
        $this->handle = $handle;
    }

    public function __invoke(
        ServerRequest $request,
        Route $route,
        Map $arguments
    ): Response {
        return ($this->handle)($request);
    }
}
