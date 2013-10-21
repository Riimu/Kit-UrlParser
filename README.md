# RFC 3986 compatible URL parsing library #

This library provides a more complete solution to URL parsing compared to PHP's
built in function `parse_url()`. The parser implements a PCRE pattern built
according to the RFC's specifications in order to accurately parse different
parts of the url.

Please note that library uses the term URL instead of the term URI, because the
usage of this library is geared towards parsing URLs. The pattern itself,
however simply implements the generic URI syntax and it is thus possible to
parse any URIs that conform that specification.

API documentation for the classes can be generated using apigen.

## Usage ##

The library provides two main methods via the `UrlParser` class. These methods
are `parserUrl()` and `parseRelative()`. The former conforms to the URI
definition of the RFC while the latter uses the relative-ref definition. The
difference is simply the fact that relative urls do not have the scheme part.
Both of these methods return an instance of `UrlInfo`, which provides additional
methods to retrieve information about the URL.

```php
<?php
$parser = new \Riimu\Kit\UrlParser\UrlParser();
$info = $parser->parseUrl('http://www.example.com');
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

## Credits ##

This library is copyright 2013 to Riikka Kalliomäki