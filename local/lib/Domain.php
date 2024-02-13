<?php

namespace Frizus;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Frizus\Helpers\UrlHelper;

class Domain
{
    public const ALT_PRODUCTION_DOMAINS = [];

    public const GUESS_DOMAIN_MAIN_DOMAIN_PART = null;

    public static function isProductionDomain()
    {
        $productionDomainName = self::getProductionDomainName();
        $serverName = self::getServerName();

        return $productionDomainName === $serverName;
    }

    public static function isNotProductionDomain()
    {
        return !static::isProductionDomain();
    }

    public static function isNotProductionDomainWithAltProductionDomains()
    {
        return !static::isProductionDomainWithAltProductionDomains();
    }

    public static function isProductionDomainWithAltProductionDomains()
    {
        $productionDomainName = self::getProductionDomainName();
        $serverName = self::getServerName();

        $productionDomains = static::ALT_PRODUCTION_DOMAINS;
        $productionDomains[] = $productionDomainName;

        return in_array($serverName, $productionDomains, true);
    }

    public static function getProductionDomainName()
    {
        static $called = false;
        static $productionDomainName;

        if (!$called) {
            $called = true;
            $result = \CSite::GetByID('s1');
            if ($row = $result->Fetch()) {
                $siteServerName = $row['SERVER_NAME'] ?: false;
            } else {
                $siteServerName = false;
            }
            if ($siteServerName === false) {
                $siteServerName = Option::get('main', 'server_name', false);
            }

            $productionDomainName = $siteServerName;

            $productionDomainName = preg_replace('#^https?\:\:#i', '', $productionDomainName);
            $productionDomainName = preg_replace('#^\/\/#', '', $productionDomainName);
            $productionDomainName = UrlHelper::cleanDomain($productionDomainName);
        }

        return $productionDomainName;
    }

    public static function getServerName()
    {
        static $called = false;
        static $serverName;

        if (!$called) {
            $called = true;
            if (php_sapi_name() === 'cli') {
                if (!($serverName = Configuration::getValue('server_name'))) {
                    $serverName = static::guessConsoleServerName();
                }
            } else {
                if (!($serverName = $_SERVER['SERVER_NAME'])) {
                    if (!($serverName = Configuration::getValue('server_name'))) {
                        $serverName = static::guessConsoleServerName();
                    }
                }
            }

            if (!$serverName) {
                throw new \Exception('Не удалось определить имя сервера. Задайте его в /bitrix/.settings.php в server_name.value значение - строка');
            }

            $serverName = UrlHelper::cleanDomain($serverName);
        }

        return $serverName;
    }

    protected static function guessConsoleServerName()
    {
        if (!static::GUESS_DOMAIN_MAIN_DOMAIN_PART) {
            return;
        }

        foreach ([basename($_SERVER['DOCUMENT_ROOT']), basename(realpath($_SERVER['DOCUMENT_ROOT'] . '/../'))] as $guess) {
            if (preg_match('#' . preg_quote(static::GUESS_DOMAIN_MAIN_DOMAIN_PART, '#') . '#i', $guess)) {
                return $guess;
            }
        }
    }
}
