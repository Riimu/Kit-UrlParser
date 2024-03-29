<?php

namespace Riimu\Kit\UrlParser;

use PHPUnit\Framework\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriParserTest extends TestCase
{
    public function testFullUri()
    {
        $uri = $this->parse('scheme://username:password@www.example.com:8080/path/to/file.html?query=string#fragment');

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
        $uri = $this->parse('//username:password@www.example.com:8080/path/to/file.html?query=string#fragment');

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
        $this->assertInstanceOf(Uri::class, $parser->parse($uri));
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
        $this->assertSame('www.example.com', $this->parse('//www.example.com')->getHost());
        $this->assertSame('www.example.com', $this->parse('www.example.com')->getPath());
    }

    public function testIpAddressMatching()
    {
        $this->assertNull($this->parse('//127-0-0-1')->getIpAddress());
        $this->assertSame('127.0.0.1', $this->parse('//127.0.0.1')->getIpAddress());
        $this->assertSame('2001:db8::ff00:42:8329', $this->parse('//[2001:db8::ff00:42:8329]')->getIpAddress());
        $this->assertSame('future', $this->parse('//[vF.future]')->getIpAddress());
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
        $uri = $this->parse($case);

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
            Uri::class,
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
        $parser->setMode(UriParser::MODE_IDNA);
        $uri = $parser->parse('http://www.fööbär.com');

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('www.xn--fbr-rla2ga.com', $uri->getHost());
    }

    public function testIdnParsingFailure()
    {
        if (!function_exists('idn_to_ascii')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $parser = new UriParser();
        $parser->setMode(UriParser::MODE_IDNA);

        // Code point 2061, FUNCTION APPLICATION, disallowed in all IDNA variants
        $this->assertNull($parser->parse("http://www.\xE2\x81\xA1.com"));
    }

    public function testBadPortNumber()
    {
        $parser = new UriParser();
        $this->assertNull($parser->parse('http://www.example.com:65536'));
    }

    /**
     * @param $uri
     * @return Uri
     */
    private function parse($uri)
    {
        $parser = new UriParser();
        $instance = $parser->parse($uri);

        $this->assertInstanceOf(Uri::class, $instance);

        return $instance;
    }
}
