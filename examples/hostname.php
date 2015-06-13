<?php

require __DIR__ . '/../src/autoload.php';

$parser = new \Riimu\Kit\UrlParser\UriParser();
$info = $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart');
var_dump($info->getHostname()); // Outputs 'www.example.com'

// URLs must have the scheme part or they are not valid
$info = $parser->parseUrl('www.example.com');
var_dump($info); // Is null

// Relative URLs cannot have scheme part
$info = $parser->parseRelative('http://www.example.com');
var_dump($info); // Is null

// Even though it looks like a domain, in relative URL that is the path part
$info = $parser->parseRelative('www.example.com');
var_dump($info->getHostname()); // Outputs false
var_dump($info->getPath()); // Outputs 'www.example.com'

// For relative URL to start with a domain, it must start with '//'
$info = $parser->parseRelative('//www.example.com');
var_dump($info->getHostname()); // Outputs 'www.example.com'
var_dump($info->getPath()); // Outputs ''
