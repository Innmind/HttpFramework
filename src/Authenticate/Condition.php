<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Authenticate;

use Innmind\Http\Message\ServerRequest;
use Innmind\Immutable\Str;

final class Condition
{
    private string $regex;

    public function __construct(string $regex)
    {
        $this->regex = $regex;
    }

    public function __invoke(ServerRequest $request): bool
    {
        $url = $request
            ->url()
            ->withoutScheme()
            ->withoutAuthority()
            ->toString();

        return Str::of($url)->matches($this->regex);
    }
}
