# RFC 3986 URL parser #

This library provides a RFC 3986 compatible URL parser for parsing URLs into
their components. The library uses a PCRE pattern based on the ABNF of said
specification to accurately parse the URLs into the parts described in the
RFC.

Compared to PHP's `parser_url()` function, this library provides a more accurate
implementation and the provided `UrlInfo` class provides more information about
the parsed URLs. While this library is intended for parsing URLs, it uses the
generic URI syntax, which can be used to parse and validate any URIs. This
library, however, is geared towards providing useful information from URLs.

API documentation is [available](http://kit.riimu.net/api/urlparser/) and it
can be generated using ApiGen.

[![Build Status](https://travis-ci.org/Riimu/Kit-UrlParser.svg?branch=master)](https://travis-ci.org/Riimu/Kit-UrlParser)
[![Coverage Status](https://coveralls.io/repos/Riimu/Kit-UrlParser/badge.png?branch=master)](https://coveralls.io/r/Riimu/Kit-UrlParser?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Riimu/Kit-UrlParser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Riimu/Kit-UrlParser/?branch=master)

## Installation ##

This library can be easily installed using [Composer](http://getcomposer.org/)
by including the following dependency in your `composer.json`:

```json
{
    "require": {
        "riimu/kit-urlparser": "1.*"
    }
}
```

The library will be the installed by running `composer install` and the classes
can be loaded with simply including the `vendor/autoload.php` file.

## Usage ##

The library provides two main methods via the `UrlParser` class. These methods
are `parserUrl()` and `parseRelative()`. The former conforms to the URI
definition of the RFC while the latter uses the relative-ref definition. The
difference is simply the fact that relative urls do not have the scheme part.
Both of these methods return an instance of `UrlInfo`, which provides additional
methods to retrieve information about the URL.

For example:

```php
<?php
$parser = new \Riimu\Kit\UrlParser\UrlParser();
$info = $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart');
var_dump($info->getHostname()); // Outputs 'www.example.com'
```

The `UrlInfo` class has several methods to help you get more information. The
method `getUrl()` will return the URL as is. `getParts()` will return different
nonempty parts of the url as named in the RFC. Most of the time, however, you
will want to use one of the following methods:

  * `getScheme()` returns the url scheme, e.g. "http"
  * `getUsername()` returns the username in the url, e.g. "foo"
  * `getPassword()` returns the password in the url, e.g. "bar"
  * `getHostname()` returns the hostname part in the url, e.g. "www.example.com"
  * `getIPAddress()` returns the IP Address of the hostname (via dns lookup)
  * `getPort()` returns the port in the url or default port for the scheme, e.g. "80"
  * `getPath()` returns the path part in the url, e.g. "/path/part"
  * `getQuery()` returns the query part of the url, e.g. "query=part"
  * `getVariables()` returns the variables parsed from the query, e.g. `["query" => "part"]`
  * `getFragment()` returns the fragment part of the url, e.g. "fragmentPart"

Most of these methods will return false if the information is not present in the
url with some exceptions. For more accurate descriptions, see the api
documentation.

Note that almost all parts of the URI are optional in the specification. For
example 'a:' is a valid URI while an empty string is completely valid relative
reference. If this library is used to validate URLs, you should also make sure
that scheme and hostname contain what you would expect them to be.

## Credits ##

This library is copyright 2013 - 2014 to Riikka Kalliomäki