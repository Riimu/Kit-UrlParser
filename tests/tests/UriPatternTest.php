<?php

namespace Riimu\Kit\UrlParser;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriPatternTest extends \PHPUnit_Framework_TestCase
{
    public function testNonAsciiCharacters()
    {
        $pattern = new UriPattern();
        $this->assertFalse($pattern->matchUri("http://www.example.com/\xFF"));
        $this->assertFalse($pattern->matchUri("http\xFF://www.example.com"));

        $pattern->allowNonAscii();
        $this->assertTrue($pattern->matchUri("http://www.example.com/\xFF"));
        $this->assertFalse($pattern->matchUri("http\xFF://www.example.com"));
    }
}
