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
use Innmind\Immutable\MapInterface;

final class CapabilitiesController implements Controller
{
    private $handle;

    public function __construct(RestCapabilities $handle)
    {
        $this->handle = $handle;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequest $request,
        Route $route,
        MapInterface $arguments
    ): Response {
        return ($this->handle)($request);
    }
}
