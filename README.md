# Http framework

[![Build Status](https://github.com/Innmind/HttpFramework/workflows/CI/badge.svg)](https://github.com/Innmind/HttpFramework/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/HttpFramework/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/HttpFramework)
[![Type Coverage](https://shepherd.dev/github/Innmind/HttpFramework/coverage.svg)](https://shepherd.dev/github/Innmind/HttpFramework)

Library to formalize http request handling and provide generic tools such as routing.

## Installation

```sh
composer require innmind/http-framework
```

## Usage

```php
use function Innmind\HttpFramework\bootstrap;
use Innmind\HttpFramework\Controller;
use Innmind\Immutable\Map;

$framework = bootstrap();
$handle = $framework['enforce_https'](
    $framework['authenticate']($authenticator, $condition)(
        $framework['router'](
            /* instance of Innmind\Router\RequestMatcher */,
            Map::of('string', Controller::class)
        )
    )
);

$response = $handle(/* instance of Innmind\Http\Message\ServerRequest */);
```

If you want to know how to build a request and send the response take a look at [`innmind/http-server`](https://github.com/Innmind/HttpServer).
