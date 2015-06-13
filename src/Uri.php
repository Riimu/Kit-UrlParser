<?php

namespace Riimu\Kit\UrlParser;

use Psr\Http\Message\UriInterface;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Uri implements UriInterface
{
    private $scheme = '';
    private $username = '';
    private $password = '';
    private $host = '';
    private $port = null;
    private $path = '';
    private $query = '';
    private $fragment = '';

    private static $standardPorts = [
        'ftp'   => 21,
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $authority = $this->getHost();
        $userInfo = $this->getUserInfo();
        $port = $this->getPort();

        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        if ($port !== null) {
            $authority = $authority . ':' . $port;
        }

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        if ($this->username === '') {
            return '';
        } elseif ($this->password !== '') {
            return $this->username . ':' . $this->password;
        }

        return $this->username;
    }

    public function getUsername()
    {
        return rawurldecode($this->username);
    }

    public function getPassword()
    {
        return rawurldecode($this->password);
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    public function getIpAddress()
    {
        preg_match(UriPattern::getHostPattern(), $this->getHost(), $match);

        if (!empty($match['IPv4address'])) {
            return $match['IPv4address'];
        } elseif (!empty($match['IP_literal'])) {
            return preg_replace('/^\\[(v[^.]+\\.)?([^\\]]+)\\]$/', '$2', $match['IP_literal']);
        }

        return null;
    }

    public function getTld()
    {
        $host = rawurldecode($this->getHost());
        $domain = strrchar($host, '.');

        if ($this->getIpAddress() !== null || $host === '') {
            return '';
        } elseif ($domain === false) {
            return $host;
        }

        return substr($domain, 1);
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        if ($this->port === $this->getStandardPort()) {
            return null;
        }

        return $this->port;
    }

    public function getStandardPort()
    {
        $scheme = $this->getScheme();

        if (isset(self::$standardPorts[$scheme])) {
            return self::$standardPorts[$scheme];
        }

        return null;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getPathSegments()
    {
        return array_map('rawurldecode', array_filter(explode('/', $this->path), 'strlen'));
    }

    public function getPathExtension()
    {
        $filename = array_slice($this->getPathSegments(), -1);
        $extension = strrchar($filename, '.');

        if ($extension === false) {
            return '';
        }

        return substr($extension, 1);
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function getQueryParameters()
    {
        parse_str($this->encode($this->query, false, '&='), $parameters);
        return $parameters ? $parameters : [];
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $scheme = (string) $scheme;

        if ($scheme === '' || preg_match(UriPattern::getSchemePattern(), $scheme)) {
            return $this->with('scheme', strtolower($scheme));
        }

        throw new \InvalidArgumentException("Invalid scheme '$scheme'");
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->username = $this->encode($user, true);

        if ($uri->username === '') {
            $password = '';
        }

        $uri->password = $this->encode($password, true);
        return $uri;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $host = (string) $host;

        if ($host === '' || preg_match(UriPattern::getHostPattern(), $host)) {
            return $this->with('host', strtolower($host));
        }

        throw new \InvalidArgumentException("Invalid host '$host'");
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if ($port !== null) {
            $port = (int) $port;

            if ($port < 0 || $port > 65535) {
                throw new \InvalidArgumentException("Invalid port number '$port'");
            }
        }

        return $this->with('port', $port);
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        return $this->with('path', $this->encode($path, true, '@/'));
    }

    public function withPathSegments(array $segments)
    {
        return $this->with(
            'path',
            implode('/', array_map('rawurlencode', array_filter($segments, 'strlen')))
        );
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        return $this->with('query', $this->encode($query, true, ':@/?'));
    }

    public function withQueryParameters(array $parameters)
    {
        return $this->with('query', http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        return $this->with('fragment', $this->encode($fragment, true, ':@/?'));
    }

    private function with($variable, $value)
    {
        if ($value === $this->$variable) {
            return $this;
        }

        $uri = clone $this;
        $uri->$variable = $value;
        return $uri;
    }

    private function encode($string, $allowSubDelimiters = false, $extra = '')
    {
        $normalized = preg_replace_callback('/%([a-f][0-9a-fA-F]|[0-9a-fA-F][a-f])/', function ($match) {
            return '%' . strtoupper($match[0]);
        }, (string) $string);

        if ($allowSubDelimiters) {
            $extra .= "!$&'()*+,;=";
        }

        $pattern = sprintf('/[^A-Za-z0-9\\-._~%s]|%%(?![0-9a-fA-F]{2})/', preg_quote($extra, '/'));

        return preg_replace_callback($pattern, function ($match) {
            return sprintf('%02X', ord($match[0]));
        }, $normalized);
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $uri = '';
        $components = [
            ['%2$s:%1$s', $this->getScheme()],
            ['%s//%s', $this->getAuthority()],
            ['%s%s', $this->getNormalizedUriPath()],
            ['%s?%s', $this->getQuery()],
            ['%s#%s', $this->getFragment()]
        ];

        foreach ($components as $definition) {
            list($format, $component) = $definition;

            if ($component !== '') {
                $uri = sprintf($format, $uri, $component);
            }
        }

        return $uri;
    }

    private function getNormalizedUriPath()
    {
        $path = $this->getPath();

        if ($path === '') {
            return '';
        } elseif ($this->getAuthority() === '') {
            return preg_replace('#^/+#', '/', $path);
        } elseif (substr($path, 0, 1) !== '/') {
            return '/' . $path;
        }

        return $path;
    }
}
