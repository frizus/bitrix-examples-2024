<?php
namespace Frizus\Module\Helper;

use Cutil;

class Str
{
    public static function ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    /**
     * @see https://stackoverflow.com/questions/3212266/detecting-russian-characters-on-a-form-in-php
     */
    public static function lcfirst($string, $cyrillicOnly = false)
    {
        if ($cyrillicOnly && !self::isCyrillic($string)) {
            return $string;
        }

        return mb_strtolower(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }

    public static function isCyrillic($string)
    {
        return (mb_strlen($string) > 1) &&
            preg_match('/^[А-Яа-яЁё]/u', $string);
    }

    public static function convertToLinuxLineFeed($string)
    {
        $string = strval($string);

        if (strpos($string, "\r") !== false) {
            $string = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $string);
        }

        return $string;
    }

    public static function normalizeText($string)
    {
        return self::normalizeString($string, true);
    }

    public static function normalizeString($string, $isText = false)
    {
        $string = strval($string);

        if (!$string) {
            return $string;
        }

        $string = self::convertToLinuxLineFeed($string);
        $string = preg_replace('#(?<![\n])\n{3,}(?![\n])#', "\n\n", $string);
        $string = preg_replace('#(^|\n)([\t ]+\n+)+#', "\n", $string);
        $string = preg_replace('#(^|\n)(\n+[\t ]+)+#', "\n", $string);
        $string = preg_replace('#^([\t ]+)#', '&nbsp;', $string);
        $string = preg_replace('#(\n)([\t ]+)#', '$1&nbsp;', $string);
        $string = preg_replace('#([\t ]+)(\n|$)#', '$2', $string);
        $string = preg_replace('#[\t ]{2,}#', ' ', $string);
        $string = trim($string);

        if ($string && $isText) {
            $string = nl2br(e($string));
        }

        return $string;
    }

    /**
     * https://stackoverflow.com/questions/25689133/php-preg-match-get-word-with-cyrillic-characters
     * @see https://www.w3.org/TR/CSS2/syndata.html#:~:text=In%20CSS%2C%20identifiers%20(including%20element,hyphen%20followed%20by%20a%20digit.
     */
    public static function convertStringToCssClass($string)
    {
        if (preg_match('~(*UTF8)[\p{Cyrillic}]+~i', $string)) {
            $string = Cutil::translit($string, 'ru', ['replace_space' => '_', 'replace_other' => '_']);
        }

        if (preg_match('#^\d#', $string)) {
            $string = '_' . $string;
        }

        $string = preg_replace('#[^a-zA-Z0-9]#', '_', $string);
        return $string;
    }

    public static function classMatch($needle, $haystack)
    {
        foreach (\Frizus\Helpers\Arr::wrap($haystack) as $item) {
            if (strcasecmp($needle, $item) === 0) {
                return true;
            }

            $neededClassStartsWith = str_starts_with($item, "\\");
            $checkAgain = false;
            $startsWith ??= str_starts_with($needle, "\\");
            if ($neededClassStartsWith) {
                if (!$startsWith) {
                    $item = preg_replace("#^\\\#", '', $item);
                    $checkAgain = true;
                }
            } else {
                if ($startsWith) {
                    $item = "\\" . $item;
                    $checkAgain = true;
                }
            }

            if ($checkAgain) {
                if (strcasecmp($needle, $item) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}