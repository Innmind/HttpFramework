<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpFramework\{
    Authenticate\Condition,
    Authenticate\Fallback,
    Authenticate\Unauthorized,
    Authenticate\MalformedAuthorizationHeader,
    Exception\NoAuthenticationProvided,
    Exception\MalformedAuthorizationHeader as MalformedAuthorizationHeaderException,
};
use Innmind\Router\RequestMatcher;
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Immutable\{
    MapInterface,
    Map,
};

function bootstrap(): array
{
    return [
        'router' => static function(RequestMatcher $requestMatcher, MapInterface $controllers): RequestHandler {
            return new Router($requestMatcher, $controllers);
        },
        'enforce_https' => static function(RequestHandler $handler): RequestHandler {
            return new EnforceHttps($handler);
        },
        'authenticate' => static function(Authenticator $authenticator, Condition $condition, MapInterface $fallbacks = null): callable {
            $fallbacks = (new Map('string', Fallback::class))
                ->put(NoAuthenticationProvided::class, new Unauthorized)
                ->put(MalformedAuthorizationHeaderException::class, new MalformedAuthorizationHeader)
                ->merge($fallbacks ?? new Map('string', Fallback::class));

            return static function(RequestHandler $handler) use ($authenticator, $condition, $fallbacks): RequestHandler {
                return new Authenticate($handler, $authenticator, $condition, $fallbacks);
            };
        },
    ];
}
