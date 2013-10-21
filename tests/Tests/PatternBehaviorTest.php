<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Tests;

use \Riimu\Kit\UrlParser\UrlParser;

class PatternBehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function testBacktrackLimits()
    {
        $parser = new UrlParser();
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://www.example.com:80/path/part?query=part#fragmentPart'));
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart'));
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://foo:bar@www.example.com/path/part?query=part#fragmentPart'));
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://www.example.com/path/part?query=part#fragmentPart'));
    }
}
