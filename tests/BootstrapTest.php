<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use function Innmind\HttpFramework\bootstrap;
use Innmind\HttpFramework\{
    Router,
    Controller,
    EnforceHttps,
    RequestHandler,
    Authenticate,
    Authenticate\Condition,
};
use Innmind\Router\RequestMatcher;
use Innmind\HttpAuthentication\Authenticator;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $handlers = bootstrap();

        $this->assertInternalType('callable', $handlers['router']);
        $this->assertInstanceOf(
            Router::class,
            $handlers['router'](
                $this->createMock(RequestMatcher::class),
                new Map('string', Controller::class)
            )
        );
        $this->assertInternalType('callable', $handlers['enforce_https']);
        $this->assertInstanceOf(
            EnforceHttps::class,
            $handlers['enforce_https']($this->createMock(RequestHandler::class))
        );
        $this->assertInternalType('callable', $handlers['authenticate']);
        $authenticate = $handlers['authenticate'](
            $this->createMock(Authenticator::class),
            new Condition('~^/~')
        );
        $this->assertInternalType('callable', $authenticate);
        $this->assertInstanceOf(
            AUthenticate::class,
            $authenticate($this->createMock(RequestHandler::class))
        );
    }
}
