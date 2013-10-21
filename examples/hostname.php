<?php

set_include_path(__DIR__ . '/../src');
spl_autoload_register();

$parser = new \Riimu\Kit\UrlParser\UrlParser();
$info = $parser->parseUrl('http://foo:bar@www.example.com:80/path/part?query=part#fragmentPart');
echo $info->getHostname(); // Outputs 'www.example.com'
