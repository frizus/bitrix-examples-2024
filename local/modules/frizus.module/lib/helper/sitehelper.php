<?
namespace Frizus\Module\Helper;

class SiteHelper
{
    public const DEFAULT = 's1';

    public static function getSiteId()
    {
        return !defined('ADMIN_SECTION') || (ADMIN_SECTION !== true) ? SITE_ID : self::DEFAULT;
    }
}