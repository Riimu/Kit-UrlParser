<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides a RFC 3986 compliant solution to URL parsing.
 *
 * UriParser provides URL parsing method that accurately complies with the
 * specification. Unlike the built in function `parse_url()`, this library will
 * not parse incomplete or invalid URLs in attempt to at least provide some
 * information. While this library should parse all valid URLs, it does not
 * mean that other applications always produce URLs that are valid according to
 * the specification.
 *
 * While this library is intended to be useful for parsing URLs that locate
 * internet resources, it is possible to parse any kind of URIs using this parser,
 * since the parser simply parses the URLs using the generic URI syntax. Hence,
 * the name UriParser.
 *
 * @see https://tools.ietf.org/html/rfc3986
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriParser
{
    /** @var string[] List of methods used to assign Uri components */
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
     * Parses the URL using the generic syntax.
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
        if (preg_match(UriPattern::getAbsoluteUriPattern(), $uri, $match)) {
            return $this->buildUri($match);
        } elseif (preg_match(UriPattern::getRelativeUriPattern(), $uri, $match)) {
            return $this->buildUri($match);
        }

        return null;
    }

    /**
     * Builds the URL object from the parsed components.
     * @param string[] $components Components parsed from the URL
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
