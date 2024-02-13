<?
namespace Frizus\Module;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Frizus\Module\ORM\AntiBruteForceTable;

class AntiBruteForce
{
    public static function isNotBlocked($type, $blockType, $config, $value, $fieldType = null, $field = null)
    {
        // ...
    }

    public static function notEnoughAttemptsToBlock($type, $blockType, $config, $ipAddress, $userId, $fieldType = null, $field = null)
    {
        // ...
    }

    public static function failAttempt($type, $blockType, $config, $ipAddress, $userId, $fieldType = null, $field = null)
    {
        // ...
    }

    protected static function getAntiBruteForceRecord($type, $blockType, $ipAddress, $userId, $fieldType, $field)
    {
        // ...
    }

    protected static function getAntiBruteForceRecordAlt($type, $blockType, $value, $fieldType, $field)
    {
        // ...
    }
}