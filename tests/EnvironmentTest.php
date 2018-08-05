<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework;

use Innmind\HttpFramework\Environment;
use Innmind\Http\Message\Environment\Environment as RequestEnvironment;
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testOf()
    {
        $environment = Environment::of(
            'fixtures/.env',
            new RequestEnvironment(
                (new Map('string', 'scalar'))
                    ->put('FOO', 'foo')
                    ->put('BAZ', 'baz')
            )
        );

        $this->assertInstanceOf(MapInterface::class, $environment);
        $this->assertSame('string', (string) $environment->keyType());
        $this->assertSame('mixed', (string) $environment->valueType());
        $this->assertCount(3, $environment);
        $this->assertSame('foo', $environment->get('FOO'));
        $this->assertSame('baz', $environment->get('BAR'));
        $this->assertSame('baz', $environment->get('BAZ'));
    }

    public function testOfWhenNoEnvFile()
    {
        $environment = Environment::of(
            'fixtures/unknown/.env',
            new RequestEnvironment(
                (new Map('string', 'scalar'))
                    ->put('FOO', 'foo')
                    ->put('BAZ', 'baz')
            )
        );

        $this->assertInstanceOf(MapInterface::class, $environment);
        $this->assertSame('string', (string) $environment->keyType());
        $this->assertSame('mixed', (string) $environment->valueType());
        $this->assertCount(2, $environment);
        $this->assertSame('foo', $environment->get('FOO'));
        $this->assertSame('baz', $environment->get('BAZ'));
    }
    public function testCamelize()
    {
        $environment = Environment::camelize(
            'fixtures/.env',
            new RequestEnvironment(
                (new Map('string', 'scalar'))
                    ->put('FOO_BAR', 'foo')
            )
        );

        $this->assertInstanceOf(MapInterface::class, $environment);
        $this->assertSame('string', (string) $environment->keyType());
        $this->assertSame('mixed', (string) $environment->valueType());
        $this->assertCount(3, $environment);
        $this->assertSame('bar', $environment->get('foo'));
        $this->assertSame('baz', $environment->get('bar'));
        $this->assertSame('foo', $environment->get('fooBar'));
    }
}
