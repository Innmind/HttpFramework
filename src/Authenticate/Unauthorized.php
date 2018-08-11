<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\Message\{
    ServerRequest,
    Response,
    StatusCode\StatusCode,
};

final class Unauthorized implements Fallback
{
    public function __invoke(ServerRequest $request, \Exception $e): Response
    {
        return new Response\Response(
            $code = StatusCode::of('UNAUTHORIZED'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion()
        );
    }
}
