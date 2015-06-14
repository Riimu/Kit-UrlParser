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
        $this->assertSame('', (string) new Uri());
    }

    public function testUriScheme()
    {
        $this->assertSame('http:', (string) (new Uri())->withScheme('http'));
    }

    public function testUriAuthority()
    {
        $this->assertSame(
            '//user:pass@www.example.com:8080',
            (string) (new Uri())->withUserInfo('user', 'pass')->withHost('www.example.com')->withPort(8080)
        );
    }

    public function testUriPath()
    {
        $this->assertSame('path/to/file.html', (string) (new Uri())->withPath('path/to/file.html'));
    }

    public function testUriQuery()
    {
        $this->assertSame('?foo=bar', (string) (new Uri())->withQuery('foo=bar'));
    }

    public function testUriFragment()
    {
        $this->assertSame('#fragment', (string) (new Uri())->withFragment('fragment'));
    }

    public function testCompleteUri()
    {
        $uri = (new Uri())
            ->withScheme('http')
            ->withUserInfo('user', 'pass')
            ->withHost('www.example.com')
            ->withPort(8080)
            ->withPath('path/to/file.html')
            ->withQuery('foo=bar')
            ->withFragment('fragment');

        $this->assertSame(
            'http://user:pass@www.example.com:8080/path/to/file.html?foo=bar#fragment',
            (string) $uri
        );
    }

    public function testUserInfoEncoding()
    {
        $uri = (new Uri())->withUserInfo('user/name', 'pass/word');

        $this->assertSame('user%2Fname:pass%2Fword', $uri->getUserInfo());
        $this->assertSame('user/name', $uri->getUsername());
        $this->assertSame('pass/word', $uri->getPassword());
    }

    public function testPathSegmentEncoding()
    {
        $uri = (new Uri())->withPathSegments(['foo/bar', 'baz.html']);
        $this->assertSame('foo%2Fbar/baz.html', $uri->getPath());
        $this->assertSame(['foo/bar', 'baz.html'], $uri->getPathSegments());
    }

    public function testQueryParameterEncoding()
    {
        $uri = (new Uri())->withQueryParameters(['foo' => 'bar&baz=1', 'i' => '1']);
        $this->assertSame('foo=bar%26baz%3D1&i=1', $uri->getQuery());
        $this->assertSame(['foo' => 'bar&baz=1', 'i' => '1'], $uri->getQueryParameters());
    }

    public function testImmutability()
    {
        $uri = new Uri();

        $this->assertNotSame($uri, $uri->withScheme('http'));
        $this->assertNotSame($uri, $uri->withUserInfo('user', 'pass'));
        $this->assertNotSame($uri, $uri->withHost('www.example.com'));
        $this->assertNotSame($uri, $uri->withPort(8080));
        $this->assertNotSame($uri, $uri->withPath('path/to/file.html'));
        $this->assertNotSame($uri, $uri->withQuery('foo=bar'));
        $this->assertNotSame($uri, $uri->withFragment('fragment'));

        $this->assertNotSame($uri, $uri->withPathSegments(['foo', 'bar.html']));
        $this->assertNotSame($uri, $uri->withQueryParameters(['foo' => 'bar']));
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

    public function testEmptyUserInfo()
    {
        $this->assertSame('username:password', (new Uri())->withUserInfo('username', 'password')->getUserInfo());
        $this->assertSame('username', (new Uri())->withUserInfo('username')->getUserInfo());
        $this->assertSame('', (new Uri())->withUserInfo('', 'password')->getUserInfo());
    }

    public function testDoubleEncoding()
    {
        $uri = (new Uri())->withPath('foo%20bar?');
        $this->assertSame('foo%20bar%3F', $uri->getPath());
    }

    public function testEncodingNormalization()
    {
        $uri = (new Uri())->withPath('foo%afbar');
        $this->assertSame('foo%AFbar', $uri->getPath());
    }

    public function testIpAddress()
    {
        $this->assertSame(null, (new Uri())->withHost('www.example.com')->getIpAddress());
        $this->assertSame('127.0.0.1', (new Uri())->withHost('127.0.0.1')->getIpAddress());
        $this->assertSame('2001:db8::ff00:42:8329', (new Uri())->withHost('[2001:db8::ff00:42:8329]')->getIpAddress());
        $this->assertSame('future', (new Uri())->withHost('[vF.future]')->getIpAddress());
    }

    public function testTld()
    {
        $this->assertSame('', (new Uri())->getTld());
        $this->assertSame('com', (new Uri())->withHost('www.example.com')->getTld());
        $this->assertSame('com', (new Uri())->withHost('www.example.com.')->getTld());
        $this->assertSame('', (new Uri())->withHost('127.0.0.1')->getTld());
        $this->assertSame('example', (new Uri())->withHost('example')->getTld());
    }

    public function testPathExtension()
    {
        $this->assertSame('', (new Uri())->getPathExtension());
        $this->assertSame('', (new Uri())->withPath('path/to.php/file')->getPathExtension());
        $this->assertSame('html', (new Uri())->withPath('path/to/file.html')->getPathExtension());
    }

    public function testPathNormalization()
    {
        $this->assertSame('//host/path', (string) (new Uri())->withHost('host')->withPath('path'));
        $this->assertSame('//host/path', (string) (new Uri())->withHost('host')->withPath('/path'));
        $this->assertSame('//host//path', (string) (new Uri())->withHost('host')->withPath('//path'));
        $this->assertSame('//host', (string) (new Uri())->withHost('host'));
        $this->assertSame('/path', (string) (new Uri())->withPath('//path'));
    }

    public function testStandardPortOmission()
    {
        $uri = (new Uri())->withHost('www.example.com')->withPort(80);
        $this->assertSame('//www.example.com:80', (string) $uri);
        $this->assertSame('http://www.example.com', (string) $uri->withScheme('http'));
    }
}
