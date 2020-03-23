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
};
use Innmind\Http\Message\Environment as RequestEnvironment;
use Innmind\Filesystem\Adapter;
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
    Pair,
};
use function Innmind\Rest\Server\bootstrap as rest;
use Symfony\Component\Dotenv\Dotenv;

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
        'bridge' => [
            'rest_server' => static function(MapInterface $gateways, Directory $directory, Route $capabilities, Prefix $prefix = null): array {
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
                    'controllers' => $controllers->put(
                        (string) $capabilities->name(),
                        new Bridge\RestServer\CapabilitiesController($rest['controller']['capabilities'])
                    ),
                ];
            },
        ],
    ];
}

/**
 * @return MapInterface<string, scalar>
 */
function env(RequestEnvironment $env, Adapter $config): MapInterface
{
    $env = \iterator_to_array($env);
    $env = Map::of('string', 'scalar', \array_keys($env), \array_values($env));

    if ($config->has('.env')) {
        $dot = (new Dotenv)->parse((string) $config->get('.env')->content());

        foreach ($dot as $key => $value) {
            $env = $env->put($key, $value);
        }
    }

    return $env->map(static function(string $name, $value): Pair {
        return new Pair(
            (string) Str::of($name)->toLower()->camelize()->lcfirst(),
            $value
        );
    });
}
