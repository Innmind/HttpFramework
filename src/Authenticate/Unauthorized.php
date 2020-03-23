<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\StatusCode,
    Headers,
    Header\WWWAuthenticate,
    Header\WWWAuthenticateValue,
};

final class Unauthorized implements Fallback
{
    public function __invoke(ServerRequest $request, \Exception $e): Response
    {
        return new Response\Response(
            $code = StatusCode::of('UNAUTHORIZED'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion(),
            Headers::of(
                new WWWAuthenticate(
                    new WWWAuthenticateValue(
                        'Basic',
                        $request
                            ->url()
                            ->authority()
                            ->withoutUserInformation()
                            ->toString(),
                    )
                )
            )
        );
    }
}
