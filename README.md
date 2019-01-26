# Http framework

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/build-status/develop) |

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
