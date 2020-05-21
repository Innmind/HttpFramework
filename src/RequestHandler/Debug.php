<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\RequestHandler;

use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode,
};
use Innmind\Stream\Readable\Stream;
use Whoops\{
    Run,
    Handler\PrettyPageHandler,
};

final class Debug implements RequestHandler
{
    private RequestHandler $handle;

    public function __construct(RequestHandler $handle)
    {
        $this->handle = $handle;
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            return ($this->handle)($request);
        } catch (\Throwable $e) {
            $whoops = new Run;
            $whoops->pushHandler(new PrettyPageHandler);

            return new Response\Response(
                $code = StatusCode::of('INTERNAL_SERVER_ERROR'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion(),
                null,
                Stream::ofContent($whoops->handleException($e)),
            );
        }
    }
}
