<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\HttpServer\Main as Base;
use Innmind\Http\Message\{
    Environment,
    ServerRequest,
    Response,
};
use Innmind\OperatingSystem\OperatingSystem;

abstract class Main extends Base
{
    private Application $app;

    protected function preload(OperatingSystem $os, Environment $env): void
    {
        $this->app = $this->configure(Application::of($os, $env));
    }

    protected function main(ServerRequest $request): Response
    {
        return $this->app->handle($request);
    }

    abstract protected function configure(Application $app): Application;
}
