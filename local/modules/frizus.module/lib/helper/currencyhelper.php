<?
namespace Frizus\Module\Helper;

use Bitrix\Main\Loader;

class CurrencyHelper
{
    public static function getRoubles()
    {
        static $roubles;

        if (!isset($roubles)) {
            if (Loader::includeModule('currency')) {
                if (\CCurrency::GetByID('RUB')) {
                    $roubles = 'RUB';
                } elseif (\CCurrency::GetByID('RUR')) {
                    $roubles = 'RUR';
                } else {
                    $roubles = false;
                }
            } else {
                $roubles = false;
            }
        }

        return $roubles;
    }
}