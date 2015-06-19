<?php

namespace Riimu\Kit\UrlParser;

class UriParserTest extends \PHPUnit_Framework_TestCase
{
    public function testFullUri()
    {
        $parser = new UriParser();
        $uri = $parser->parse('http://user:pass@www.example.com:8080/path/to/file.html?foo=bar#fragment');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path/to/file.html', $uri->getPath());
        $this->assertSame('foo=bar', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testRelativeUri()
    {
        $parser = new UriParser();
        $uri = $parser->parse('//user:pass@www.example.com:8080/path/to/file.html?foo=bar#fragment');

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path/to/file.html', $uri->getPath());
        $this->assertSame('foo=bar', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testInvalidUri()
    {
        $parser = new UriParser();
        $this->assertNull($parser->parse('http&:'));
    }

    /**
     * @param string $uri
     * @dataProvider getBacktrackLimitUris
     */
    public function testBacktrackLimits($uri)
    {
        $parser = new UriParser();
        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $parser->parse($uri));
    }

    /**
     * @return array[]
     */
    public function getBacktrackLimitUris()
    {
        return [
            ['http://www.example.com:80/path/part?query=part#fragmentPart'],
            ['http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart'],
            ['http://foo:bar@www.example.com/path/part?query=part#fragmentPart'],
            ['http://www.example.com/path/part?query=part#fragmentPart'],
        ];
    }

    public function testHostMatching()
    {
        $parser = new UriParser();

        $this->assertSame('www.example.com', $parser->parse('//www.example.com')->getHost());
        $this->assertSame('www.example.com', $parser->parse('www.example.com')->getPath());
    }

    public function testIpAddressMatching()
    {
        $parser = new UriParser();

        $this->assertSame(null, $parser->parse('//127-0-0-1')->getIpAddress());
        $this->assertSame('127.0.0.1', $parser->parse('//127.0.0.1')->getIpAddress());
        $this->assertSame('2001:db8::ff00:42:8329', $parser->parse('//[2001:db8::ff00:42:8329]')->getIpAddress());
        $this->assertSame('future', $parser->parse('//[vF.future]')->getIpAddress());
    }

    public function testMinimalURIs()
    {
        $parser = new UriParser();

        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $parser->parse('a:'));
        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $parser->parse(''));
    }
}
