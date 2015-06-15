<?php

namespace Riimu\Kit\UrlParser;

use Psr\Http\Message\UriInterface;

/**
 * Immutable URI value object that also provides methods for manipulation.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Uri implements UriInterface
{
    use ExtendedUriTrait;

    /** @var string The scheme component of the URI */
    private $scheme = '';

    /** @var string The username part of the user information component */
    private $username = '';

    /** @var string The password part of the user information component */
    private $password = '';

    /** @var string The host component of the URI */
    private $host = '';

    /** @var int|null The port for the authority component or null for none */
    private $port = null;

    /** @var string The path component of the URI */
    private $path = '';

    /** @var string The query component of the URI */
    private $query = '';

    /** @var string The fragment component of the URI */
    private $fragment = '';

    /**
     * Returns the scheme component of the URI.
     *
     * Note that the returned value will always be normalized to lowercase,
     * as per RFC 3986 Section 3.1. If no scheme has been provided, an empty
     * string will be returned instead.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme or an empty string if no scheme has been provided
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Returns the authority component of the URI.
     *
     * If no authority information has been provided, an empty string will be
     * returned instead. Note that the host component in the authority component
     * will always be normalized to lowercase as per RFC 3986 Section 3.2.2.
     *
     * Also note that even if a port has been provided, but it is the standard port
     * for the current scheme, the port will not be included in the returned value.
     *
     * The format of the returned value is `[user-info@]host[:port]`
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority or an empty string if no authority information has been provided
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
     * Returns the user information component of the URI.
     *
     * The user information component contains the username and password in the
     * URI separated by a colon. If no username has been provided, an empty
     * string will be returned instead. If no password has been provided, the returned
     * value will only contain the username without the delimiting colon.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.1
     * @return string The URI user information or an empty string if no username has been provided
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

    /**
     * Retrieve the host component of the URI.
     *
     * Note that the returned value will always be normalized to lowercase,
     * as per RFC 3986 Section 3.2.2. If no host has been provided, an empty
     * string will be returned instead.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host or an empty string if no host has been provided
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns the port component of the URI.
     *
     * If no port has been provided, this method will return a null instead.
     * Note that this method will also return a null, if the provided port is
     * the standard port for the current scheme.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.3
     * @return int|null The URI port or null if no port has been provided
     */
    public function getPort()
    {
        if ($this->port === $this->getStandardPort()) {
            return null;
        }

        return $this->port;
    }

    /**
     * Returns the path component of the URI.
     *
     * If no path has been provided, an empty string will be returned instead.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path or an empty string if no path has been provided
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the query string of the URI.
     *
     * If no query string has been provided, an empty string will be returned
     * instead.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string or an empty string if no query has been provided
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns the fragment component of the URI.
     *
     * If no fragment has been provided, an empty string will be returned instead.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment or an empty string if no fragment has been provided
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Returns a new URI instance with the specified scheme.
     *
     * This method allows all different kinds of schemes. Note, however,
     * the different components are only validated based on the generic
     * URI syntax. An empty string can be used to remove the scheme. Note
     * that all provided hosts will be normalized to lowercase.
     *
     * @param string $scheme The scheme to use with the new instance
     * @return Uri A new instance with the specified scheme
     * @throws \InvalidArgumentException If the scheme is invalid
     */
    public function withScheme($scheme)
    {
        $scheme = strtolower($scheme);

        return $this->withValidate('scheme', strtolower($scheme), UriPattern::getSchemePattern());
    }

    /**
     * Returns a new URI instance with the specified user information.
     *
     * Note that the password is optional, but unless an username is provided,
     * the password will be ignored. Note that this method assumes that neither
     * the username nor the password contains encoded characters. Thus, any
     * encoded characters will be double encoded, if present. An empty username
     * can be used to remove the user information.
     *
     * @param string $user The username to use for the authority component
     * @param string|null $password The password associated with the user
     * @return Uri A new instance with the specified user information
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->username = rawurlencode($user);

        if ($uri->username === '') {
            $password = '';
        }

        $uri->password = rawurlencode($password);
        return $uri;
    }

    /**
     * Returns a new URI instance with the specified host.
     *
     * An empty host can be used to remove the host. Note that since host names
     * are treated in a case insensitive manner, the host will be normalized
     * to lowercase.
     *
     * @param string $host The hostname to use with the new instance
     * @return Uri A new instance with the specified host
     * @throws \InvalidArgumentException If the hostname is invalid.
     */
    public function withHost($host)
    {
        return $this->withValidate('host', strtolower($host), UriPattern::getHostPattern());
    }

    /**
     * Returns a new URI instance with the specified port.
     *
     * A null value can be used to remove the port number. Note that if an
     * invalid port number is provided (a number less than 0 or more than
     * 65535), an exception will be thrown.
     *
     * @param int|null $port The port to use with the new instance
     * @return Uri A new instance with the specified port
     * @throws \InvalidArgumentException If the port is invalid
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
     * Returns a new URI instance with the specified path.
     *
     * The provided path may or may not begin with a forward slash. The path
     * will be automatically normalized with the appropriate number of slashes
     * once the Uri is generated. An empty string can be used to remove the
     * path. The path may also contain percent encoded characters as these
     * characters will not be double encoded.
     *
     * @param string $path The path to use with the new instance
     * @return Uri A new instance with the specified path
     */
    public function withPath($path)
    {
        return $this->with('path', $this->encode($path, true, '@/'));
    }

    /**
     * Returns a new URI instance with the specified query string.
     *
     * An empty string can be used to remove the query. The provided value may
     * contain both encoded and decoded characters. Encoded characters will not
     * be double encoded in query.
     *
     * @param string $query The query string to use with the new instance
     * @return Uri A new instance with the specified query string
     */
    public function withQuery($query)
    {
        return $this->with('query', $this->encode($query, true, ':@/?'));
    }

    /**
     * Returns a new URI instance with the specified URI fragment.
     *
     * An empty string can be used to remove the fragment. The provided value may
     * contain both encoded and unencoded characters. The encoded characters will
     * not be double encoded.
     *
     * @param string $fragment The fragment to use with the new instance
     * @return Uri A new instance with the specified fragment
     */
    public function withFragment($fragment)
    {
        return $this->with('fragment', $this->encode($fragment, true, ':@/?'));
    }

    /**
     * Returns a new instance with the given value if the pattern matches the value.
     * @param string $variable Name of the variable to change
     * @param string $value New value of the variable
     * @param string $pattern Pattern to match the value against
     * @return Uri A new instance or the same instance
     */
    private function withValidate($variable, $value, $pattern)
    {
        if ($value === '' || preg_match($pattern, $value)) {
            return $this->with($variable, $value);
        }

        throw new \InvalidArgumentException("Invalid $variable '$value'");
    }

    /**
     * Returns a new instance with the given value, or the same instance if the value is the same.
     * @param string $variable Name of the variable to change
     * @param mixed $value New value for the variable
     * @return Uri A new instance or the same instance
     */
    private function with($variable, $value)
    {
        if ($value === $this->$variable) {
            return $this;
        }

        $uri = clone $this;
        $uri->$variable = $value;
        return $uri;
    }

    /**
     * Percent encodes the value without double encoding.
     * @param string $string The value to encode
     * @param bool $allowSubDelimiters Whether to allow sub delimiters in the value
     * @param string $extra Additional allowed characters in the value
     * @return string The encoded string
     */
    private function encode($string, $allowSubDelimiters = false, $extra = '')
    {
        $normalized = preg_replace_callback('/%([a-f][0-9a-fA-F]|[0-9a-fA-F][a-f])/', function ($match) {
            return '%' . strtoupper($match[1]);
        }, (string) $string);

        if ($allowSubDelimiters) {
            $extra .= "!$&'()*+,;=";
        }

        $pattern = sprintf('/[^%%A-Za-z0-9\\-._~%s]|%%(?![0-9a-fA-F]{2})/', preg_quote($extra, '/'));

        return preg_replace_callback($pattern, function ($match) {
            return sprintf('%%%02X', ord($match[0]));
        }, $normalized);
    }

    /**
     * Returns the string representation for the URI.
     *
     * The resulting URI will be composed of the provided components. Any components
     * that have not been provided will be omitted from the constructed URI. The
     * provided path will be normalized based on whether the authority is included
     * in the URI or not.
     *
     * @return string The constructed URI
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

    /**
     * Returns the path normalized for the string URI representation.
     * @return string The normalized path for the string representation
     */
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
