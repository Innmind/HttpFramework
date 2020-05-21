<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\RequestHandler;

use Innmind\HttpFramework\RequestHandler;
use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\StatusCode,
};
use Innmind\Stream\Readable\Stream;

final class HelloWorld implements RequestHandler
{
    public function __invoke(ServerRequest $request): Response
    {
        return new Response\Response(
            $code = StatusCode::of('OK'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion(),
            null,
            Stream::ofContent('Hello World!'),
        );
    }
}
