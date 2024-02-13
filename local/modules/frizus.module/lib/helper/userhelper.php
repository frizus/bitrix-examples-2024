<?
namespace Frizus\Module\Helper;

use Bitrix\Main\GroupTable;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\UserTable;

class UserHelper
{
    public const ADMIN_USER_GROUP = 1;

    public static function getUserId()
    {
        global $USER;

        return (int)$USER->GetID();
    }

    public static function isAuthorized()
    {
        global $USER;

        return is_object($USER) && $USER->IsAuthorized();
    }

    public static function isGuest()
    {
        global $USER;

        return !is_object($USER) || !$USER->IsAuthorized();
    }

    public static function isBlocked($userId)
    {
        if (!$userId ||
            !((int)$userId > 0) ||
            (!($row = self::getUser($userId)))
        ) {
            return null;
        }

        return not_true($row['ACTIVE']) || is_true($row['BLOCKED']);
    }

    public static function isAdmin($userId)
    {
        if (!$userId ||
            !((int)$userId > 0) ||
            (!($row = self::getUser($userId)))
        ) {
            return null;
        }

        $adminGroups = self::getAdminGroups();

        foreach ($row['GROUPS'] as $group) {
            if (in_array((int)$group['GROUP_ID'], $adminGroups, true)) {
                return true;
            }
        }

        return false;
    }

    public static function clearUserCache($userId)
    {
        if (isset(self::$users[$userId])) {
            unset(self::$users[$userId]);
            return true;
        }

        return false;
    }

    public static $users = [];

    public static function getUser($userId, $noCache = false)
    {
        $userId = (int)$userId;

        if (!((int)$userId > 0)) {
            return false;
        }

        if (!isset(self::$users[$userId]) || $noCache) {
            $result = UserTable::getList([
                'filter' => [
                    '=ID' => $userId,
                ],
                'order' => [
                    'ID' => 'asc',
                ],
                'select' => [
                    'ID',
                    'ACTIVE',
                    'BLOCKED',
                    'PERSONAL_BIRTHDAY',
                    'PERSONAL_GENDER',
                    'NAME',
                    'SECOND_NAME',
                    'LAST_NAME',
                    'EMAIL',
                    'LOGIN',
                    'PERSONAL_PHONE',
                    'PHONE_AUTH.PHONE_NUMBER',
                    'GROUPS.*',
                ],
            ]);

            if (!($row = $result->fetchObject())) {
                self::$users[$userId] = false;
            } else {
                self::$users[$userId] = $row;
            }
        }

        if ($noCache) {
            $row = self::$users[$userId];
            unset(self::$users[$userId]);
        } else {
            $row = self::$users[$userId];
        }

        return $row;
    }

    public static function getAllUsersGroups()
    {
        static $ids;

        if (!isset($ids)) {
            $result = GroupTable::getList([
                'order' => [
                    'ID' => 'ASC',
                ],
                'filter' => [
                    '=NAME' => 'Все пользователи (в том числе неавторизованные)',
                ],
                'select' => [
                    'ID',
                ],
                'limit' => 1,
            ]);

            $ids = [];
            if ($row = $result->fetch()) {
                $ids[] = (int)$row['ID'];
            }
        }

        return $ids;
    }

    public static function getAdminGroups()
    {
        static $ids;

        if (!isset($ids)) {
            $result = GroupTable::getList([
                'filter' => [
                    '@NAME' => [
                        'Администраторы',
                        'Администраторы интернет-магазина',
                    ],
                    '=ANONYMOUS' => false,
                ],
                'select' => ['ID'],
            ]);

            $ids = [];
            while ($row = $result->fetch()) {
                $ids[] = intval($row['ID']);
            }

            if (!$ids || !in_array(self::ADMIN_USER_GROUP, $ids, true)) {
                $ids[] = self::ADMIN_USER_GROUP;
            }
        }

        return $ids;
    }

    public static function getMobilePhone($userId = null)
    {
        if (is_null($userId)) {
            $userId = UserHelper::getUserId();
        }

        if ($userId &&
            ($user = self::getUser($userId))
        ) {
            return Phone::parse($user['PERSONAL_PHONE']);
        }
    }

    public static function getUserGroups()
    {
        $result = null;

        if (!isset($result)) {
            /** @global \CUser $USER */
            global $USER;
            $result = array(2);
            if (isset($USER) && $USER instanceof \CUser) {
                $result = $USER->GetUserGroupArray();
                Collection::normalizeArrayValuesByInt($result, true);
            }
        }

        return $result;
    }
}