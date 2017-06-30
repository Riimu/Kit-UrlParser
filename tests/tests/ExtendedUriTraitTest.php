<?php

namespace Riimu\Kit\UrlParser;

use PHPUnit\Framework\TestCase;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ExtendedUriTraitTest extends TestCase
{
    public function testUserInfoDecoding()
    {
        $uri = (new Uri())->withUserInfo('user/name', 'pass/word');
        $this->assertSame('user/name', $uri->getUsername());
        $this->assertSame('pass/word', $uri->getPassword());
    }

    public function testPathSegmentKeys()
    {
        $this->assertSame(['foo', 'bar'], (new Uri())->withPath('foo/bar')->getPathSegments());
        $this->assertSame(['foo', 'bar'], (new Uri())->withPath('/foo/bar')->getPathSegments());
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

        $this->assertNotSame($uri, $uri->withPathSegments(['foo', 'bar.html']));
        $this->assertNotSame($uri, $uri->withQueryParameters(['foo' => 'bar']));
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
        $this->assertSame('', (new Uri())->getTopLevelDomain());
        $this->assertSame('com', (new Uri())->withHost('www.example.com')->getTopLevelDomain());
        $this->assertSame('com', (new Uri())->withHost('www.example.com.')->getTopLevelDomain());
        $this->assertSame('', (new Uri())->withHost('127.0.0.1')->getTopLevelDomain());
        $this->assertSame('example', (new Uri())->withHost('example')->getTopLevelDomain());
    }

    public function testPathExtension()
    {
        $this->assertSame('', (new Uri())->getPathExtension());
        $this->assertSame('', (new Uri())->withPath('path/to.php/file')->getPathExtension());
        $this->assertSame('html', (new Uri())->withPath('path/to/file.html')->getPathExtension());
    }

    public function testStandardPortOmission()
    {
        $uri = (new Uri())->withHost('www.example.com')->withPort(80);
        $this->assertSame('//www.example.com:80', (string) $uri);
        $this->assertSame('http://www.example.com', (string) $uri->withScheme('http'));
    }
}
