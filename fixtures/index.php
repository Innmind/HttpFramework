<?php

require __DIR__.'/../vendor/autoload.php';

use Innmind\HttpFramework\{
    Main,
    Application,
    RequestHandler,
};
use Innmind\Http\Message\{
    ServerRequest,
    Response,
};
use Innmind\Url\Path;

new class extends Main
{
    protected function configure(Application $app): Application
    {
        return $app
            ->configAt(Path::of(__DIR__.'/'))
            ->disableSilentCartographer()
            ->handler(fn() => new class implements RequestHandler {
                public function __invoke(ServerRequest $request): Response
                {
                    throw new \RuntimeException;
                }
            });
    }
};
