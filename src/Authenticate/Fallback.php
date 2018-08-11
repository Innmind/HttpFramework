<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\Message\{
    ServerRequest,
    Response,
};

interface Fallback
{
    public function __invoke(ServerRequest $request, \Exception $e): Response;
}
