<?php

require __DIR__ . '/../src/autoload.php';

$parser = new \Riimu\Kit\UrlParser\UriParser();
$info = $parser->parseUrl('http://jane:pass123@www.example.com:8080/site/index.php?action=login&prev=index#form');

// The following outputs: http://jane:pass123@www.example.com:8080/site/index.php?action=login&prev=index#form
echo $info->getUrl() . PHP_EOL;

echo $info->getScheme() . PHP_EOL;        // outputs: http
echo $info->getUsername() . PHP_EOL;      // outputs: jane
echo $info->getPassword() . PHP_EOL;      // outputs: pass123
echo $info->getHostname() . PHP_EOL;      // outputs: www.example.com
echo $info->getIpAddress() . PHP_EOL;     // outputs: 93.184.216.34
echo $info->getPort() . PHP_EOL;          // outputs: 8080
echo $info->getDefaultPort() . PHP_EOL;   // outputs: 80
echo $info->getPath() . PHP_EOL;          // outputs: /site/index.php
echo $info->getFileExtension() . PHP_EOL; // outputs: php
echo $info->getQuery() . PHP_EOL;         // outputs: action=login&prev=index
echo $info->getFragment() . PHP_EOL;      // outputs: form

// The following would dump the array ['action' => 'login', 'prev' => 'index']
var_dump($info->getVariables());
