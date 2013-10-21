<?php

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Riimu\Kit\UrlParser;

class UrlInfo
{
    private $url;
    private $parts;

    public function __construct($url, $parts)
    {
        $this->url = $url;
        $this->parts = [];

        foreach ($parts as $name => $part) {
            if (is_int($name) || $part === '') {
                continue;
            }

            $this->parts[$name] = $part;
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getParts()
    {
        return $this->parts;
    }

    public function getPart($part)
    {
        return isset($this->parts[$part]) ? $this->parts[$part] : false;
    }

    public function getScheme()
    {
        return $this->getPart('scheme');
    }

    public function getUsername()
    {
        $user = $this->getPart('userinfo');

        if ($user === false) {
            return false;
        } elseif (strpos($user, ':') !== false) {
            return substr($user, 0, strpos($user, ':'));
        } else {
            return $user;
        }
    }

    public function getPassword()
    {
        $user = $this->getPart('userinfo');

        if ($user === false || strpos($user, ':') === false) {
            return false;
        } else {
            return substr($user, strpos($user, ':') + 1);
        }
    }

    public function getHostname()
    {
        return $this->getPart('host');
    }

    public function getIPAddress($nslookup = true)
    {
        if (isset($this->parts['IPv4address'])) {
            return $this->parts['IPv4address'];
        } elseif (isset($this->parts['IPv6address'])) {
            return $this->parts['IPv6address'];
        } elseif (isset($this->parts['reg_name'])) {
            if ($nslookup) {
                $ip = gethostbyname($this->parts['reg_name']);
                return $ip === $this->parts['reg_name'] ? false : $ip;
            }
        } elseif (isset($this->parts['IPvFuture'])) {
            return substr($this->parts['IPvFuture'], strpos($this->parts['IPvFuture'], '.') + 1);
        }

        return false;
    }

    public function getPort($useDefault = true)
    {
        $port = $this->getPart('port');

        if ($port === false && $useDefault) {
            switch ($this->getPart('scheme')) {
                case 'ftp':
                    return 21;
                case 'http':
                    return 80;
                case 'https':
                    return 443;
                default:
                    return false;
            }
        }

        return $port === false ? false : (int) $port;
    }

    public function getPath()
    {
        if (isset($this->parts['path_abempty'])) {
            return $this->parts['path_abempty'];
        } elseif (isset($this->parts['path_absolute'])) {
            return $this->parts['path_absolute'];
        } elseif (isset($this->parts['path_noscheme'])) {
            return $this->parts['path_noscheme'];
        } elseif (isset($this->parts['path_rootless'])) {
            return $this->parts['path_rootless'];
        }

        return '';
    }

    public function getQuery()
    {
        return $this->getPart('query');
    }

    public function getVariables()
    {
        $query = $this->getQuery();

        if ($query === false) {
            return [];
        }

        $variables = [];
        parse_str($query, $variables);

        return $variables;
    }

    public function getFragment()
    {
        return $this->getPart('fragment');
    }
}
