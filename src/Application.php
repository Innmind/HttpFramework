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
use Innmind\Debug\{
    Profiler,
    Section,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\SilentCartographer\bootstrap as cartographer;
use function Innmind\Debug\bootstrap as debug;
use function Innmind\Stack\stack;
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
    /** @var list<class-string> */
    private array $disabledSections;

    /**
     * @psalm-suppress UndefinedDocblockClass
     * @param callable(OperatingSystem, Environment): RequestHandler $handler
     * @param callable(OperatingSystem, Environment): Environment $loadDotEnv
     * @param callable(OperatingSystem, Environment): OperatingSystem $enableSilentCartographer
     * @param callable(OperatingSystem): OperatingSystem $useResilientOperatingSystem
     * @param list<class-string> $disabledSections
     */
    private function __construct(
        OperatingSystem $os,
        Environment $env,
        callable $handler,
        callable $loadDotEnv,
        callable $enableSilentCartographer,
        callable $useResilientOperatingSystem,
        array $disabledSections
    ) {
        $this->os = $os;
        $this->env = $env;
        $this->handler = \Closure::fromCallable($handler);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
        $this->enableSilentCartographer = \Closure::fromCallable($enableSilentCartographer);
        $this->useResilientOperatingSystem = \Closure::fromCallable($useResilientOperatingSystem);
        $this->disabledSections = $disabledSections;
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
            [],
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
            $this->disabledSections,
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
            $this->disabledSections,
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
            [],
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
            $this->disabledSections,
        );
    }

    /**
     * @psalm-suppress UndefinedDocblockClass
     * @param list<class-string<Section>> $sections
     */
    public function disableProfilerSection(string ...$sections): self
    {
        return new self(
            $this->os,
            $this->env,
            $this->handler,
            $this->loadDotEnv,
            $this->enableSilentCartographer,
            $this->useResilientOperatingSystem,
            \array_merge(
                $this->disabledSections,
                $sections,
            ),
        );
    }

    public function build(): RequestHandler
    {
        $os = ($this->enableSilentCartographer)($this->os, $this->env);
        // done after the silent cartographer so that retries show up in the
        // cartographer panel
        $os = ($this->useResilientOperatingSystem)($os);
        $env = ($this->loadDotEnv)($os, $this->env);
        $middlewares = [static fn(RequestHandler $_): RequestHandler => $_];

        if ($env->contains('DEBUG') && \class_exists(Run::class)) {
            $middlewares[] = static fn(RequestHandler $_): RequestHandler => new RequestHandler\Debug($_);
        }

        if ($env->contains('PROFILER') && \class_exists(Profiler::class)) {
            /**
             * Forced to add this docblock as we can't require innmind/debug in
             * this project as debug depends on this (circular dependency)
             *
             * @psalm-suppress UndefinedFunction
             * @var array{os: callable(): OperatingSystem, http: callable(RequestHandler): RequestHandler} $debug
             */
            $debug = debug(
                $os,
                Url::of($env->get('PROFILER')),
                $env->reduce(
                    Map::of('string', 'string'),
                    static fn(Map $variables, string $key, string $value): Map => ($variables)($key, $value),
                ),
                null,
                Set::strings(...$this->disabledSections),
            );
            $os = $debug['os']();
            $middlewares[] = $debug['http'];
        }

        return stack(...$middlewares)(
            ($this->handler)($os, $env),
        );
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->build()($request);
    }
}
