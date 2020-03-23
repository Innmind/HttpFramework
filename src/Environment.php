<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Http\Message\Environment as RequestEnvironment;
use Innmind\Immutable\{
    Map,
    Pair,
    Str,
};
use Symfony\Component\Dotenv\Dotenv;

/**
 * Used to easily combine env variables from the server environment and the .env
 * file if any exists
 */
final class Environment
{
    /**
     * @deprecated
     *
     * @return Map<string, mixed>
     */
    public static function of(
        string $envFile,
        RequestEnvironment $environment
    ): Map {
        @trigger_error('Use the `env` function instead', E_USER_DEPRECATED);

        $arguments = Map::of('string', 'mixed');

        if (\file_exists($envFile)) {
            $env = (new Dotenv)->parse(\file_get_contents($envFile));

            foreach ($env as $key => $value) {
                $arguments = $arguments->put($key, $value);
            }
        }


        return $environment->reduce(
            $arguments,
            static function(Map $env, string $key, string $value): Map {
                return ($env)($key, $value);
            },
        );
    }

    /**
     * Same as self::of() but will camelize all keys
     *
     * @return Map<string, mixed>
     */
    public static function camelize(
        string $envFile,
        RequestEnvironment $environment
    ): Map {
        return self::of($envFile, $environment)->map(static function(string $name, $value): Pair {
            return new Pair(
                Str::of($name)->toLower()->camelize()->toString(),
                $value
            );
        });
    }
}
