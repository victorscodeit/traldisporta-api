<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/libs/Slim/Slim.php';
require_once dirname(__DIR__, 2) . '/include/functions.php';
require_once dirname(__DIR__, 2) . '/modules/morosos/morosos.php';

/**
 * WAMP PHP 8.3 + Slim converts utf8_encode deprecation into ErrorException.
 * Docker hides E_DEPRECATED, so this suite uses E_ALL like production Apache.
 */
class Utf8EncodeCompatTest extends TestCase
{
    private $previousReporting;
    private $previousHandler;

    protected function setUp(): void
    {
        $this->previousReporting = error_reporting(E_ALL);
        $this->previousHandler = set_error_handler(array('Slim\Slim', 'handleErrors'));
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        error_reporting($this->previousReporting);
    }

    public function testUtf8EncodeThrowsUnderSlimWithEAll()
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessageMatches('/utf8_encode\(\) is deprecated/');
        utf8_encode("\xE9");
    }

    public function testLatin1ToUtf8MatchesLegacyUtf8EncodeBytes()
    {
        $latin1 = 'Jos' . "\xE9";
        $expected = 'Jos' . "\xC3\xA9";
        $this->assertSame($expected, latin1_to_utf8($latin1));
        $this->assertSame('c3a9', bin2hex(latin1_to_utf8("\xE9")));
        $this->assertSame('', latin1_to_utf8(''));
        $this->assertNull(latin1_to_utf8(null));
        json_encode(array('n' => latin1_to_utf8($latin1)));
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testLatin1ToUtf8DoesNotThrowUnderSlim()
    {
        $out = latin1_to_utf8('Jos' . "\xE9");
        $this->assertSame("Jos\xC3\xA9", $out);
    }

    public function testMorososUtf8izeStringBranchDoesNotThrow()
    {
        $m = new morosos();
        $this->assertSame("Jos\xC3\xA9", $m->utf8ize('Jos' . "\xE9"));
    }
}
