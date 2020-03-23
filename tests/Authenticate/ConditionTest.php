<?php
declare(strict_types = 1);

namespace Tests\Innmind\HttpFramework\Authenticate;

use Innmind\HttpFramework\Authenticate\Condition;
use Innmind\Http\Message\ServerRequest;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    public function testInvokation()
    {
        $condition = new Condition('~^/(foo|bar)$~');
        $request = $this->createMock(ServerRequest::class);
        $request
            ->expects($this->exactly(4))
            ->method('url')
            ->will($this->onConsecutiveCalls(
                Url::of('/foo'),
                Url::of('http://localhost/bar'),
                Url::of('/baz'),
                Url::of('/foobar')
            ));

        $this->assertTrue($condition($request));
        $this->assertTrue($condition($request));
        $this->assertFalse($condition($request));
        $this->assertFalse($condition($request));
    }
}
