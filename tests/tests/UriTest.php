<?php

namespace Riimu\Kit\UrlParser;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyUri()
    {
        $this->assertUri('', new Uri());
    }

    public function testScheme()
    {
        $this->assertUri('scheme:', (new Uri())->withScheme('scheme'));
    }

    public function testAuthority()
    {
        $this->assertUri('//www.example.com', (new Uri())->withHost('www.example.com'));
        $this->assertUri('//username:password@', (new Uri())->withUserInfo('username', 'password'));
        $this->assertUri('//:8080', (new Uri())->withPort(8080));
        $this->assertUri('//username@:8080', (new Uri())->withUserInfo('username')->withPort(8080));

        $this->assertUri(
            '//username:password@www.example.com:8080',
            (new Uri())->withHost('www.example.com')->withUserInfo('username', 'password')->withPort(8080)
        );
    }

    public function testEmptyUserInfo()
    {
        $this->assertUri('//username@', (new Uri())->withUserInfo('username'));
        $this->assertUri('', (new Uri())->withUserInfo('', 'password'));
    }

    public function testPathWithoutAuthorityOrScheme()
    {
        $this->assertUri('path/to/file.html', (new Uri())->withPath('path/to/file.html'));
        $this->assertUri('/path/to/file.html', (new Uri())->withPath('/path/to/file.html'));
        $this->assertUri('/path/to/file.html', (new Uri())->withPath('//path/to/file.html'));
    }

    public function testPathWithScheme()
    {
        $uri = (new Uri())->withScheme('scheme');

        $this->assertUri('scheme:path/to/file.html', $uri->withPath('path/to/file.html'));
        $this->assertUri('scheme:/path/to/file.html', $uri->withPath('/path/to/file.html'));
        $this->assertUri('scheme:/path/to/file.html', $uri->withPath('//path/to/file.html'));
    }

    public function testPathWithAuthority()
    {
        $uri = (new Uri())->withHost('www.example.com');

        $this->assertUri('//www.example.com/path/to/file.html', $uri->withPath('path/to/file.html'));
        $this->assertUri('//www.example.com/path/to/file.html', $uri->withPath('/path/to/file.html'));
        $this->assertUri('//www.example.com//path/to/file.html', $uri->withPath('//path/to/file.html'));
    }

    public function testQuery()
    {
        $this->assertUri('?query=string', (new Uri())->withQuery('query=string'));
    }

    public function testFragment()
    {
        $this->assertUri('#fragment', (new Uri())->withFragment('fragment'));
    }

    /**
     * @return Uri
     */
    public function testCompleteUri()
    {
        $uri = (new Uri())
            ->withScheme('scheme')
            ->withUserInfo('username', 'password')
            ->withHost('www.example.com')
            ->withPort(8080)
            ->withPath('path/to/file.html')
            ->withQuery('query=string')
            ->withFragment('fragment');

        $this->assertUri(
            'scheme://username:password@www.example.com:8080/path/to/file.html?query=string#fragment',
            $uri
        );

        return $uri;
    }

    /**
     * @param Uri $uri
     * @depends testCompleteUri
     */
    public function testRemovingComponents(Uri $uri)
    {
        $uri = $uri
            ->withScheme('')
            ->withUserInfo('')
            ->withHost('')
            ->withPort(null)
            ->withPath('')
            ->withQuery('')
            ->withFragment('');

        $this->assertUri('', $uri);
    }

    public function testUserInfoEncoding()
    {
        $uri = (new Uri())->withUserInfo('user/name', 'pass/word');
        $this->assertUri('//user%2Fname:pass%2Fword@', $uri);
    }

    public function testPathEncoding()
    {
        $uri = (new Uri())->withPath('path:to file.html');
        $this->assertUri('path%3Ato%20file.html', $uri);
    }

    public function testDoubleEncoding()
    {
        $this->assertUri('foo%20%20bar', (new Uri())->withPath('foo%20 bar'));
    }

    public function testEncodingNormalization()
    {
        $this->assertUri('foo%AF%AFbar', (new Uri())->withPath('foo%AF%afbar'));
    }

    public function testCaseNormalization()
    {
        $this->assertUri('//www.example.com', (new Uri())->withHost('WWW.EXAMPLE.COM'));
        $this->assertUri('scheme:', (new Uri())->withScheme('SCHEME'));
    }

    public function testArrayAsPath()
    {
        $uri = new Uri();

        try {
            $uri = $uri->withPath(['foo', 'bar']);
        } catch (\Exception $exception) {
        }

        $this->assertInternalType('string', $uri->getPath());
    }

    public function testImmutability()
    {
        $uri = new Uri();

        $this->assertNotSame($uri, $uri->withScheme('scheme'));
        $this->assertNotSame($uri, $uri->withUserInfo('username', 'password'));
        $this->assertNotSame($uri, $uri->withHost('www.example.com'));
        $this->assertNotSame($uri, $uri->withPort(8080));
        $this->assertNotSame($uri, $uri->withPath('path/to/file.html'));
        $this->assertNotSame($uri, $uri->withQuery('foo=bar'));
        $this->assertNotSame($uri, $uri->withFragment('fragment'));
    }

    public function testImmutabilityOptimization()
    {
        $uri = (new Uri())->withPort(8080);
        $this->assertSame($uri, $uri->withPort(8080));
    }

    public function testInvalidScheme()
    {
        $uri = new Uri();

        $this->setExpectedException('InvalidArgumentException');
        $uri->withScheme('-invalid-');
    }

    public function testInvalidHost()
    {
        $uri = new Uri();

        $this->setExpectedException('InvalidArgumentException');
        $uri->withHost('[invalid]');
    }

    public function testInvalidNegativePort()
    {
        $uri = new Uri();

        $this->setExpectedException('InvalidArgumentException');
        $uri->withPort(-1);
    }

    public function testInvalidLargePort()
    {
        $uri = new Uri();

        $this->setExpectedException('InvalidArgumentException');
        $uri->withPort(65536);
    }

    public function testInvalidUri()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Uri('http&:');
    }

    public function testParsingMode()
    {
        $uri = new Uri('/föö/bär.html', UriParser::MODE_UTF8);
        $this->assertSame('/f%C3%B6%C3%B6/b%C3%A4r.html', $uri->getPath());
    }

    public function testInvalidParsingMode()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Uri('/föö/bär.html', UriParser::MODE_RFC3986);
    }

    /**
     * Asserts that the URI produces the expected string.
     * @param string $expected The expected string
     * @param Uri $uri The URI to test
     */
    private function assertUri($expected, $uri)
    {
        $this->assertInstanceOf('Riimu\Kit\UrlParser\Uri', $uri);
        $generated = $uri->__toString();

        $this->assertSame($expected, $generated);
        $this->assertSame($expected, (string) (new UriParser())->parse($generated));
        $this->assertSame($expected, (string) new Uri($generated));
    }
}
