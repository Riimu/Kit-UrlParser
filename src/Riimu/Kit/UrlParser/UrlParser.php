<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Riimu\Kit\UrlParser;

class UrlParser
{
    private $urlPattern;
    private $relativePattern;

    public function __construct()
    {
        $this->buildPatterns();
    }

    public function parseUrl($url)
    {
        if (preg_match("#^$this->urlPattern$#", $url, $match)) {
            return new UrlInfo($url, $match);
        } else {
            return null;
        }
    }

    public function parseRelative($url)
    {
        if (preg_match("#^$this->relativePattern$#", $url, $match)) {
            return new UrlInfo($url, $match);
        } else {
            return null;
        }
    }

    private function buildPatterns()
    {
        $ALPHA = "A-Za-z";
        $DIGIT = "0-9";
        $HEXDIG = $DIGIT . "A-Fa-f";
        $unreserved = "$ALPHA$DIGIT\-._~";
        $sub_delims = "!$&'()*+,;=";

        $dec_octet = "(?:[$DIGIT]|[1-9][$DIGIT]|1[$DIGIT]{2}|2[0-4]$DIGIT|25[0-5])";
        $IPv4address = "$dec_octet.$dec_octet.$dec_octet.$dec_octet";

        $pct_encoded = "%[$HEXDIG]{2}";
        $h16 = "[$HEXDIG]{1,4}";
        $ls32 = "(?:$h16:$h16|$IPv4address)";

        $pchar = "(?>[$unreserved$sub_delims:@]+|$pct_encoded)";

        // scheme
        $scheme = "(?P<scheme>[$ALPHA][$ALPHA$DIGIT+\-.]*)";

        // authority
        $IPv6address = str_replace(' ', '', "(?P<IPv6address>" .
            "(?:                         (?:$h16:){6}$ls32)|" .
            "(?:                       ::(?:$h16:){5}$ls32)|" .
            "(?:(?:              $h16)?::(?:$h16:){4}$ls32)|" .
            "(?:(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32)|" .
            "(?:(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32)|" .
            "(?:(?:(?:$h16:){0,3}$h16)?::   $h16:    $ls32)|" .
            "(?:(?:(?:$h16:){0,4}$h16)?::            $ls32)|" .
            "(?:(?:(?:$h16:){0,5}$h16)?::            $h16 )|" .
            "(?:(?:(?:$h16:){0,6}$h16)?::                 ))");

        $reg_name = "(?P<reg_name>(?>[$unreserved$sub_delims]+|$pct_encoded)*)";

        $IPvFuture = "(?P<IPvFuture>v[$HEXDIG]+\.[$unreserved$sub_delims:]+)";
        $IP_literal = "(?P<IP_literal>\[(?:$IPv6address|$IPvFuture)\])";

        $port = "(?P<port>[$DIGIT]*)";
        $host = "(?P<host>$IP_literal|(?P<IPv4address>$IPv4address)|$reg_name)";
        $userinfo = "(?P<userinfo>(?>[$unreserved$sub_delims:]+|$pct_encoded)*)";
        $authority = "(?P<authority>(?:$userinfo@)?$host(?::$port)?)";

        // path
        $segment = "(?:$pchar)*";
        $segment_nz = "(?:$pchar)+";
        $segment_nz_nc = "(?:[$unreserved$sub_delims@]+|$pct_encoded)+";

        $path_abempty = "(?P<path_abempty>(?:/$segment)*)";
        $path_absolute = "(?P<path_absolute>/(?:$segment_nz(?:/$segment)*)?)";
        $path_noscheme = "(?P<path_noscheme>$segment_nz_nc(?:/$segment)*)";
        $path_rootless = "(?P<path_rootless>$segment_nz(?:/$segment)*)";
        $path_empty = "(?P<path_empty>(?:$pchar){0})";

        // other
        $query = "(?P<query>(?:$pchar|[/?])*)";
        $fragment = "(?P<fragment>(?:$pchar|[/?])*)";

        $hier_part = "(?P<hier_part>//$authority$path_abempty|$path_absolute|$path_rootless|$path_empty)";
        $relative_part = "(?P<relative_part>//$authority$path_abempty|$path_absolute|$path_noscheme|$path_empty)";

        $URI = "$scheme:$hier_part(?:\?$query)?(?:\#$fragment)?";
        $relative_ref = "$relative_part(?:\?$query)?(?:\#$fragment)?";

        $this->urlPattern = $URI;
        $this->relativePattern = $relative_ref;
    }
}
