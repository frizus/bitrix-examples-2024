<?
namespace Frizus\Module\Helper;

use Bitrix\Main\Localization\Loc;

class LocHelper
{
    public static $debug = false;

    public static function getMessage($prefix, $code, $replacements = [], $config = [])
    {
        if (is_string($prefix)) {
            $prefix = [$prefix];
        } elseif (!is_array($prefix)) {
            $prefix = [''];
        }

        $code = str_replace(' ', '_', mb_strtoupper($code));
        $codes = [];
        foreach ($prefix as $singlePrefix) {
            $fullCode = $singlePrefix . $code;
            $message = Loc::getMessage($fullCode, $replacements ?? []);
            if (isset($message)) {
                break;
            }
        }

        if (self::$debug) {
            $message .= ' ' . $fullCode;
        }

        if ($config['lcfirst']) {
            $message = Str::lcfirst($message);
        } elseif ($config['ucfirst']) {
            $message = Str::ucfirst($message);
        }

        return $message;
    }
}