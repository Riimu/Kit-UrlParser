<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides a RFC 3986 compliant solution to URL parsing.
 *
 * UrlParser provides a more accurate solution to parsing URLs compared to PHP's
 * built in parse_url(). The URLs are parsed only as defined in the spec. This
 * however, means that this class will not parse URLs that are incomplete or
 * otherwise invalid according to the spec despite the fact that people commonly
 * use these urls.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UrlParser
{
    /**
     * PCRE pattern conforming the URI spec.
     * @var string
     */
    private $urlPattern;

    /**
     * PCRE pattern conforming the relative-ref spec.
     * @var string
     */
    private $relativePattern;

    /**
     * Creates a new UrlParser and builds the parsing patterns.
     */
    public function __construct()
    {
        $patterns = $this->buildPatterns();
        $this->urlPattern = $patterns['URI'];
        $this->relativePattern = $patterns['relative-ref'];
    }

    /**
     * Parses the URL according to the URI spec and returns the UrlInfo object.
     *
     * This method will basically parse complete URLs. Essentially, the real
     * requirement is that the URL must have the scheme defined. In other words
     * 'www.example.com' will return null, but 'http://www.example.com' will
     * return an UrlInfo object.
     *
     * Any string that cannot be parsed as an URL according to the spec will
     * return a null value.
     *
     * @param string $url URL to parse
     * @return UrlInfo|null UrlInfo object from the URL or null on failure
     */
    public function parseUrl($url)
    {
        if (preg_match("#^$this->urlPattern$#", $url, $match)) {
            return new UrlInfo($url, $match);
        } else {
            return null;
        }
    }

    /**
     * Parses the URL according to relative-ref spec and returns UrlInfo object.
     *
     * The relative-ref spec differs from URI spec in that relative-ref never
     * has the scheme part defined. Note that while 'www.example.com' can be
     * parsed as relative url, it's actually part of the path and not the
     * hostname. It will only be recognized as hostname if prefixed with two
     * forward slashes, e.g. '//www.example.com'.
     *
     * @param string $url Relative URL to parse
     * @return UrlInfo|null UrlInfo object from the URL or null on failure
     */
    public function parseRelative($url)
    {
        if (preg_match("#^$this->relativePattern$#", $url, $match)) {
            return new UrlInfo($url, $match);
        } else {
            return null;
        }
    }

    /**
     * Builds the PCRE patterns according to the RFC.
     */
    private function buildPatterns()
    {
        $ALPHA = 'A-Za-z';
        $DIGIT = '0-9';
        $HEXDIG = $DIGIT . 'A-Fa-f';
        $unreserved = "$ALPHA$DIGIT\-._~";
        $sub_delims = "!$&'()*+,;=";

        $dec_octet = "(?:[$DIGIT]|[1-9][$DIGIT]|1[$DIGIT]{2}|2[0-4]$DIGIT|25[0-5])";
        $IPv4address = "(?>$dec_octet\.$dec_octet\.$dec_octet\.$dec_octet)";

        $pct_encoded = "%[$HEXDIG]{2}";
        $h16 = "[$HEXDIG]{1,4}";
        $ls32 = "(?:$h16:$h16|$IPv4address)";

        $pchar = "[$unreserved$sub_delims:@]++|$pct_encoded";

        // scheme
        $scheme = "(?P<scheme>(?>[$ALPHA][$ALPHA$DIGIT+\-.]*+))";

        // authority
        $IPv6address = str_replace(
            ' ',
            '',
            '(?P<IPv6address>' .
            "(?:                         (?:$h16:){6}$ls32)|" .
            "(?:                       ::(?:$h16:){5}$ls32)|" .
            "(?:(?:              $h16)?::(?:$h16:){4}$ls32)|" .
            "(?:(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32)|" .
            "(?:(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32)|" .
            "(?:(?:(?:$h16:){0,3}$h16)?::   $h16:    $ls32)|" .
            "(?:(?:(?:$h16:){0,4}$h16)?::            $ls32)|" .
            "(?:(?:(?:$h16:){0,5}$h16)?::            $h16 )|" .
            "(?:(?:(?:$h16:){0,6}$h16)?::                 ))"
        );

        $reg_name = "(?P<reg_name>(?>(?:[$unreserved$sub_delims]++|$pct_encoded)*))";

        $IPvFuture = "(?P<IPvFuture>v[$HEXDIG]++\.[$unreserved$sub_delims:]++)";
        $IP_literal = "(?P<IP_literal>\[(?>$IPv6address|$IPvFuture)\])";

        $port = "(?P<port>(?>[$DIGIT]*+))";
        $host = "(?P<host>$IP_literal|(?P<IPv4address>$IPv4address)|$reg_name)";
        $userinfo = "(?P<userinfo>(?>(?:[$unreserved$sub_delims:]++|$pct_encoded)*))";
        $authority = "(?P<authority>(?:$userinfo@)?$host(?::$port)?)";

        // path
        $segment = "(?>(?:$pchar)*)";
        $segment_nz = "(?>(?:$pchar)+)";
        $segment_nz_nc = "(?>([$unreserved$sub_delims@]++|$pct_encoded)+)";

        $path_abempty = "(?P<path_abempty>(?:/$segment)*)";
        $path_absolute = "(?P<path_absolute>/(?:$segment_nz(?:/$segment)*)?)";
        $path_noscheme = "(?P<path_noscheme>$segment_nz_nc(?:/$segment)*)";
        $path_rootless = "(?P<path_rootless>$segment_nz(?:/$segment)*)";
        $path_empty = '(?P<path_empty>)';

        // other
        $query = "(?P<query>(?>(?:$pchar|[/?])*))";
        $fragment = "(?P<fragment>(?>(?:$pchar|[/?])*))";

        $hier_part = "(?P<hier_part>//$authority$path_abempty|$path_absolute|$path_rootless|$path_empty)";
        $relative_part = "(?P<relative_part>//$authority$path_abempty|$path_absolute|$path_noscheme|$path_empty)";

        $URI = "$scheme:$hier_part(?:\?$query)?(?:\#$fragment)?";
        $relative_ref = "$relative_part(?:\?$query)?(?:\#$fragment)?";

        return [
            'URI' => $URI,
            'relative-ref' => $relative_ref,
        ];
    }
}
