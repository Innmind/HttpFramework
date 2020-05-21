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
<?php
# index.php

use function Innmind\HttpFramework\bootstrap;
use Innmind\HttpFramework\{
    Controller,
    Application,
    Main,
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;

new class extends Main {
    protected function configure(Application $app): Application
    {
        return $app
            ->configAt(Path::of('/folder/containing/dotenv_file/'))
            ->handler(static function($os, $env) {
                $framework = bootstrap();

                return $framework['enforce_https'](
                    $framework['authenticate']($authenticator, $condition)(
                        $framework['router'](
                            /* instance of Innmind\Router\RequestMatcher */,
                            Map::of('string', Controller::class),
                        ),
                    ),
                );
            });
    }
}
```
