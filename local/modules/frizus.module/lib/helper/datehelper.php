<?
namespace Frizus\Module\Helper;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class DateHelper
{
    public const DEFAULT_OFFSET = 3;

    public static function getUtcDateFormatted($date = null)
    {
        if ($date instanceof DateTime) {
            $date = (clone $date)->setTimeZone(new \DateTimeZone('UTC'));
        } elseif (!isset($date)) {
            $date = (new DateTime())->setTimeZone(new \DateTimeZone('UTC'));
        }

        return $date->format('Y-m-d');
    }

    public static function getUtcDateTimeFormatted(DateTime $date = null)
    {
        if ($date instanceof DateTime) {
            $date = (clone $date)->setTimeZone(new \DateTimeZone('UTC'));
        } elseif (!isset($date)) {
            $date = new DateTime(null, null, new \DateTimeZone('UTC'));
        }

        return $date->format('Y-m-d H:i:s');
    }
}