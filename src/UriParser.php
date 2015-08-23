<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides a RFC 3986 compliant solution to URL parsing.
 *
 * UriParser provides a URL parsing method that accurately complies with the
 * specification. Unlike the built in function `parse_url()`, this library will
 * parse the URLs using a regular expression that has been built based on the
 * ABNF definition of the generic URI syntax. In other words, this library does
 * not allow any kind of invalid URLs and parses them exactly as defined in the
 * specification.
 *
 * While the intention of this library is to provide an accurate implementation
 * of URL parsing, by employing the generic URI syntax, this library can be used
 * to parse any kind of URIs. The parser, however, will only validate that the
 * provided URI matches the generic URI syntax and it will not perform any
 * additional validation based on the scheme.
 *
 * @see https://tools.ietf.org/html/rfc3986
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriParser
{
    /** @var array<string,string> List of methods used to assign the URI components */
    private static $mutators = [
        'scheme'        => 'withScheme',
        'host'          => 'withHost',
        'port'          => 'withPort',
        'path_abempty'  => 'withPath',
        'path_absolute' => 'withPath',
        'path_noscheme' => 'withPath',
        'path_rootless' => 'withPath',
        'query'         => 'withQuery',
        'fragment'      => 'withFragment',
    ];

    /**
     * Parses the URL using the generic URI syntax.
     *
     * Please note that the provided URL is parsed using either the absolute URI
     * specification or the relative URI specification (depending on which matches).
     * Thus, the method is quite permissive in what you can provide. However, the
     * results may not be what you expect, since this is intended for parsing
     * complete and valid URLs.
     *
     * For example, simply passing 'www.example.com' would parse it as a relative
     * URI with the path 'www.example.com' instead of having that as the host.
     * The string would need to be entered as 'http://www.example.com', for example,
     * if you want to domain to be correctly parsed as the host.
     *
     * @param string $uri The URL to parse
     * @return Uri|null The parsed URL or null if the URL is invalid
     */
    public function parse($uri)
    {
        $pattern = new UriPattern();

        if ($pattern->matchAbsoluteUri($uri, $match)) {
            return $this->buildUri($match);
        } elseif ($pattern->matchRelativeUri($uri, $match)) {
            return $this->buildUri($match);
        }

        return null;
    }

    /**
     * Builds the URL object from the parsed components.
     * @param array<string, string> $components Components parsed from the URL
     * @return Uri The generated URL representation
     */
    private function buildUri(array $components)
    {
        $uri = new Uri();
        $components = array_filter($components, 'strlen');

        foreach (array_intersect_key($components, self::$mutators) as $key => $value) {
            $uri = call_user_func([$uri, self::$mutators[$key]], $value);
        }

        if (isset($components['userinfo'])) {
            list($username, $password) = preg_split('/:|$/', $components['userinfo'], 2);

            return $uri->withUserInfo(rawurldecode($username), rawurldecode($password));
        }

        return $uri;
    }
}
