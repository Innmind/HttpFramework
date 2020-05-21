<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Http\{
    Message\Environment,
    Message\ServerRequest,
    Message\Response,
};
use Innmind\Url\Path;
use Innmind\Filesystem\Name;
use Innmind\Immutable\Map;
use Symfony\Component\Dotenv\Dotenv;

final class Application
{
    private OperatingSystem $os;
    private Environment $env;
    /** @var \Closure(OperatingSystem, Environment): RequestHandler */
    private \Closure $handler;
    /** @var \Closure(OperatingSystem, Environment): Environment */
    private \Closure $loadDotEnv;

    /**
     * @param callable(OperatingSystem, Environment): RequestHandler $handler
     * @param callable(OperatingSystem, Environment): Environment $loadDotEnv
     */
    private function __construct(
        OperatingSystem $os,
        Environment $env,
        callable $handler,
        callable $loadDotEnv
    ) {
        $this->os = $os;
        $this->env = $env;
        $this->handler = \Closure::fromCallable($handler);
        $this->loadDotEnv = \Closure::fromCallable($loadDotEnv);
    }

    public static function of(OperatingSystem $os, Environment $env): self
    {
        return new self(
            $os,
            $env,
            static fn(): RequestHandler => new RequestHandler\HelloWorld,
            static fn(OperatingSystem $os, Environment $env): Environment => $env,
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
        );
    }

    public function handle(ServerRequest $request): Response
    {
        $env = ($this->loadDotEnv)($this->os, $this->env);
        $handle = ($this->handler)($this->os, $env);

        return $handle($request);
    }
}
