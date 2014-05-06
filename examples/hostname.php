<?php

include '../src/UrlParser.php';
include '../src/UrlInfo.php';

$parser = new \Riimu\Kit\UrlParser\UrlParser();
$info = $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart');
var_dump($info->getHostname()); // Outputs 'www.example.com'

$info = $parser->parseUrl('www.example.com');
var_dump($info); // Is null

$info = $parser->parseRelative('http://www.example.com');
var_dump($info); // Is null

$info = $parser->parseRelative('www.example.com');
var_dump($info->getHostname()); // Outputs false
var_dump($info->getPath()); // Outputs 'www.example.com'

$info = $parser->parseRelative('//www.example.com');
var_dump($info->getHostname()); // Outputs 'www.example.com'
var_dump($info->getPath()); // Outputs ''