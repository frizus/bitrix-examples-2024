<?php

namespace Frizus\Helpers;

use Frizus\Domain;

class UrlHelper
{
    /**
     * @see https://stackoverflow.com/questions/41545089/how-to-force-space-to-20-in-http-build-query
     */
    public static function addQuery($url, $query, $rfc3986encoding = false)
    {
        $encodingType = $rfc3986encoding ? PHP_QUERY_RFC3986 : PHP_QUERY_RFC1738;

        if ($query) {
            $query = http_build_query($query, '', '&', $encodingType);
        }

        if (!$query) {
            return $url;
        }


        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $hash = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        } else {
            $hash = null;
        }

        $qmarkPos = strpos($url, '?');
        if ($qmarkPos !== false) {
            if (strlen($url) > ($qmarkPos + 1)) {
                $url .= '&';
            }
        } else {
            $url .= '?';
        }

        $url .= $query;

        if (isset($hash)) {
            $url .= $hash;
        }

        return $url;
    }

    public static function isCatalogUrl($url)
    {
        if (!is_string($url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return preg_match("#[\/|\\\]catalog([\/]|$)#", (string)$path);
    }

    public static function removeProductionDomain($url)
    {
        if (!is_string($url) ||
            !trim($url) ||
            !is_array($parts = parse_url($url)) ||
            !isset($parts['host']) ||
            isset($parts['user']) ||
            isset($parts['pass']) ||
            (
                isset($parts['port']) &&
                !in_array($parts['port'], [80, 443])
            ) ||
            !($domain = static::cleanDomain($parts['host'])) ||
            ($domain !== Domain::getProductionDomainName())
        ) {
            return $url;
        }

        unset($parts['scheme'], $parts['host'], $parts['port']);

        if (!$parts['path'] ||
            !trim($parts['path'])
        ) {
            $parts['path'] = '/' . (string)$parts['path'];
        }

        return static::buildUrl($parts);
    }

    /**
     * @see https://stackoverflow.com/questions/4354904/php-parse-url-reverse-parsed-url
     */
    public static function buildUrl($parts)
    {
        if (!is_array($parts)) {
            return $parts;
        }

        return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
            (isset($parts['user']) ? "{$parts['user']}" : '') .
            (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
            (isset($parts['user']) ? '@' : '') .
            (isset($parts['host']) ? "{$parts['host']}" : '') .
            (isset($parts['port']) ? ":{$parts['port']}" : '') .
            (isset($parts['path']) ? "{$parts['path']}" : '') .
            (isset($parts['query']) ? "?{$parts['query']}" : '') .
            (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }

    public static function cleanDomain($host)
    {
        if (!is_string($host)) {
            return $host;
        }

        $host = preg_replace('#^www\.#i', '', $host);
        $host = mb_strtolower($host);

        return $host;
    }
}
