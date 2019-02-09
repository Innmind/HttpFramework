<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Http\Message\Environment as RequestEnvironment;
use Innmind\Immutable\{
    MapInterface,
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
     * @return MapInterface<string, mixed>
     */
    public static function of(
        string $envFile,
        RequestEnvironment $environment
    ): MapInterface {
        @trigger_error('Use the `env` function instead', E_USER_DEPRECATED);

        $arguments = new Map('string', 'mixed');

        if (\file_exists($envFile)) {
            $env = (new Dotenv)->parse(\file_get_contents($envFile));

            foreach ($env as $key => $value) {
                $arguments = $arguments->put($key, $value);
            }
        }

        foreach ($environment as $key => $value) {
            $arguments = $arguments->put($key, $value);
        };

        return $arguments;
    }

    /**
     * Same as self::of() but will camelize all keys
     *
     * @return MapInterface<string, mixed>
     */
    public static function camelize(
        string $envFile,
        RequestEnvironment $environment
    ): MapInterface {
        return self::of($envFile, $environment)->map(static function(string $name, $value): Pair {
            return new Pair(
                (string) Str::of($name)->toLower()->camelize()->lcfirst(),
                $value
            );
        });
    }
}
