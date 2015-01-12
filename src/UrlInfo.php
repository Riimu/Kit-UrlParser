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
    /** @var string The original parsed url */
    private $url;

    /** @var string[] All the nonempty parts parsed from the url */
    private $parts;

    /** @var integer[] List of known default ports */
    private static $defaultPorts = [
        'ftp' => 21,
        'http' => 80,
        'https' => 443,
    ];

    /**
     * Creates a new UrlInfo instance.
     * @param string $url The original url that has been parsed
     * @param string[] $parts The parts the have been parsed from the url
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
     * Returns the nonempty named parts from the parsed URL.
     *
     * The array contains all the nonempty parts that have been parsed from the
     * url. The URL may consist of following parts:
     *
     * - scheme        : Scheme defined in the URL (the part before '://')
     * - hier_part     : Part that may consist of authority and path
     * - relative_part : Part of relative URL that may consist of authority and path
     * - authority     : Part that may consist of userinfo, host and port
     * - userinfo      : Part prior to '@' that usually contains username and password
     * - host          : Part that contains either the IP address or hostname
     * - IP_literal    : Either IPv6address or IPvFuture
     * - IPv6address   : Possible IPv6address if used
     * - IPvFuture     : Possible IPvFuture address if used
     * - IPv4address   : Possible IPv4address if used
     * - reg_name      : Hostname when not using IP address
     * - port          : Port defined in the URL
     * - path_abempty  : Path when authority is present
     * - path_absolute : Path that begins with /
     * - path_noscheme : Path that begins with non empty segment without :
     * - path_rootless : Path that begins with non empty segment
     * - path_empty    : This part is always empty, so it should never be returned
     * - query         : Query part of the url (the part after '?')
     * - fragment      : Fragment part of the url (the part after '#')
     *
     * @return string[] Named nonempty parts from the parsed URL
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Returns one of the parts in the URL or false if not defined.
     * @see UrlInfo::getParts()
     * @param string $part Name of the part
     * @return string|false Value of that part or false if not defined
     */
    public function getPart($part)
    {
        return isset($this->parts[$part]) ? $this->parts[$part] : false;
    }

    /**
     * Returns the first defined part from the list of parts
     * @param string[] $list List of part names
     * @return string|false Value for the first defined part of false if none found
     */
    private function findPart(array $list)
    {
        foreach ($list as $key) {
            if (isset($this->parts[$key])) {
                return $this->parts[$key];
            }
        }

        return false;
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
     * Username is defined in the userinfo part, which is separated from the
     * host with '@'. If the userinfo part contains a colon, the username is
     * considered anything that comes before the colon. If there is no colon,
     * the entire part prior to '@' is considered the username.
     *
     * @return string|false Username from the URL of false if not defined.
     */
    public function getUsername()
    {
        return $this->getAuth(true);
    }

    /**
     * Returns the password from the userinfo part of the url.
     *
     * Password is defined in the userinfo part, which is separated from the
     * host with '@'. Password is the part of userinfo that comes after the
     * colon. If no colon is present, then false is returned instead.
     *
     * @return string|false Password from the URL of false if not defined.
     */
    public function getPassword()
    {
        return $this->getAuth(false);
    }

    /**
     * Returns username or password from the userinfo part.
     * @param boolean $username True to return username, false to return password
     * @return string|false Requested part or false if not defined
     */
    private function getAuth($username)
    {
        $info = $this->getPart('userinfo');
        $pos = strpos($info, ':');

        if ($pos === false) {
            return $username ? $info : false;
        }

        return $username ? substr($info, 0, $pos) : substr($info, $pos + 1);
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
     * Returns the IP address for the URL.
     *
     * If the IP address is defined in the URL itself, that IP address is
     * returned (without any enclosing characters or version information). If
     * the URL has a hostname instead, the IP address will be determined by
     * gethostbyname() (unless the optional parameter is set to false).
     *
     * @param boolean $resolve Whether to determine IP address for hostnames or not
     * @return string|false IP address for the URL or false if not defined
     */
    public function getIPAddress($resolve = true)
    {
        if (isset($this->parts['IPvFuture'])) {
            return substr($this->parts['IPvFuture'], strpos($this->parts['IPvFuture'], '.') + 1);
        } elseif (isset($this->parts['reg_name']) && $resolve) {
            return $this->resolveHost($this->parts['reg_name']);
        }

        return $this->findPart(['IPv4address', 'IPv6address']);
    }

    /**
     * Resolves the IP address for the hostname.
     * @param string $hostname Hostname to resolve
     * @return string|false IP address for the host or false if not found
     */
    private function resolveHost($hostname)
    {
        $address = gethostbyname($hostname);
        return $address === $hostname ? false : $address;
    }

    /**
     * Returns the port from the URL or default one for the scheme.
     *
     * If no port is present in the url and the first parameter is not set to
     * false, this method will return the default port of the scheme for known
     * schemes.
     *
     * @param boolean $useDefault Whether to return default port for the scheme or not
     * @return integer|false Port number or false if not defined
     */
    public function getPort($useDefault = true)
    {
        $port = $this->getPart('port');

        if ($port === false && $useDefault) {
            return $this->getDefaultPort();
        }

        return $port === false ? false : (int) $port;
    }

    /**
     * Returns the default port for the scheme for known schemes.
     *
     * If the scheme is one of the following, the appropriate port number will
     * be returned:
     *
     * - http  : 80
     * - https : 443
     * - ftp   : 21
     *
     * @return integer|false Default port for the scheme or false if not known
     */
    public function getDefaultPort()
    {
        $scheme = $this->getScheme();

        if ($scheme === false || !isset(self::$defaultPorts[$scheme])) {
            return false;
        }

        return self::$defaultPorts[$scheme];
    }

    /**
     * Returns the path part of the url.
     * @return string Path part of the url or empty string if none defined
     */
    public function getPath()
    {
        $path = $this->findPart([
            'path_abempty',
            'path_absolute',
            'path_noscheme',
            'path_rootless',
            'path_empty'
        ]);

        return $path === false ? '' : $path;
    }

    /**
     * Returns the file extension from the path of false if none.
     *
     * File extension is defined as the part of the path after the last period
     * (unless the period is followed by '/'). If no extension is present in the
     * path, then false is returned instead.
     *
     * @return string|false File extension from the path or false if none
     */
    public function getFileExtension()
    {
        preg_match('/\.([^.\/]+)$/', $this->getPath(), $match);
        return empty($match[1]) ? false : $match[1];
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
     * Returns an array containing variables parsed from the query.
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
        parse_str($this->getQuery(), $variables);
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
