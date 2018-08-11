# Http framework

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/HttpFramework/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/HttpFramework/build-status/develop) |

Library to formlise http request handling and provide generic tools such as routing.

## Installation

```sh
composer require innmind/http-framework
```

## Usage

```yaml
# container.yml
dependencies:
    framework @innmind/http-framework/container.yml:
        routes: $appRoutes
        controllers: $appControllers
        authenticator: $authentication.authenticator # optional if you don't need authentication
        authenticationFallbacks: $appAuthenticationFallbacks # optional
        routePatternToAuthenticate: 'regex to match request urls to authenticate' # optional if you don't use authentication

    authentication @innmind/http-authentication/container.yml: []

expose:
    requestHandler: $requestHandler

services:
    requestHandler stack:
        - $framework.enforceHttps
        - $framework.authenticate
        - $framework.router

    appRoutes set<Innmind\Url\PathInterface>:
        - # see the innmind/router library to see the definition of routes

    appControllers map<string, Innmind\HttpFramework\Controller>:
        - <route.name, $instanceToHandleTheRoute>

    appAuthenticationFallbacks map<string, Innmind\HttpFramework\Authenticate\Fallback>:
        - <exception\class\fqcn, $instanceProducingResponseForTheGivenException>
```

```php
use Innmind\Compose\ContainerBuilder\ContainerBuilder;
use Innmind\Url\Path;
use Innmind\Immutable\Map;

$container = (new ContainerBuilder)(
    new Path('container.yml'),
    new Map('string', 'mixed')
);
$handle = $container->get('requestHandler');

$response = $handle(/* instance of Innmind\Http\Message\ServerRequest */);
```

If you want to know how to build a request and send the response take a look at [`innmind/http-server`](https://github.com/Innmind/HttpServer).
