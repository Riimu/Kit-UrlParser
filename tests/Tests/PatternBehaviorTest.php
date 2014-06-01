<?php

namespace Riimu\Kit\UrlParser;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PatternBehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function testBacktrackLimits()
    {
        $parser = new UrlParser();
        $this->assertInstanceOf(
            'Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://www.example.com:80/path/part?query=part#fragmentPart')
        );
        $this->assertInstanceOf(
            'Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart')
        );
        $this->assertInstanceOf(
            'Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://foo:bar@www.example.com/path/part?query=part#fragmentPart')
        );
        $this->assertInstanceOf(
            'Riimu\Kit\UrlParser\UrlInfo',
            $parser->parseUrl('http://www.example.com/path/part?query=part#fragmentPart')
        );
    }

    public function testSpecDifferences()
    {
        $parser = new UrlParser();

        $this->assertEquals('www.example.com', $parser->parseUrl('http://www.example.com')->getHostname());

        $this->assertSame(null, $parser->parseUrl('www.example.com'));
        $this->assertSame(null, $parser->parseRelative('http://www.example.com'));

        $path = $parser->parseRelative('www.example.com');
        $this->assertEquals(false, $path->getHostname());
        $this->assertEquals('www.example.com', $path->getPath());

        $host = $parser->parseRelative('//www.example.com');
        $this->assertEquals('www.example.com', $host->getHostname());
        $this->assertEquals('', $host->getPath());
    }

    public function testIP4AddressDots()
    {
        $parser = new UrlParser();
        $this->assertEquals(false, $parser->parseUrl('http://192-168-0-1')->getIPAddress(false));
        $this->assertEquals('192.168.0.1', $parser->parseUrl('http://192.168.0.1')->getIPAddress(false));
    }

    public function testMinimalURIs()
    {
        $parser = new UrlParser();
        $this->assertEquals('a', $parser->parseUrl('a:')->getScheme());
        $this->assertSame('', $parser->parseRelative('')->getPath());
    }
}
