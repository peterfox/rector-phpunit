<?php

namespace Rector\PHPUnit\Tests\CodeQuality\Rector\MethodCall\NarrowSingleWillReturnCallbackRector\Fixture;

use PHPUnit\Framework\TestCase;

final class SkipMultiValue extends TestCase
{
    public function test()
    {
        $matcher = $this->exactly(3);

        $someServiceMock = $this->createMock(SomeMockedClass::class);
        $someServiceMock->expects($matcher)
            ->method('prepare')
            ->willReturnCallback(function (...$parameters) use ($matcher) {
                match ($matcher->getInvocationCount()) {
                    1 => $this->assertSame([1], $parameters),
                    2 => $this->assertSame([1], $parameters),
                };
            });
    }
}