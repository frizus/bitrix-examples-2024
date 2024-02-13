<?
namespace Frizus\Module\Helper;

class IpAddress
{
    public const UNKNOWN_IP = 'UNKNOWN';

    /**
     * @see https://bxapi.ru/src/?module_id=main&name=Manager::getRealIp
     * @see https://stackoverflow.com/questions/1384410/php-getenvremote-addr-serious-side-effects
     */
    public static function getIp()
    {
        static $cachedIp;

        if (!isset($cachedIp)) {
            foreach ([
                         'HTTP_CLIENT_IP',
                         'HTTP_CLIENT_IP',
                         'HTTP_X_FORWARDED_FOR',
                         'HTTP_X_FORWARDED',
                         'HTTP_FORWARDED_FOR',
                         'HTTP_FORWARDED',
                         'REMOTE_ADDR'
                     ] as $key) {
                $ip = getenv($key);

                if (!$ip) {
                    continue;
                }

                if (in_array($key, ['HTTP_X_FORWARDED_FOR'], true)) {
                    $ips = preg_split('#\,\s*', $ip, -1, PREG_SPLIT_NO_EMPTY);

                    foreach ($ips as $ip) {
                        if (!preg_match("/^(10|172\\.16|192\\.168)\\./", $ip)) {
                            break 2;
                        }
                    }
                }

                break;
            }

            $cachedIp = $ip ? strval($ip) : self::UNKNOWN_IP;
        }

        return $cachedIp;
    }
}