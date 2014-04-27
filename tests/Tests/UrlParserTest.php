<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Tests;

use \Riimu\Kit\UrlParser\UrlParser;

class UrlParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseUrl()
    {
        $parser = new UrlParser();
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo', $parser->parseUrl('http://www.example.com'));
        $this->assertEquals(null, $parser->parseUrl('notabsolute'));
        $this->assertInstanceOf('Riimu\Kit\UrlParser\UrlInfo', $parser->parseRelative('notabsolute'));
        $this->assertEquals(null, $parser->parseRelative('<'));
    }

    public function testGetUrl()
    {
        $parser = new UrlParser();
        $url = 'http://www.example.com';
        $this->assertEquals($url, $parser->parseUrl($url)->getUrl());
    }

    public function testGetParts()
    {
        $parser = new UrlParser();
        $this->assertEquals([
            'scheme' => 'http',
            'hier_part' => '//www.example.com',
            'host' => 'www.example.com',
            'authority' => 'www.example.com',
            'reg_name' => 'www.example.com',
        ], $parser->parseUrl('http://www.example.com')->getParts());
    }

    public function testGetPart()
    {
        $parser = new UrlParser();
        $info = $parser->parseUrl('http://www.example.com:80');
        $this->assertEquals(80, $info->getPart('port'));
        $this->assertEquals(false, $info->getPart('userinfo'));
    }

    public function testGetScheme()
    {
        $parser = new UrlParser();
        $infoA = $parser->parseUrl('https://www.example.com');
        $this->assertEquals('https', $infoA->getScheme());
        $infoB = $parser->parseRelative('/foo/bar');
        $this->assertEquals(false, $infoB->getScheme());
    }

    public function testGetUsername()
    {
        $parser = new UrlParser();
        $this->assertEquals('foo', $parser->
            parseUrl('http://foo:bar@www.example.com')->getUsername());
        $this->assertEquals('foo', $parser->
            parseUrl('http://foo@www.example.com')->getUsername());
        $this->assertEquals(false, $parser->
            parseUrl('http://www.example.com')->getUsername());
    }

    public function testGetPassword()
    {
        $parser = new UrlParser();
        $this->assertEquals('bar', $parser->
            parseUrl('http://foo:bar@www.example.com')->getPassword());
        $this->assertEquals(false, $parser->
            parseUrl('http://foo@www.example.com')->getPassword());
        $this->assertEquals(false, $parser->
            parseUrl('http://www.example.com')->getPassword());
    }

    public function testGetHostname()
    {
        $parser = new UrlParser();
        $this->assertEquals('www.example.com', $parser->
            parseUrl('http://www.example.com')->getHostname());
        $this->assertEquals('127.0.0.1', $parser->
            parseUrl('http://127.0.0.1')->getHostname());
    }

    public function testGetIPAddress()
    {
        $parser = new UrlParser();
        $this->assertEquals('127.0.0.1', $parser->
            parseUrl('http://127.0.0.1')->getIPAddress());
        $this->assertEquals('2001:db8::7', $parser->
            parseUrl('http://[2001:db8::7]')->getIPAddress());
        $this->assertEquals('faa:bab', $parser->
            parseUrl('http://[v1F.faa:bab]')->getIPAddress());
        $this->assertEquals(false, $parser->
            parseUrl('http://www.example.com')->getIPAddress(false));
        $this->assertEquals('127.0.0.1', $parser->
            parseUrl('http://localhost')->getIPAddress(true));
    }

    public function testGetPort()
    {
        $parser = new UrlParser();
        $this->assertEquals(false, $parser->
            parseUrl('http://www.example.com')->getPort(false));
        $this->assertEquals(80, $parser->
            parseUrl('http://www.example.com')->getPort(true));
        $this->assertEquals(443, $parser->
            parseUrl('https://www.example.com')->getPort(true));
        $this->assertEquals(21, $parser->
            parseUrl('ftp://www.example.com')->getPort(true));
        $this->assertEquals(8080, $parser->
            parseUrl('ftp://www.example.com:8080')->getPort());
        $this->assertEquals(false, $parser->
            parseUrl('scp://www.example.com')->getPort(true));
    }

    public function testGetPath()
    {
        $parser = new UrlParser();
        $this->assertSame('', $parser->
            parseUrl('http://www.example.com')->getPath());
        $this->assertEquals('/path/to/file', $parser->
            parseUrl('http://www.example.com/path/to/file')->getPath());
        $this->assertEquals('/path/to/file', $parser->
            parseUrl('http:/path/to/file')->getPath());
        $this->assertEquals('path/to/file', $parser->
            parseUrl('http:path/to/file')->getPath());
        $this->assertEquals('path/to/file', $parser->
            parseRelative('path/to/file')->getPath());
    }

    public function testGetQuery()
    {
        $parser = new UrlParser();
        $this->assertEquals('foo=bar', $parser->
            parseUrl('http://www.example.com/path/to/file?foo=bar#frag')->getQuery());
    }

    public function testGetVariables()
    {
        $parser = new UrlParser();
        $this->assertEquals([], $parser->
            parseUrl('http://www.example.com/path/to/file#frag')->getVariables());
        $this->assertEquals(['foo' => 'bar'], $parser->
            parseUrl('http://www.example.com/path/to/file?foo=bar#frag')->getVariables());
        $this->assertEquals(['foo' => ['bar', 'baz']], $parser->
            parseUrl('http://www.example.com/path/to/file?foo%5B%5D=bar&foo%5B%5D=baz#frag')->getVariables());
    }

    public function testGetFragment()
    {
        $parser = new UrlParser();
        $this->assertEquals('frag', $parser->
            parseUrl('http://www.example.com/path/to/file?foo=bar#frag')->getFragment());
    }
}
