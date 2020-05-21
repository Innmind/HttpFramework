<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Http\{
    Message\Environment,
    Message\ServerRequest,
    Message\Response,
};

final class Application
{
    private OperatingSystem $os;
    private Environment $env;
    /** @var \Closure(OperatingSystem, Environment): RequestHandler */
    private \Closure $handler;

    /**
     * @param callable(OperatingSystem, Environment): RequestHandler $handler
     */
    private function __construct(
        OperatingSystem $os,
        Environment $env,
        callable $handler
    ) {
        $this->os = $os;
        $this->env = $env;
        $this->handler = \Closure::fromCallable($handler);
    }

    public static function of(OperatingSystem $os, Environment $env): self
    {
        return new self(
            $os,
            $env,
            static fn(): RequestHandler => new RequestHandler\HelloWorld,
        );
    }

    public function handle(ServerRequest $request): Response
    {
        $handle = ($this->handler)($this->os, $this->env);

        return $handle($request);
    }
}
