<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpFramework\Authenticate\{
    Condition,
    Fallback,
    Unauthorized,
    MalformedAuthorizationHeader,
};
use Innmind\Router\{
    RequestMatcher,
    Route,
};
use Innmind\HttpAuthentication\{
    Authenticator,
    Exception\NoAuthenticationProvided,
    Exception\MalformedAuthorizationHeader as MalformedAuthorizationHeaderException,
};
use Innmind\Rest\Server\{
    Definition\Directory,
    Routing\Prefix,
    Gateway,
};
use Innmind\Http\Message\Environment as RequestEnvironment;
use Innmind\Filesystem\{
    Adapter,
    Name,
};
use Innmind\Immutable\{
    Set,
    Map,
    Str,
    Pair,
};
use function Innmind\Rest\Server\bootstrap as rest;
use Symfony\Component\Dotenv\Dotenv;

/**
 * @return array{router: callable(RequestMatcher, Map<string, Controller>): RequestHandler, enforce_https: callable(RequestHandler): RequestHandler, authenticate: callable(Authenticator, Condition, ?Map<string, Fallback>): (callable(RequestHandler): RequestHandler), bridge: array{rest_server: callable(Map<string, Gateway>, Directory, Route, ?Prefix): array{routes: Set<Route>, controllers: Map<string, Controller>}}}
 */
function bootstrap(): array
{
    return [
        'router' => static function(RequestMatcher $requestMatcher, Map $controllers): RequestHandler {
            /** @var Map<string, Controller> $controllers */

            return new Router($requestMatcher, $controllers);
        },
        'enforce_https' => static function(RequestHandler $handler): RequestHandler {
            return new EnforceHttps($handler);
        },
        'authenticate' => static function(Authenticator $authenticator, Condition $condition, Map $fallbacks = null): callable {
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             * @psalm-suppress InvalidScalarArgument
             * @psalm-suppress InvalidArgument
             * @var Map<string, Fallback>
             */
            $fallbacks = Map::of('string', Fallback::class)
                (NoAuthenticationProvided::class, new Unauthorized)
                (MalformedAuthorizationHeaderException::class, new MalformedAuthorizationHeader)
                    ->merge($fallbacks ?? Map::of('string', Fallback::class));

            return static function(RequestHandler $handler) use ($authenticator, $condition, $fallbacks): RequestHandler {
                return new Authenticate($handler, $authenticator, $condition, $fallbacks);
            };
        },
        'bridge' => [
            'rest_server' => static function(Map $gateways, Directory $directory, Route $capabilities, Prefix $prefix = null): array {
                /** @var Map<string, Gateway> $gateways */
                $rest = rest($gateways, $directory, null, null, $prefix);

                $routesToDefinitions = Bridge\RestServer\Routes::from($rest['routes']);
                $controllers = Bridge\RestServer\Controllers::from(
                    $rest['routes'],
                    $rest['controller']['create'],
                    $rest['controller']['get'],
                    $rest['controller']['index'],
                    $rest['controller']['options'],
                    $rest['controller']['remove'],
                    $rest['controller']['update'],
                    $rest['controller']['link'],
                    $rest['controller']['unlink'],
                    $routesToDefinitions
                );

                return [
                    'routes' => $routesToDefinitions->keys()->add($capabilities),
                    'controllers' => ($controllers)(
                        $capabilities->name()->toString(),
                        new Bridge\RestServer\CapabilitiesController($rest['controller']['capabilities'])
                    ),
                ];
            },
        ],
    ];
}

/**
 * @return Map<string, scalar>
 */
function env(RequestEnvironment $env, Adapter $config): Map
{
    /** @var Map<string, scalar> */
    $env = $env->reduce(
        Map::of('string', 'scalar'),
        static function(Map $env, string $key, string $value): Map {
            return ($env)($key, $value);
        },
    );

    if ($config->contains(new Name('.env'))) {
        /** @var array<string, scalar> */
        $dot = (new Dotenv)->parse($config->get(new Name('.env'))->content()->toString());

        foreach ($dot as $key => $value) {
            $env = ($env)($key, $value);
        }
    }

    return $env->map(static function(string $name, $value): Pair {
        return new Pair(
            Str::of($name)->toLower()->camelize()->toString(),
            $value
        );
    });
}
