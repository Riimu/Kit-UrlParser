<?php

namespace Riimu\Kit\UrlParser;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriPattern
{
    /** @var string PCRE pattern that conforms to the URI ABNF */
    private static $absoluteUri;

    /** @var string PCRE pattern that conforms to the relative-ref ABNF */
    private static $relativeUri;

    /** @var string PCRE pattern that conforms to the scheme ABNF */
    private static $scheme;

    /** @var string PCRE pattern that conforms to the host ABNF */
    private static $host;

    public static function getAbsoluteUriPattern()
    {
        self::buildPatterns();
        return self::$absoluteUri;
    }

    public static function getRelativeUriPattern()
    {
        self::buildPatterns();
        return self::$relativeUri;
    }

    public static function getSchemePattern()
    {
        self::buildPatterns();
        return self::$scheme;
    }

    public static function getHostPattern()
    {
        self::buildPatterns();
        return self::$host;
    }

    /**
     * Builds the PCRE patterns according to the RFC.
     * @return string[] Patterns build for URL matching
     */
    private static function buildPatterns()
    {
        if (isset(self::$absoluteUri)) {
            return;
        }

        $alpha = 'A-Za-z';
        $digit = '0-9';
        $hex = $digit . 'A-Fa-f';
        $unreserved = "$alpha$digit\\-._~";
        $delimiters = "!$&'()*+,;=";

        $octet = "(?:[$digit]|[1-9][$digit]|1[$digit]{2}|2[0-4]$digit|25[0-5])";
        $IPv4address = "(?>$octet\\.$octet\\.$octet\\.$octet)";

        $encoded = "%[$hex]{2}";
        $h16 = "[$hex]{1,4}";
        $ls32 = "(?:$h16:$h16|$IPv4address)";

        $data = "[$unreserved$delimiters:@]++|$encoded";

        // Defining the scheme
        $scheme = "(?'scheme'(?>[$alpha][$alpha$digit+\\-.]*+))";

        // Defining the authority
        $IPv6address = "(?'IPv6address'" .
            "(?:(?:$h16:){6}$ls32)|" .
            "(?:::(?:$h16:){5}$ls32)|" .
            "(?:(?:$h16)?::(?:$h16:){4}$ls32)|" .
            "(?:(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32)|" .
            "(?:(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32)|" .
            "(?:(?:(?:$h16:){0,3}$h16)?::$h16:$ls32)|" .
            "(?:(?:(?:$h16:){0,4}$h16)?::$ls32)|" .
            "(?:(?:(?:$h16:){0,5}$h16)?::$h16)|" .
            "(?:(?:(?:$h16:){0,6}$h16)?::))";

        $regularName = "(?'reg_name'(?>(?:[$unreserved$delimiters]++|$encoded)*))";

        $IPvFuture = "(?'IPvFuture'v[$hex]++\\.[$unreserved$delimiters:]++)";
        $IPLiteral = "(?'IP_literal'\\[(?>$IPv6address|$IPvFuture)\\])";

        $port = "(?'port'(?>[$digit]*+))";
        $host = "(?'host'$IPLiteral|(?'IPv4address'$IPv4address)|$regularName)";
        $userInfo = "(?'userinfo'(?>(?:[$unreserved$delimiters:]++|$encoded)*))";
        $authority = "(?'authority'(?:$userInfo@)?$host(?::$port)?)";

        // Defining the path
        $segment = "(?>(?:$data)*)";
        $segmentNotEmpty = "(?>(?:$data)+)";
        $segmentNoScheme = "(?>([$unreserved$delimiters@]++|$encoded)+)";

        $pathAbsoluteEmpty = "(?'path_abempty'(?:/$segment)*)";
        $pathAbsolute = "(?'path_absolute'/(?:$segmentNotEmpty(?:/$segment)*)?)";
        $pathNoScheme = "(?'path_noscheme'$segmentNoScheme(?:/$segment)*)";
        $pathRootless = "(?'path_rootless'$segmentNotEmpty(?:/$segment)*)";
        $pathEmpty = "(?'path_empty')";

        // Defining other parts
        $query = "(?'query'(?>(?:$data|[/?])*))";
        $fragment = "(?'fragment'(?>(?:$data|[/?])*))";

        $absolutePath = "(?'hier_part'//$authority$pathAbsoluteEmpty|$pathAbsolute|$pathRootless|$pathEmpty)";
        $relativePath = "(?'relative_part'//$authority$pathAbsoluteEmpty|$pathAbsolute|$pathNoScheme|$pathEmpty)";

        self::$absoluteUri = "#^$scheme:$absolutePath(?:\\?$query)?(?:\\#$fragment)?$#";
        self::$relativeUri = "#^$relativePath(?:\\?$query)?(?:\\#$fragment)?$#";
        self::$scheme      = "#^$scheme$#";
        self::$host        = "#^$host$#";
    }
}
