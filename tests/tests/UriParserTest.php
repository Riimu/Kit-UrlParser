<?php

namespace Riimu\Kit\UrlParser;

use PHPUnit\Framework\TestCase;

class UriParserTest extends TestCase
{
    public function testFullUri()
    {
        $parser = new UriParser();
        $uri = $parser->parse(
            'scheme://username:password@www.example.com:8080/path/to/file.html?query=string#fragment'
        );

        $this->assertSame('scheme', $uri->getScheme());
        $this->assertSame('username:password', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('username:password@www.example.com:8080', $uri->getAuthority());
        $this->assertSame('/path/to/file.html', $uri->getPath());
        $this->assertSame('query=string', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testRelativeUri()
    {
        $parser = new UriParser();
        $uri = $parser->parse(
            '//username:password@www.example.com:8080/path/to/file.html?query=string#fragment'
        );

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('username:password', $uri->getUserInfo());
        $this->assertSame('www.example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('username:password@www.example.com:8080', $uri->getAuthority());
        $this->assertSame('/path/to/file.html', $uri->getPath());
        $this->assertSame('query=string', $uri->getQuery());
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

    /**
     * @param mixed $case
     * @param mixed $scheme
     * @param mixed $host
     * @param mixed $path
     * @param mixed $string
     * @dataProvider getCornerCases
     */
    public function testCornerCase($case, $scheme, $host, $path, $string)
    {
        $parser = new UriParser();
        $uri = $parser->parse($case);

        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $uri);
        $this->assertSame($scheme, $uri->getScheme());
        $this->assertSame($host, $uri->getHost());
        $this->assertSame($path, $uri->getPath());
        $this->assertSame($string, (string) $uri);
    }

    public function getCornerCases()
    {
        return [
            ['', '', '', '', ''],
            ['scheme:', 'scheme', '', '', 'scheme:'],
            ['http:non/root/path', 'http', '', 'non/root/path', 'http:non/root/path'],
            ['http:///rooted/path', 'http', '', '/rooted/path', 'http:/rooted/path'],
            ['http:////absolute/double/slash', 'http', '', '//absolute/double/slash', 'http:/absolute/double/slash'],
            [
                'http://authority//absolute/double/slash',
                'http',
                'authority',
                '//absolute/double/slash',
                'http://authority//absolute/double/slash',
            ],
            ['//authority', '', 'authority', '', '//authority'],
            ['//authority/rooted/path', '', 'authority', '/rooted/path', '//authority/rooted/path'],
        ];
    }

    public function testUtfParsing()
    {
        $parser = new UriParser();
        $this->assertNull($parser->parse(
            'http://usernäme:pässword@www.example.com/föö/bär.html?föö=bär#fööbär'
        ));

        $this->assertNull($parser->parse('http://www.fööbär.com'));
        $this->assertNull($parser->parse("http://www.example.com/\xFF"));

        $parser->setMode(UriParser::MODE_UTF8);

        $this->assertInstanceOf(
            'Riimu\Kit\UrlParser\Uri',
            $parser->parse('http://usernäme:pässwörd@www.example.com/föö/bär.html?föö=bär#fööbär')
        );

        $this->assertNull($parser->parse('http://www.fööbär.com'));
        $this->assertNull($parser->parse("http://www.example.com/\xFF"));
    }

    public function testIdnParsing()
    {
        if (!function_exists('idn_to_ascii')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $parser = new UriParser();
        $parser->setMode(UriParser::MODE_IDNA2003);
        $uri = $parser->parse('http://www.fööbär.com');

        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $uri);
        $this->assertSame('www.xn--fbr-rla2ga.com', $uri->getHost());
    }

    public function testIdnParsingFailure()
    {
        if (!function_exists('idn_to_ascii')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $parser = new UriParser();
        $parser->setMode(UriParser::MODE_IDNA2003);
        $this->assertNull($parser->parse("http://www.\xE2\xAC\x8C.com"));
    }

    public function testBadPortNumber()
    {
        $parser = new UriParser();
        $this->assertNull($parser->parse('http://www.example.com:65536'));
    }
}
