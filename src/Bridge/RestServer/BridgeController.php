<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Bridge\RestServer;

use Innmind\HttpFramework\Controller;
use Innmind\Rest\Server\{
    Controller as RestController,
    Definition\HttpResource,
    Identity\Identity,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Router\Route;
use Innmind\Immutable\MapInterface;
use function Innmind\Immutable\assertMap;

final class BridgeController implements Controller
{
    private $handle;
    private $definitions;

    public function __construct(RestController $handle, MapInterface $definitions)
    {
        assertMap(Route::class, HttpResource::class, $definitions, 2);

        $this->handle = $handle;
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequest $request,
        Route $route,
        MapInterface $arguments
    ): Response {
        if ($arguments->contains('identity')) {
            $identity = new Identity(
                $arguments->get('identity')
            );
        }

        return ($this->handle)(
            $request,
            $this->definitions->get($route),
            $identity ?? null
        );
    }
}
