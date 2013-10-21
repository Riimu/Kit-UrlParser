<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides additional information about the URL.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UrlInfo
{
    /**
     * The original url that has been parsed.
     * @var string
     */
    private $url;

    /**
     * The different parts that have been parsed from the url.
     * @var array
     */
    private $parts;

    /**
     * Creates a new urlinfo object using the URL and the parts.
     * @param string $url The original url that has been parsed
     * @param array $parts The parts the have been parsed from the url
     */
    public function __construct($url, array $parts)
    {
        $this->url = $url;
        $this->parts = [];

        foreach ($parts as $name => $part) {
            if (is_int($name) || $part === '') {
                continue;
            }

            $this->parts[$name] = $part;
        }
    }

    /**
     * Returns the original URL.
     * @return string The original URL that has been parsed.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the different named parts from the URL that have been parsed.
     *
     * The array contains any nonempty parts from the url. It may contain any of
     * the following keys:
     * - scheme        : Scheme inside the url
     * - hier_part     : Part with possible authority and path
     * - relative_part : Part with possible authority and path on relative url
     * - authority     : Part with userinfo, host and port
     * - userinfo      : Part prior to @ containing username and possibly password
     * - host          : Host part of the url
     * - IP_literal    : Either IPv6address or IPvFuture
     * - IPv6address   : Possible IPv6address if used
     * - IPvFuture     : Possible IPvFuture address if used
     * - IPv4address   : Possible IPv4address if used
     * - reg_name      : Hostname when not using IP address
     * - port          : Port in the url
     * - path_abempty  : Path when authority is present
     * - path_absolute : Path that begins with /
     * - path_noscheme : Path that begins with non empty segment without :
     * - path_rootless : Path that begins with non empty segment
     * - path_empty    : Path that is empty
     * - query         : Query part of the url
     * - fragment      : Fragment part of the url
     *
     * @return array Named parts from the parsed URL
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Returns one of the parts in the url or false if not defined.
     * @see UrlInfo::getParts()
     * @param string $part Name of the part
     * @return string|false Value of that part or false if not defined
     */
    public function getPart($part)
    {
        return isset($this->parts[$part]) ? $this->parts[$part] : false;
    }

    /**
     * Returns the scheme part of the url.
     * @return string|false Scheme part of the url or false if not defined
     */
    public function getScheme()
    {
        return $this->getPart('scheme');
    }

    /**
     * Returns the username from the userinfo part of the url.
     *
     * The username in the url is the part prior to @ and possibly delimited by
     * a colon, which starts the password. If no colon is present, then the
     * entire part prior to @ is the username.
     *
     * @return string|false Username from the url of false if not defined.
     */
    public function getUsername()
    {
        $user = $this->getPart('userinfo');

        if ($user === false) {
            return false;
        } elseif (strpos($user, ':') !== false) {
            return substr($user, 0, strpos($user, ':'));
        } else {
            return $user;
        }
    }

    /**
     * Returns the password from the userinfo part of the url.
     *
     * The password is defined to begin in the url before @, but after the
     * colon. If the userinfo part does not have a colon, then false is
     * returned instead.
     *
     * @return string|false Password or false if not defined
     */
    public function getPassword()
    {
        $user = $this->getPart('userinfo');

        if ($user === false || strpos($user, ':') === false) {
            return false;
        } else {
            return substr($user, strpos($user, ':') + 1);
        }
    }

    /**
     * Returns the host part of the url.
     * @return string|false Host part from the url or false if not defined
     */
    public function getHostname()
    {
        return $this->getPart('host');
    }

    /**
     * Returns the IP address of the hostname.
     *
     * The return value depends on the hostname used in the url. If IPv4 address
     * is present in the url, then that is returned as is. When IPv6 address is
     * used, the address is returned without the enclosing [ and ]. Should
     * IPvFuture address be used, the part following version string and before
     * the closing ] is returned.
     *
     * When hostname is present in the url, the returned value depends on the
     * parameter passed to the method. If true, then a DNS lookup is made to
     * retrieve the IP address. If that fails, or false is passed, then this
     * method will simply return false if hostname is present.
     *
     * @param boolean $nslookup Whether to use DNS to determine the IP or not
     * @return string|false IP address of the host or false on failure
     */
    public function getIPAddress($nslookup = true)
    {
        if (isset($this->parts['IPv4address'])) {
            return $this->parts['IPv4address'];
        } elseif (isset($this->parts['IPv6address'])) {
            return $this->parts['IPv6address'];
        } elseif (isset($this->parts['reg_name'])) {
            if ($nslookup) {
                $ip = gethostbyname($this->parts['reg_name']);
                return $ip === $this->parts['reg_name'] ? false : $ip;
            }
        } elseif (isset($this->parts['IPvFuture'])) {
            return substr($this->parts['IPvFuture'], strpos($this->parts['IPvFuture'], '.') + 1);
        }

        return false;
    }

    /**
     * Returns the port from the url or default for the scheme.
     *
     * If no port is present in the url and the first parameter is true, this
     * method will return the default port of the scheme if known. Following
     * port numbers will be returned for different schemes:
     * - http  : 80
     * - https : 443
     * - ftp   : 21
     *
     * @param boolean $useDefault True to return scheme's default port if missing
     * @return int|false Port number or false if not defined
     */
    public function getPort($useDefault = true)
    {
        $port = $this->getPart('port');

        if ($port === false && $useDefault) {
            switch ($this->getPart('scheme')) {
                case 'ftp':
                    return 21;
                case 'http':
                    return 80;
                case 'https':
                    return 443;
                default:
                    return false;
            }
        }

        return $port === false ? false : (int) $port;
    }

    /**
     * Returns the path part of the url.
     * @return string Path part of the url or empty string if none
     */
    public function getPath()
    {
        if (isset($this->parts['path_abempty'])) {
            return $this->parts['path_abempty'];
        } elseif (isset($this->parts['path_absolute'])) {
            return $this->parts['path_absolute'];
        } elseif (isset($this->parts['path_noscheme'])) {
            return $this->parts['path_noscheme'];
        } elseif (isset($this->parts['path_rootless'])) {
            return $this->parts['path_rootless'];
        }

        return '';
    }

    /**
     * Returns the query part of the url.
     * @return string|false Query part of the url or false if not defined
     */
    public function getQuery()
    {
        return $this->getPart('query');
    }

    /**
     * Returns an array containing variables parsed from the Query.
     *
     * The variables are parsed from the string returned by getQuery() using
     * PHP's built in parse_str() function. Thus, the parsing is identical to
     * parsing of $_GET variables. If the Query is empty, and empty array will
     * be returned.
     *
     * @return array Variables parsed from the query or empty array if none
     */
    public function getVariables()
    {
        $query = $this->getQuery();

        if ($query === false) {
            return [];
        }

        $variables = [];
        parse_str($query, $variables);

        return $variables;
    }

    /**
     * Returns the fragment part of the url.
     * @return string|false Fragment part of the url or false if not defined
     */
    public function getFragment()
    {
        return $this->getPart('fragment');
    }
}
