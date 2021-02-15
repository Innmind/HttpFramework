<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework;

use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\StatusCode,
    Headers,
    Header\Location,
};
use Innmind\Url\Scheme;

final class EnforceHttps implements RequestHandler
{
    private RequestHandler $handle;

    public function __construct(RequestHandler $handle)
    {
        $this->handle = $handle;
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($request->url()->scheme()->toString() !== 'https') {
            /** @psalm-suppress InvalidArgument */
            return new Response\Response(
                $code = StatusCode::of('PERMANENTLY_REDIRECT'),
                $code->associatedReasonPhrase(),
                $request->protocolVersion(),
                Headers::of(
                    Location::of(
                        $request
                            ->url()
                            ->withScheme(Scheme::of('https')),
                    ),
                ),
            );
        }

        return ($this->handle)($request);
    }
}
