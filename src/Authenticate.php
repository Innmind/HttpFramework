<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpFramework\Authenticate\{
    Condition,
    Fallback,
};
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class Authenticate implements RequestHandler
{
    private RequestHandler $handle;
    private Authenticator $authenticate;
    private Condition $mustAuthenticate;
    /** @var Map<string, Fallback> */
    private Map $fallbacks;

    /**
     * @param Map<string, Fallback> $fallbacks
     */
    public function __construct(
        RequestHandler $handle,
        Authenticator $authenticate,
        Condition $condition,
        Map $fallbacks
    ) {
        assertMap('string', Fallback::class, $fallbacks, 3);

        $this->handle = $handle;
        $this->authenticate = $authenticate;
        $this->mustAuthenticate = $condition;
        $this->fallbacks = $fallbacks;
    }

    public function __invoke(ServerRequest $request): Response
    {
        // we don't do anything with the returned identity as the authenticator
        // should keep in memory for later usage in the current request
        // here the only goal is to make sure a user is authenticated before
        // further handling of the request
        if (($this->mustAuthenticate)($request)) {
            try {
                ($this->authenticate)($request);
            } catch (\Exception $e) {
                $class = get_class($e);

                if (!$this->fallbacks->contains($class)) {
                    throw $e;
                }

                return $this->fallbacks->get($class)($request, $e);
            }
        }

        return ($this->handle)($request);
    }
}
