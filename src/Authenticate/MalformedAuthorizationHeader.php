<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode\StatusCode,
};
use Innmind\Filesystem\Stream\StringStream;

final class MalformedAuthorizationHeader implements Fallback
{
    public function __invoke(ServerRequest $request, \Exception $e): Response
    {
        return new Response\Response(
            $code = StatusCode::of('BAD_REQUEST'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion(),
            null,
            new StringStream('Malformed authorization header')
        );
    }
}
