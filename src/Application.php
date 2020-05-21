<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Http\{
    Message\Environment,
    Message\ServerRequest,
    Message\Response,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Filesystem\Name;
use Innmind\Immutable\Map;
use function Innmind\SilentCartographer\bootstrap as cartographer;
use Symfony\Component\Dotenv\Dotenv;
use Whoops\Run;

final class Application
{
    private OperatingSystem $os;
    private Environment $env;
    /** @var \Closure(OperatingSystem, Environment): RequestHandler */
    private \Closure $handler;
    /** @var \Closure(OperatingSystem, Environment): Environment */
    private \Closure $loadDotEnv;
    /** @var \Closure(OperatingSystem, Environment): OperatingSystem */
    private \Closure $enableSilentCartographer;
    /** @var \Closure(OperatingSystem): OperatingSystem */
    private \Closure $useResilientOperatingSystem;

    /**
     * @param callable(OperatingSystem, Environment): RequestHandler $handler
     * @param callable(OperatingSystem, Environment): Environment $loadDotEnv
     * @param callable(OperatingSystem, Environment): OperatingSystem $enableSilentCartographer
     * @param callable(OperatingSystem): OperatingSystem $useResilientOperatingSystem
     */
    private function __construct(
        OperatingSystem $os,
        Environment $env,
        callable $handler,
        callable $loadDotEnv,
        callable $enableSilentCartographer,
        callable $useResilientOperatingSystem
    ) {
        $this->os = $os;
        $this->env = $env;
        $this->handler = \Closure::fromCallable($handler);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
        $this->enableSilentCartographer = \Closure::fromCallable($enableSilentCartographer);
        $this->useResilientOperatingSystem = \Closure::fromCallable($useResilientOperatingSystem);
    }

    public static function of(OperatingSystem $os, Environment $env): self
    {
        return new self(
            $os,
            $env,
            static fn(): RequestHandler => new RequestHandler\HelloWorld,
            static fn(OperatingSystem $os, Environment $env): Environment => $env,
            static function(OperatingSystem $os, Environment $env): OperatingSystem {
                switch (true) {
                    case $env->contains('PWD'):
                        $location = $env->get('PWD');
                        break;

                    case $env->contains('SCRIPT_FILENAME'):
                        $location = $env->get('SCRIPT_FILENAME');
                        break;

                    default:
                        return $os;
                }

                return cartographer($os)['http_server'](
                    Url::of($location),
                );
            },
            static fn(OperatingSystem $os): OperatingSystem => $os,
        );
    }

    /**
     * @param callable(OperatingSystem, Environment): RequestHandler $handler
     */
    public function handler(callable $handler): self
    {
        return new self(
            $this->os,
            $this->env,
            $handler,
            $this->loadDotEnv,
            $this->enableSilentCartographer,
            $this->useResilientOperatingSystem,
        );
    }

    public function configAt(Path $path): self
    {
        return new self(
            $this->os,
            $this->env,
            $this->handler,
            static function(OperatingSystem $os, Environment $env) use ($path): Environment {
                if (!$os->filesystem()->contains($path)) {
                    return $env;
                }

                $config = $os->filesystem()->mount($path);

                if (!$config->contains(new Name('.env'))) {
                    return $env;
                }

                /** @var Map<string, string> */
                $variables = $env->reduce(
                    Map::of('string', 'string'),
                    static fn(Map $variables, string $key, string $value): Map => ($variables)($key, $value),
                );

                /** @var array<string, string> */
                $dot = (new Dotenv)->parse($config->get(new Name('.env'))->content()->toString());

                foreach ($dot as $key => $value) {
                    $variables = ($variables)($key, $value);
                }

                return new Environment($variables);
            },
            $this->enableSilentCartographer,
            $this->useResilientOperatingSystem,
        );
    }

    public function disableSilentCartographer(): self
    {
        return new self(
            $this->os,
            $this->env,
            $this->handler,
            $this->loadDotEnv,
            static fn(OperatingSystem $os): OperatingSystem => $os,
            $this->useResilientOperatingSystem,
        );
    }

    public function useResilientOperatingSystem(): self
    {
        return new self(
            $this->os,
            $this->env,
            $this->handler,
            $this->loadDotEnv,
            $this->enableSilentCartographer,
            static fn(OperatingSystem $os): OperatingSystem => new OperatingSystem\Resilient($os),
        );
    }

    public function handle(ServerRequest $request): Response
    {
        $os = ($this->enableSilentCartographer)($this->os, $this->env);
        // done after the silent cartographer so that retries show up in the
        // cartographer panel
        $os = ($this->useResilientOperatingSystem)($os);
        $env = ($this->loadDotEnv)($os, $this->env);
        $handle = ($this->handler)($os, $env);

        if ($env->contains('DEBUG') && \class_exists(Run::class)) {
            $handle = new RequestHandler\Debug($handle);
        }

        return $handle($request);
    }
}
