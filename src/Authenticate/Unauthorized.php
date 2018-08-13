<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\{
    Message\ServerRequest,
    Message\Response,
    Message\StatusCode\StatusCode,
    Headers\Headers,
    Header\WWWAuthenticate,
    Header\WWWAuthenticateValue,
};
use Innmind\Url\Authority\NullUserInformation;

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
                        (string) $request
                            ->url()
                            ->authority()
                            ->withUserInformation(new NullUserInformation)
                    )
                )
            )
        );
    }
}
