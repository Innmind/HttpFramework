<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode\StatusCode,
};

final class EnforceHttps implements RequestHandler
{
    private $handle;

    public function __construct(RequestHandler $handle)
    {
        $this->handle = $handle;
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ((string) $request->url()->scheme() !== 'https') {
            return new Response\Response(
                $code = StatusCode::of('PERMANENTLY_REDIRECT'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion()
            );
        }

        return ($this->handle)($request);
    }
}