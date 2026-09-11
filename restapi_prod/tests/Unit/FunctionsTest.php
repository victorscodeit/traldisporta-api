<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/include/functions.php';

class FunctionsTest extends TestCase
{
    public function testParseDateAppendsMidnightUtc()
    {
        $this->assertSame('2024-06-15T00:00:00.000Z', parseDate('2024-06-15'));
    }

    public function testParseDateKeepsFalse()
    {
        $this->assertFalse(parseDate(false));
    }
}
