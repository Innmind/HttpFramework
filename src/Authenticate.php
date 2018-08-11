<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpFramework\Authenticate\Fallback;
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Immutable\MapInterface;

final class Authenticate implements RequestHandler
{
    private $handle;
    private $authenticate;
    private $fallbacks;

    public function __construct(
        RequestHandler $handle,
        Authenticator $authenticate,
        MapInterface $fallbacks
    ) {
        if (
            (string) $fallbacks->keyType() !== 'string' ||
            (string) $fallbacks->valueType() !== Fallback::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 3 must be of type MapInterface<string, %s>',
                Fallback::class
            ));
        }

        $this->handle = $handle;
        $this->authenticate = $authenticate;
        $this->fallbacks = $fallbacks;
    }

    public function __invoke(ServerRequest $request): Response
    {
        // we don't do anything with the returned identity as the authenticator
        // should keep in memory for later usage in the current request
        // here the only goal is to make sure a user is authenticated before
        // further handling of the request
        try {
            ($this->authenticate)($request);
        } catch (\Exception $e) {
            $class = get_class($e);

            if (!$this->fallbacks->contains($class)) {
                throw $e;
            }

            return $this->fallbacks->get($class)($request, $e);
        }

        return ($this->handle)($request);
    }
}
