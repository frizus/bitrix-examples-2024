<?
namespace Frizus\Module\Helper;

class Phone
{
    /**
     * @see https://stackoverflow.com/a/14167216
     */
    public static function format($value)
    {
        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) > 10) {
            $countryCode = substr($value, 0, strlen($value) - 10);
            $areaCode = substr($value, -10, 3);
            $nextThree = substr($value, -7, 3);
            $nextTwo = substr($value, -4, 2);
            $lastTwo = substr($value, -2);

            if ($countryCode === '8') {
                //$start = $countryCode;
                $start = '+7';
            } else {
                $start = '+' . $countryCode;
            }

            $value = $start . ' (' . $areaCode . ') ' . $nextThree . '-' . $nextTwo . '-' . $lastTwo;
        } else if (strlen($value) == 10) {
            $areaCode = substr($value, 0, 3);
            $nextThree = substr($value, 3, 3);
            $nextTwo = substr($value, 6, 2);
            $lastTwo = substr($value, 8, 2);

            $value = '(' . $areaCode . ') ' . $nextThree . '-' . $nextTwo . '-' . $lastTwo;
        } else if (strlen($value) == 7) {
            $nextThree = substr($value, 0, 3);
            $nextTwo = substr(3, 2);
            $lastTwo = substr(5, 2);

            $value = $nextThree . '-' . $nextTwo . '-' . $lastTwo;
        }

        return $value;
    }

    public static function parse($value, $withCountryCode = false)
    {
        if (!preg_match('#^[\d\(\)\.\-\+\s\r\n]+$#m', $value)) {
            return false;
        }

        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) <= 10) {
            return false;
        }

        $countryCode = substr($value, 0, strlen($value) - 10);

        if (strlen($countryCode) > 10) {
            return false;
        }

        if ($countryCode === '8') {
            $countryCode = '7';
            $value = '7' . substr($value, -10);
        }

        $value = '+' . $value;

        if ($withCountryCode) {
            return ['phone' => $value, 'countryCode' => $countryCode];
        }

        return $value;
    }

    public static function parseNoPlus($value)
    {
        if (!($value = self::parse($value))) {
            return $value;
        }

        return str_replace('+', '', self::parse($value));
    }
}