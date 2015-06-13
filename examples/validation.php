<?php

require __DIR__ . '/../src/autoload.php';

function is_valid_url($url) {
    static $parser = null;

    if ($parser === null) {
        $parser = new \Riimu\Kit\UrlParser\UriParser();
    }

    if (($info = $parser->parseUrl($url)) === null) {
        return false;
    }

    $scheme = $info->getScheme();
    $host = $info->getHostname();

    if ($scheme !== 'http' && $scheme !== 'https') {
        return false;
    } elseif ($host === false || strlen($host) < 4) {
        return false;
    }

    return true;
}


var_dump(is_valid_url('http://www.example.com')); // true
var_dump(is_valid_url('something else'));         // false
