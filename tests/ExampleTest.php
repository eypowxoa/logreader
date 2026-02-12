<?php

declare(strict_types=1);

namespace LogReaderTests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        $this->assertSame('ok', 'ok');
    }
}
