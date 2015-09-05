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
 * for URL parsing, by employing the generic URI syntax, this library can be
 * used to parse any kind of URIs. The parser, however, will only validate that
 * the provided URI matches the generic URI syntax and it will not perform any
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

    /** @var bool Whether UTF8 is allowed in URI path, query and fragment or not */
    private $allowUtf8;

    /**
     * Creates a new instance of UriParser.
     */
    public function __construct()
    {
        $this->allowUtf8 = false;
    }

    /**
     * Allows or disables UTF-8 encoded characters in path, query and fragment.
     * @param bool $enabled True to allow, false to disable
     */
    public function allowUtf8($enabled = true)
    {
        $this->allowUtf8 = (bool) $enabled;
    }

    /**
     * Parses the URL using the generic URI syntax.
     *
     * This method returns the `Uri` instance constructed from the components
     * parsed from the URL. The URL is parsed using either the absolute URI
     * pattern or the relative URI pattern based on which one matches the
     * provided string. If the URL cannot be parsed as a valid URI, null is
     * returned instead.
     *
     * @param string $uri The URL to parse
     * @return Uri|null The parsed URL or null if the URL is invalid
     */
    public function parse($uri)
    {
        if (!$this->isValidString($uri)) {
            return null;
        }

        $pattern = new UriPattern();
        $pattern->allowNonAscii($this->allowUtf8);

        if ($pattern->matchUri($uri, $match)) {
            try {
                return $this->buildUri($match);
            } catch (\InvalidArgumentException $exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Tells if the string is a valid URI string according to encoding settings.
     * @param string $uri The URI to validate
     * @return bool True if the string is valid, false if not
     */
    private function isValidString($uri)
    {
        if ($this->allowUtf8) {
            return (bool) preg_match(
                '/^(?>
                    [\x00-\x7F]+                       # ASCII
                  | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
                  |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding over longs
                  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                  |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
                  |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
                  | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                  |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
                )*$/x',
                $uri
            );
        }

        return (bool) preg_match('/^[\\x00-\\x7F]*$/', $uri);
    }

    /**
     * Builds the Uri instance from the parsed components.
     * @param array<string, string> $components Components parsed from the URI
     * @return Uri The constructed URI representation
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
